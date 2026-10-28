<?php

namespace App\Services\Backend\Admin;

use App\Models\Admin\AdminMenu;
use App\Services\Backend\BaseService;
use App\Services\Backend\Common\Audit;
use App\Services\Backend\Common\MenuDiscovery;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminMenuService extends BaseService
{
    public function __construct(private MenuDiscovery $discovery) {}

    public function list(): Collection
    {
        $rows = AdminMenu::with('navigationTarget')->orderBy('sort')->orderBy('id')->get();

        return $rows;
    }

    /**
     * @param  array{permission?: ?string}  $validated
     * @return array{created: int, updated: int}
     */
    public function sync(array $validated): array
    {
        $this->guardConfiguration();

        return DB::transaction(function () use ($validated): array {
            AdminMenu::query()->orderBy('id')->lockForUpdate()->get();
            $this->guardConfiguration();
            $permission = $validated['permission'] ?? null;
            if ($permission !== null) {
                $menu = AdminMenu::with('navigationTarget')->where('permission', $permission)->firstOrFail();
                if ($menu->navigation_target_id !== null) {
                    $target = $menu->navigationTarget;
                    if ($target === null || $target->type !== 'page' || $target->navigation_target_id !== null) {
                        throw ValidationException::withMessages(['permission' => __('backend/common.invalid')]);
                    }
                    $permission = $target->permission;
                }
            }
            $result = $this->discovery->sync($permission);
            Audit::record('admin.menu.sync', $validated['permission'] ?? 'all', $result);

            return $result;
        });
    }

    public function create(array $validated): AdminMenu
    {
        $this->guardConfiguration();
        $model = DB::transaction(function () use ($validated) {
            AdminMenu::query()->orderBy('id')->lockForUpdate()->get();
            $this->guardConfiguration();
            abort_unless(Auth::guard('backend')->user()->permits('admin.menu.create'), 403);
            $data = $validated;

            $this->validateHierarchy($data);
            AdminMenu::query()->orderBy('id')->lockForUpdate()->get();
            $model = AdminMenu::query()->create($data);
            if ($model->type === 'page') {
                $this->discovery->syncActions($model);
                $model->refresh();
            }

            Audit::record('admin.menu.create', (string) $model->id);

            return $model;
        });

        return $model;
    }

    public function update(array $validated, int $id): void
    {
        $this->guardConfiguration();
        DB::transaction(function () use ($validated, $id) {
            AdminMenu::query()->orderBy('id')->lockForUpdate()->get();
            $this->guardConfiguration();
            abort_unless(Auth::guard('backend')->user()->permits('admin.menu.update'), 403);
            // Serialize hierarchy edits to prevent concurrent parent-cycle creation.
            AdminMenu::query()->orderBy('id')->lockForUpdate()->get();
            $model = AdminMenu::query()->findOrFail($id);
            $data = $validated;

            $this->validateHierarchy($data, $id);
            if ($model->is_system) {
                if (array_key_exists('component', $data) && $data['component'] !== $model->component) {
                    throw ValidationException::withMessages(['component' => __('backend/menu.system_menu')]);
                }
                foreach (['type', 'permission', 'path', ...($model->type === 'button' ? ['parent_id'] : [])] as $field) {
                    if (($data[$field] ?? null) != $model->$field) {
                        throw ValidationException::withMessages([$field => __('backend/menu.system_menu')]);
                    }
                }
            }
            if ($data['type'] === 'button' && AdminMenu::where('parent_id', $id)->exists()) {
                throw ValidationException::withMessages(['type' => __('backend/common.in_use')]);
            }
            if ($data['type'] === 'directory' && AdminMenu::where('parent_id', $id)->where('type', 'button')->exists()) {
                throw ValidationException::withMessages(['type' => __('backend/menu.invalid_parent')]);
            }
            $model->update($data);

            Audit::record('admin.menu.update', (string) $id, ['fields' => array_keys($data)]);
        });
    }

    public function delete(int $id): void
    {
        $this->guardConfiguration();
        DB::transaction(function () use ($id) {
            AdminMenu::query()->orderBy('id')->lockForUpdate()->get();
            $this->guardConfiguration();
            abort_unless(Auth::guard('backend')->user()->permits('admin.menu.delete'), 403);
            $model = AdminMenu::query()->lockForUpdate()->findOrFail($id);

            $inUse = $model->is_system || AdminMenu::where('parent_id', $id)->exists() || AdminMenu::where('navigation_target_id', $id)->exists() || DB::table('admin_department_menu')->where('menu_id', $id)->exists();
            if ($inUse) {
                throw ValidationException::withMessages(['id' => __('backend/common.in_use')]);
            }
            Audit::record('admin.menu.delete', (string) $id);
            $model->delete();
        });
    }

    private function guardConfiguration(): void
    {
        abort_unless(Auth::guard('backend')->user()->isBoard(), 403, __('backend/menu.super_only'));
    }

    private function validateHierarchy(array $data, ?int $id = null): void
    {
        $parent = $data['parent_id'] ?? null;
        $seen = [];
        while ($parent) {
            if ($parent === $id || in_array($parent, $seen, true)) {
                throw ValidationException::withMessages(['parent_id' => __('backend/common.cycle')]);
            }
            $seen[] = $parent;
            $record = AdminMenu::query()->findOrFail($parent);
            if ($record->type === 'button') {
                throw ValidationException::withMessages(['parent_id' => __('backend/menu.invalid_parent')]);
            }
            $parent = $record->parent_id;
        }
        if ($data['type'] === 'button') {
            $parentMenu = AdminMenu::find($data['parent_id'] ?? null);
            if (! $parentMenu || $parentMenu->type !== 'page' || $parentMenu->navigation_target_id !== null) {
                throw ValidationException::withMessages(['parent_id' => __('backend/menu.invalid_parent')]);
            }
        }
    }
}
