<?php

namespace App\Services\Backend\Common;

use App\Models\Admin\AdminDepartment;
use App\Models\Admin\AdminMenu;
use App\Models\Admin\AdminUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class DepartmentAccess
{
    public function actor(): AdminUser
    {
        return Auth::guard('backend')->user();
    }

    public function lock(): void
    {
        AdminDepartment::orderBy('id')->lockForUpdate()->get();
        AdminUser::orderBy('id')->lockForUpdate()->get();
        $this->actor()->refresh();
    }

    public function ids(?int $root): array
    {
        if ($root === null) {
            return [];
        }
        $ids = [$root];
        $departments = AdminDepartment::get(['id', 'parent_id']);
        do {
            $previous = count($ids);
            foreach ($departments as $department) {
                if (in_array($department->parent_id, $ids, true) && ! in_array($department->id, $ids, true)) {
                    $ids[] = $department->id;
                }
            }
        } while (count($ids) !== $previous);

        return $ids;
    }

    public function visibleIds(): array
    {
        $actor = $this->actor();
        if ($actor->isBoard()) {
            return AdminDepartment::pluck('id')->all();
        }

        return $actor->is_leader ? $this->ids($actor->department_id) : array_filter([$actor->department_id]);
    }

    public function canManage(?int $departmentId): bool
    {
        $actor = $this->actor();

        return $actor->isBoard() || ($actor->active && $actor->is_leader && in_array($departmentId, $this->ids($actor->department_id), true));
    }

    public function requireDepartment(?int $departmentId): void
    {
        abort_unless($this->canManage($departmentId), 403, __('backend/department.department_scope'));
    }

    public function requireAccount(AdminUser $target): void
    {
        $actor = $this->actor();
        abort_if($target->is_super || $target->id === $actor->id, 403, __('backend/admin.protected_account'));
        $this->requireDepartment($target->department_id);
        abort_if(! $actor->isBoard() && $target->department_id === $actor->department_id && $target->is_leader, 403, __('backend/department.department_scope'));
    }

    public function requireChild(?AdminDepartment $department, ?int $parentId): void
    {
        $actor = $this->actor();
        if ($actor->isBoard()) {
            return;
        }
        abort_unless($actor->is_leader && $parentId === $actor->department_id
            && (! $department || $department->parent_id === $actor->department_id), 403, __('backend/department.department_scope'));
    }

    public function validateGrants(array $menuIds, AdminDepartment $parent): void
    {
        $actor = $this->actor();
        $allowed = $parent->effectiveMenuIds();
        if (! $actor->isBoard()) {
            $owned = AdminMenu::whereIn('permission', $actor->permissions())->pluck('id')->all();
            $allowed = array_intersect($allowed, $owned);
        }
        if (array_diff($menuIds, $allowed)) {
            throw ValidationException::withMessages(['menu_ids' => __('backend/department.grant_scope')]);
        }
    }

    public function validateLeaderCount(?int $departmentId): void
    {
        $department = AdminDepartment::find($departmentId);
        if (! $department || $department->code === 'board') {
            return;
        }
        $leaders = AdminUser::where('department_id', $departmentId)->where('is_leader', true)->get();
        if ($leaders->count() < 1 || $leaders->count() > 2 || $leaders->contains(fn ($leader) => ! $leader->active)) {
            throw ValidationException::withMessages(['leader_ids' => __('backend/department.leader_count')]);
        }
    }

    public function setLeaders(AdminDepartment $department, array $ids, ?array $newLeader = null): void
    {
        $sources = [];
        foreach (AdminUser::whereIn('id', $ids)->get() as $leader) {
            $this->requireAccount($leader);
            abort_unless($leader->active, 422, __('backend/department.leader_count'));
            $sources[] = $leader->department_id;
        }
        AdminUser::where('department_id', $department->id)->where('is_leader', true)->whereNotIn('id', $ids)->update(['is_leader' => false]);
        foreach (AdminUser::whereIn('id', $ids)->get() as $leader) {
            $leader->forceFill(['department_id' => $department->id, 'is_leader' => true, 'auth_version' => $leader->auth_version + 1])->save();
        }
        if ($newLeader !== null) {
            $user = new AdminUser([...$newLeader, 'department_id' => $department->id, 'is_leader' => true, 'active' => true]);
            $user->forceFill(['created_ip' => request()->ip(), 'created_by' => $this->actor()->id, 'created_by_name' => $this->actor()->name])->save();
        }
        foreach (array_unique([...$sources, $department->id]) as $source) {
            $this->validateLeaderCount($source);
        }
    }
}
