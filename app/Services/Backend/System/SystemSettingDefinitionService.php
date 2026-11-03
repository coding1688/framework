<?php

namespace App\Services\Backend\System;

use App\Libs\Fields\FieldRegistry;
use App\Libs\Fields\Options\OptionContext;
use App\Libs\Settings\SettingStore;
use App\Models\System\SystemSettingDefinition;
use App\Models\System\SystemSettingValue;
use App\Services\Backend\BaseService;
use App\Services\Backend\Common\Audit;
use App\Services\Backend\Common\SettingFields;
use App\Services\Backend\Common\SettingGroupTree;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class SystemSettingDefinitionService extends BaseService
{
    public function __construct(private readonly FieldRegistry $fields, private readonly SettingFields $settingFields, private readonly SettingStore $settings, private readonly SettingGroupTree $groups) {}

    public function options(): array
    {
        $this->guard('options');

        return [...$this->groups->options(), 'input_types' => $this->fields->metadata(), 'option_source_types' => $this->fields->optionSources()->types(),
            'option_sources' => $this->fields->optionSources()->sources($this->optionContext()),
            'option_source_categories' => $this->fields->optionSources()->categories($this->optionContext()),
            'dependency_definitions' => SystemSettingDefinition::query()->where('secret', false)->orderBy('key')->get()
                ->map(fn (SystemSettingDefinition $definition): array => ['id' => $definition->id, 'group_id' => $definition->group_id, 'key' => $definition->key, 'name' => $definition->name])->values()->all()];
    }

    public function optionValues(array $validated): array
    {
        $this->guard('optionValues');
        try {
            return ['options' => (object) $this->fields->optionSources()->options($validated['option_source_type'], $validated['option_source_key'], $this->optionContext())];
        } catch (InvalidArgumentException) {
            throw ValidationException::withMessages(['option_source_key' => __('backend/common.invalid')]);
        }
    }

    private function optionContext(): OptionContext
    {
        return new OptionContext(app()->getLocale(), 'backend', (int) Auth::guard('backend')->id());
    }

    public function list(array $validated): LengthAwarePaginator
    {
        $this->guard('list');
        $query = SystemSettingDefinition::with('group')->orderBy('group_id')->orderBy('sort')->orderBy('id');
        if ($q = $validated['q'] ?? null) {
            $query->where('key', 'like', '%'.$q.'%');
        }
        if (isset($validated['group_id'])) {
            $query->where('group_id', $validated['group_id']);
        }
        if (isset($validated['active'])) {
            $query->where('active', $validated['active']);
        }

        return $query->paginate(20, ['*'], 'page', (int) ($validated['page'] ?? 1));
    }

    public function detail(int $id): SystemSettingDefinition
    {
        $this->guard('detail');

        return SystemSettingDefinition::with('group')->findOrFail($id);
    }

    public function create(array $validated): SystemSettingDefinition
    {
        $this->guard('create');
        $validated = $this->settingFields->prepare($validated);
        $model = DB::transaction(function () use ($validated): SystemSettingDefinition {
            $this->validateDependency($validated);
            $model = SystemSettingDefinition::create($validated);
            Audit::record('development.setting.create', (string) $model->id);

            return $model;
        });
        $this->settings->forget();

        return $model;
    }

    public function update(array $validated, int $id): void
    {
        $this->guard('update');
        $validated = $this->settingFields->prepare($validated);
        DB::transaction(function () use ($validated, $id): void {
            $model = SystemSettingDefinition::lockForUpdate()->findOrFail($id);
            $value = SystemSettingValue::where('setting_definition_id', $id)->lockForUpdate()->first();
            if ($value !== null && $model->secret && ! $validated['secret']) {
                throw ValidationException::withMessages(['secret' => __('backend/system/settings.secret_downgrade')]);
            }
            $hasDependents = SystemSettingDefinition::where('depends_on', $model->key)->exists();
            if ($hasDependents && ($model->key !== $validated['key'] || (int) $model->group_id !== (int) $validated['group_id'])) {
                throw ValidationException::withMessages(['key' => __('backend/common.in_use')]);
            }
            $this->validateDependency($validated, $model);
            $candidate = new SystemSettingDefinition($validated);
            if ($value !== null) {
                $this->settingFields->filter($candidate, $value->payload, 'validation');
            }
            $model->update($validated);
            Audit::record('development.setting.update', (string) $id);
        });
        $this->settings->forget();
    }

    public function delete(int $id): void
    {
        $this->guard('delete');
        DB::transaction(function () use ($id): void {
            $model = SystemSettingDefinition::lockForUpdate()->findOrFail($id);
            if ($model->value()->exists() || SystemSettingDefinition::where('depends_on', $model->key)->exists()) {
                throw ValidationException::withMessages(['id' => __('backend/common.in_use')]);
            }
            $model->delete();
            Audit::record('development.setting.delete', (string) $id);
        });
        $this->settings->forget();
    }

    /** @param array<string, mixed> $validated */
    private function validateDependency(array $validated, ?SystemSettingDefinition $model = null): void
    {
        $dependsOn = $validated['depends_on'] ?? null;
        if ($dependsOn === null) {
            if (($validated['depends_value'] ?? null) !== null) {
                throw ValidationException::withMessages(['depends_on' => __('backend/common.invalid')]);
            }

            return;
        }
        $definitions = SystemSettingDefinition::where('group_id', $validated['group_id'])->get()->keyBy('key');
        $dependency = $definitions->get($dependsOn);
        if ($dependency === null || $dependency->secret) {
            throw ValidationException::withMessages(['depends_on' => __('backend/common.invalid')]);
        }
        $seen = [];
        foreach (array_filter([$validated['key'], $model?->key]) as $key) {
            $seen[$key] = true;
        }
        while ($dependsOn !== null) {
            if (isset($seen[$dependsOn])) {
                throw ValidationException::withMessages(['depends_on' => __('backend/common.cycle')]);
            }
            $seen[$dependsOn] = true;
            $dependsOn = $definitions->get($dependsOn)?->depends_on;
        }
    }

    private function guard(string $action): void
    {
        $this->authorize('development.setting.'.$action, true);
    }
}
