<?php

namespace App\Services\Backend\Admin;

use App\Models\Admin\AdminDepartment;
use App\Models\Admin\AdminMenu;
use App\Models\Admin\AdminUser;
use App\Services\Backend\BaseService;
use App\Services\Backend\Common\Audit;
use App\Services\Backend\Common\DepartmentAccess;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminDepartmentService extends BaseService
{
    public function __construct(private DepartmentAccess $access) {}

    public function list(): Collection
    {
        $query = AdminDepartment::query();

        $query->whereIn('id', $this->access->visibleIds())->with(['menus', 'leaders' => function ($query) {
            $actor = $this->access->actor();
            $query->select(['id', 'department_id', 'name', 'is_leader']);
            if (! $actor->isBoard()) {
                $actor->is_leader ? $query->where('department_id', $actor->department_id) : $query->whereKey($actor->id);
            }
        }]);

        $query->orderBy('sort');

        $rows = $query->orderBy('id')->get();

        $actor = $this->access->actor();
        $rows->each(function (AdminDepartment $department) use ($actor) {
            $department->setAttribute('can_manage', $department->code !== 'board' && ($actor->isBoard() || ($actor->is_leader && $department->parent_id === $actor->department_id)));
            $department->setAttribute('effective_menu_ids', $department->effectiveMenuIds());
        });

        return $rows;
    }

    public function options(): array
    {
        $actor = $this->access->actor();
        if (! $actor->isBoard() && ! $actor->is_leader) {
            return ['leaders' => [], 'permissions' => []];
        }
        $candidates = AdminUser::whereIn('department_id', $actor->isBoard() ? $this->access->visibleIds() : [$actor->department_id])
            ->where('active', true)->where('is_super', false)->where('id', '!=', $actor->id);
        if (! $actor->isBoard()) {
            $candidates->where(fn ($query) => $query->where('department_id', '!=', $actor->department_id)->orWhere('is_leader', false));
        }

        return [
            'leaders' => $candidates->get(['id', 'name', 'department_id', 'is_leader']),
            'permissions' => AdminMenu::where('active', true)->whereNull('navigation_target_id')->when(! $actor->isBoard(), fn ($query) => $query->where(fn ($scope) => $scope->where('type', 'directory')->orWhereIn('permission', $actor->permissions())))->orderBy('sort')->get(),
        ];
    }

    public function employees(int $id): array
    {
        abort_unless($this->access->actor()->permits('admin.department.employees'), 403);

        return $this->employeeScope($id);
    }

    private function employeeScope(int $id): array
    {
        $actor = $this->access->actor();
        abort_unless(($actor->isBoard() || $actor->is_leader) && in_array($id, $this->access->visibleIds(), true), 403);
        $department = AdminDepartment::findOrFail($id);

        return [
            'employees' => AdminUser::where('department_id', $id)->orderBy('id')->get(),
            'can_set_leaders' => $department->code !== 'board' && $actor->permits('admin.department.leaders')
                && ($actor->isBoard() || $actor->department_id !== $id),
        ];
    }

    public function leaders(array $data, int $id): void
    {
        DB::transaction(function () use ($data, $id) {
            $this->access->lock();
            $scope = $this->employeeScope($id);
            abort_unless($scope['can_set_leaders'], 403);
            $department = AdminDepartment::findOrFail($id);
            $allowed = $scope['employees']->where('active', true)->pluck('id')->all();
            if (array_diff($data['leader_ids'], $allowed)) {
                throw ValidationException::withMessages(['leader_ids' => __('backend/department.department_scope')]);
            }
            $this->access->setLeaders($department, $data['leader_ids']);
            Audit::record('admin.department.leaders', (string) $id, $data);
        });
    }

    public function permissions(array $validated, int $id): void
    {
        $data = $validated;
        DB::transaction(function () use ($id, $data) {
            $this->access->lock();
            abort_unless($this->access->actor()->permits('admin.department.permissions'), 403);
            $department = AdminDepartment::findOrFail($id);
            abort_if($department->code === 'board', 422, __('backend/department.board_protected'));
            $this->access->requireChild($department, $department->parent_id);
            $this->access->validateGrants($data['menu_ids'], AdminDepartment::findOrFail($department->parent_id));
            $department->menus()->sync($data['menu_ids']);
            foreach (AdminDepartment::whereIn('id', $this->access->ids($department->id))->get() as $descendant) {
                $descendant->menus()->sync($descendant->effectiveMenuIds());
            }
            Audit::record('admin.department.permissions', (string) $id, $data);
        });
    }

    public function create(array $validated): AdminDepartment
    {
        $model = DB::transaction(function () use ($validated) {
            $this->access->lock();

            abort_unless($this->access->actor()->permits('admin.department.create'), 403);
            abort_if(! empty($validated['menu_ids']) && ! $this->access->actor()->permits('admin.department.permissions'), 403);
            $data = Arr::except($validated, ['menu_ids', 'leader_ids', 'leader', 'create_leader']);

            $data['parent_id'] ??= AdminDepartment::where('code', 'board')->sole()->id;
            $this->access->requireChild(null, $data['parent_id']);
            $this->access->validateGrants(($validated['menu_ids'] ?? null), AdminDepartment::findOrFail($data['parent_id']));

            $this->validateHierarchy($data);
            $model = AdminDepartment::query()->create($data);

            $model->menus()->sync(($validated['menu_ids'] ?? null));

            if (($validated['create_leader'] ?? true) !== false) {
                $this->access->setLeaders($model, ($validated['leader_ids'] ?? []), ($validated['leader'] ?? null));
            }

            Audit::record('admin.department.create', (string) $model->id, ['leader_ids' => $model->leaders()->pluck('id')->all(), 'menu_ids' => ($validated['menu_ids'] ?? null)]);

            return $model;
        });

        return $model;
    }

    public function update(array $validated, int $id): void
    {
        DB::transaction(function () use ($validated, $id) {
            $this->access->lock();

            abort_unless($this->access->actor()->permits('admin.department.update'), 403);
            // Serialize hierarchy edits to prevent concurrent parent-cycle creation.
            AdminDepartment::query()->orderBy('id')->lockForUpdate()->get();
            $model = AdminDepartment::query()->findOrFail($id);
            $currentGrants = $model->menus()->pluck('admin_menus.id')->all();
            $nextGrants = $validated['menu_ids'] ?? [];
            if (array_diff($currentGrants, $nextGrants) || array_diff($nextGrants, $currentGrants)) {
                abort_unless($this->access->actor()->permits('admin.department.permissions'), 403);
            }
            $currentLeaders = $model->leaders()->pluck('id')->all();
            $nextLeaders = $validated['leader_ids'] ?? [];
            if (array_diff($currentLeaders, $nextLeaders) || array_diff($nextLeaders, $currentLeaders)) {
                abort_unless($this->access->actor()->permits('admin.department.leaders'), 403);
            }
            $data = Arr::except($validated, ['menu_ids', 'leader_ids', 'leader', 'create_leader']);

            abort_if($model->code === 'board', 422, __('backend/department.board_protected'));
            $data['parent_id'] ??= AdminDepartment::where('code', 'board')->sole()->id;
            $this->access->requireChild($model, $data['parent_id']);
            $this->access->validateGrants(($validated['menu_ids'] ?? null), AdminDepartment::findOrFail($data['parent_id']));

            $this->validateHierarchy($data, $id);

            $model->update($data);

            $model->menus()->sync(($validated['menu_ids'] ?? null));

            $this->access->setLeaders($model, ($validated['leader_ids'] ?? null));
            // Revoked grants are removed throughout the subtree so they cannot silently return later.
            foreach (AdminDepartment::whereIn('id', $this->access->ids($model->id))->get() as $descendant) {
                $descendant->menus()->sync($descendant->effectiveMenuIds());
            }

            Audit::record('admin.department.update', (string) $id, ['fields' => array_keys($data), 'leader_ids' => ($validated['leader_ids'] ?? null), 'menu_ids' => ($validated['menu_ids'] ?? null)]);
        });
    }

    public function status(array $validated, int $id): void
    {
        DB::transaction(function () use ($validated, $id) {
            $this->access->lock();
            abort_unless($this->access->actor()->permits('admin.department.status'), 403);
            $model = AdminDepartment::query()->findOrFail($id);
            abort_if($model->code === 'board', 422, __('backend/department.board_protected'));
            $this->access->requireChild($model, $model->parent_id);
            $model->update(['active' => $validated['active']]);
            foreach (AdminDepartment::whereIn('id', $this->access->ids($model->id))->get() as $descendant) {
                $descendant->menus()->sync($descendant->effectiveMenuIds());
            }
            Audit::record('admin.department.status', (string) $id, ['active' => (bool) $validated['active']]);
        });
    }

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id) {
            $this->access->lock();

            abort_unless($this->access->actor()->permits('admin.department.delete'), 403);
            $model = AdminDepartment::query()->lockForUpdate()->findOrFail($id);

            abort_if($model->code === 'board', 422, __('backend/department.board_protected'));
            $this->access->requireChild($model, $model->parent_id);

            $inUse = AdminDepartment::where('parent_id', $id)->exists() || AdminUser::where('department_id', $id)->exists();
            if ($inUse) {
                throw ValidationException::withMessages(['id' => __('backend/common.in_use')]);
            }
            Audit::record('admin.department.delete', (string) $id);
            $model->delete();
        });
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
            $record = AdminDepartment::query()->findOrFail($parent);

            $parent = $record->parent_id;
        }
    }
}
