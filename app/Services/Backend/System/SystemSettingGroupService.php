<?php

namespace App\Services\Backend\System;

use App\Libs\Settings\SettingStore;
use App\Models\System\SystemSettingGroup;
use App\Services\Backend\BaseService;
use App\Services\Backend\Common\Audit;
use App\Services\Backend\Common\SettingGroupTree;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SystemSettingGroupService extends BaseService
{
    public function __construct(private readonly SettingGroupTree $tree, private readonly SettingStore $settings) {}

    public function list(): array
    {
        $this->guard('list');

        return $this->tree->options();
    }

    public function options(): array
    {
        $this->guard('options');

        return $this->tree->options();
    }

    public function detail(int $id): SystemSettingGroup
    {
        $this->guard('detail');

        return $this->enrich(SystemSettingGroup::findOrFail($id));
    }

    public function create(array $validated): SystemSettingGroup
    {
        $this->guard('create');
        $model = DB::transaction(function () use ($validated): SystemSettingGroup {
            $this->validateHierarchy($validated['parent_id'] ?? null);
            $model = SystemSettingGroup::create($validated);
            Audit::record('development.settingGroup.create', (string) $model->id);

            return $model;
        });
        $this->settings->forget();

        return $this->enrich($model);
    }

    public function update(array $validated, int $id): void
    {
        $this->guard('update');
        DB::transaction(function () use ($validated, $id): void {
            $groups = SystemSettingGroup::orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $model = $groups->get($id);
            abort_unless($model !== null, 404);
            $this->validateHierarchy($validated['parent_id'] ?? null, $id, $groups);
            $model->update($validated);
            Audit::record('development.settingGroup.update', (string) $id);
        });
        $this->settings->forget();
    }

    public function delete(int $id): void
    {
        $this->guard('delete');
        DB::transaction(function () use ($id): void {
            SystemSettingGroup::orderBy('id')->lockForUpdate()->get();
            $model = SystemSettingGroup::findOrFail($id);
            if ($model->children()->exists() || $model->definitions()->exists()) {
                throw ValidationException::withMessages(['id' => __('backend/common.in_use')]);
            }
            $model->delete();
            Audit::record('development.settingGroup.delete', (string) $id);
        });
        $this->settings->forget();
    }

    private function enrich(SystemSettingGroup $model): SystemSettingGroup
    {
        $row = collect($this->tree->options()['groups'])->firstWhere('id', $model->id);
        $model->setAttribute('ancestor_ids', $row['ancestor_ids']);
        $model->setAttribute('depth', $row['depth']);
        $model->setAttribute('path', $row['path']);
        $model->setAttribute('effective_active', $row['effective_active']);
        $model->setAttribute('disabled', $row['disabled']);

        return $model;
    }

    private function validateHierarchy(?int $parentId, ?int $id = null, ?Collection $groups = null): void
    {
        $groups ??= SystemSettingGroup::orderBy('id')->lockForUpdate()->get()->keyBy('id');
        $seen = [];
        while ($parentId !== null) {
            if ($parentId === $id || isset($seen[$parentId])) {
                throw ValidationException::withMessages(['parent_id' => __('backend/common.cycle')]);
            }
            $seen[$parentId] = true;
            $parent = $groups->get($parentId);
            if ($parent === null) {
                throw ValidationException::withMessages(['parent_id' => __('backend/common.invalid')]);
            }
            $parentId = $parent->parent_id;
        }
    }

    private function guard(string $action): void
    {
        $this->authorize('development.settingGroup.'.$action, true);
    }
}
