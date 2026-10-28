<?php

namespace App\Services\Backend\System;

use App\Libs\Fields\FieldRegistry;
use App\Libs\Settings\SettingResolverRegistry;
use App\Libs\Settings\SettingStore;
use App\Models\System\SystemSettingDefinition;
use App\Models\System\SystemSettingValue;
use App\Services\Backend\BaseService;
use App\Services\Backend\Common\Audit;
use App\Services\Backend\Common\SettingFields;
use App\Services\Backend\Common\SettingGroupTree;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SystemSettingValueService extends BaseService
{
    public function __construct(private readonly FieldRegistry $fields, private readonly SettingFields $settingFields, private readonly SettingStore $settings, private readonly SettingResolverRegistry $resolvers, private readonly SettingGroupTree $groups) {}

    public function options(): array
    {
        $this->guard('options');

        return [...$this->groups->options(), 'field_types' => $this->fields->metadata()];
    }

    public function list(array $validated): Collection
    {
        $this->guard('list');
        $this->activeGroup($validated['group_id']);

        $definitions = SystemSettingDefinition::with(['group', 'value'])->where('group_id', $validated['group_id'])->orderBy('sort')->orderBy('id')->get();
        $effective = $this->resolvers->values();
        foreach ($definitions as $definition) {
            if (! $definition->secret && isset($effective[$definition->key])) {
                $definition->setAttribute('resolved_value', $effective[$definition->key]);
            }
            $hasEffectiveValue = $this->resolvers->hasValue($definition->key);
            if ($hasEffectiveValue !== null) {
                $definition->setAttribute('has_effective_value', $hasEffectiveValue);
            }
        }

        return $definitions;
    }

    public function update(array $validated): array
    {
        $this->guard('update');
        DB::transaction(function () use ($validated): void {
            $this->activeGroup($validated['group_id']);
            $ids = array_column($validated['values'], 'definition_id');
            $definitions = SystemSettingDefinition::whereIn('id', $ids)->where('group_id', $validated['group_id'])->where('active', true)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            if ($definitions->count() !== count($ids)) {
                throw ValidationException::withMessages(['values' => __('backend/common.invalid')]);
            }
            SystemSettingValue::whereIn('setting_definition_id', $ids)->orderBy('setting_definition_id')->lockForUpdate()->get();
            $changed = [];
            foreach ($validated['values'] as $index => $entry) {
                $definition = $definitions->get($entry['definition_id']);
                if ($entry['clear'] ?? false) {
                    SystemSettingValue::where('setting_definition_id', $definition->id)->delete();
                    $changed[] = $definition->key;

                    continue;
                }
                $value = $entry['value'] ?? [];
                if ($definition->secret && array_diff(array_keys($value), ['value_string']) !== []) {
                    throw ValidationException::withMessages(['values.'.$index.'.value' => __('backend/common.invalid')]);
                }
                if ($definition->secret && trim((string) ($value['value_string'] ?? '')) === '') {
                    continue;
                }
                $payload = $this->settingFields->filter($definition, $value, 'values.'.$index.'.value');
                SystemSettingValue::updateOrCreate(['setting_definition_id' => $definition->id], ['payload' => $payload]);
                $changed[] = $definition->key;
            }
            Audit::record('system.setting.update', (string) $validated['group_id'], ['keys' => $changed]);
        });
        $this->settings->forget();

        return ['translation_locales' => config('platform.locales')];
    }

    public function status(array $validated, int $id): array
    {
        $this->guard('status');
        DB::transaction(function () use ($validated, $id): void {
            $definition = SystemSettingDefinition::lockForUpdate()->findOrFail($id);
            $this->activeGroup($definition->group_id);
            $definition->update(['active' => $validated['active']]);
            Audit::record('system.setting.status', (string) $id, ['active' => (bool) $validated['active']]);
        });
        $this->settings->forget();

        return ['translation_locales' => config('platform.locales')];
    }

    private function activeGroup(int $id): void
    {
        abort_unless($this->groups->active($id), 404);
    }

    private function guard(string $action): void
    {
        $this->authorize('system.setting.'.$action, true);
    }
}
