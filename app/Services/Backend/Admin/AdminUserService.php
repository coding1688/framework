<?php

namespace App\Services\Backend\Admin;

use App\Models\Admin\AdminDepartment;
use App\Models\Admin\AdminUser;
use App\Services\Backend\BaseService;
use App\Services\Backend\Common\Audit;
use App\Services\Backend\Common\DepartmentAccess;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class AdminUserService extends BaseService
{
    public function __construct(private DepartmentAccess $access) {}

    public function list(array $validated): LengthAwarePaginator
    {
        $data = $validated;
        $query = AdminUser::with('department')->latest('id');
        if (! $this->access->actor()->isBoard()) {
            $actor = $this->access->actor();
            if ($actor->is_leader) {
                $query->where('department_id', $actor->department_id);
            } else {
                $query->whereKey($actor->id);
            }
        }
        if ($q = $data['q'] ?? null) {
            $query->where(fn ($query) => $query->where('name', 'like', '%'.$q.'%')->orWhere('username', 'like', '%'.$q.'%')->orWhere('email', 'like', '%'.$q.'%'));
        }
        if (isset($data['active'])) {
            $query->where('active', $data['active']);
        }
        if (isset($data['department_id'])) {
            $query->where('department_id', $data['department_id']);
        }
        $page = $query->paginate(15, ['*'], 'page', (int) ($validated['page'] ?? 1));

        return $page;
    }

    public function detail(int $id): AdminUser
    {
        $administrator = AdminUser::findOrFail($id);
        $actor = $this->access->actor();
        abort_unless($actor->isBoard() || ($actor->is_leader
            ? $actor->department_id === $administrator->department_id
            : $actor->id === $administrator->id), 403);

        return $administrator->load('department');
    }

    public function options(): array
    {
        return ['departments' => AdminDepartment::whereIn('id', $this->access->visibleIds())->orderBy('sort')->get(['id', 'name_i18n', 'parent_id', 'code'])];
    }

    public function create(array $validated, ?string $ip = null): AdminUser
    {
        $admin = DB::transaction(function () use ($validated, $ip) {
            abort_unless($this->access->actor()->permits('admin.user.create'), 403);
            $data = Arr::except($validated, 'role_ids');
            $data['department_id'] ??= $this->access->actor()->department_id ?? AdminDepartment::where('code', 'board')->sole()->id;
            $this->access->lockAccounts([$data['department_id']]);
            $this->access->requireDepartment($data['department_id']);
            $data['locale'] ??= app()->getLocale();
            $data['active'] ??= true;
            $admin = new AdminUser($data);
            $admin->forceFill(['created_ip' => $ip, 'created_by' => $this->access->actor()->id, 'created_by_name' => $this->access->actor()->name])->save();
            if (AdminUser::where('department_id', $admin->department_id)->where('is_leader', true)->exists()) {
                $this->access->validateLeaderCount($admin->department_id);
            }
            Audit::record('admin.user.create', (string) $admin->id, ['fields' => array_keys(Arr::except($validated, 'password'))]);

            return $admin;
        });

        return $admin;
    }

    public function update(array $validated, int $id): void
    {
        DB::transaction(function () use ($validated, $id) {
            abort_unless($this->access->actor()->permits('admin.user.update'), 403);
            $current = AdminUser::findOrFail($id);
            $this->access->lockAccounts([$current->department_id, $validated['department_id'] ?? $current->department_id], $id);
            $administrator = AdminUser::lockForUpdate()->findOrFail($id);
            $this->protect($administrator);
            $oldDepartment = $administrator->department_id;
            $administrator->fill(Arr::except($validated, 'role_ids'));
            $administrator->department_id ??= $this->access->actor()->department_id ?? AdminDepartment::where('code', 'board')->sole()->id;
            $this->access->requireDepartment($administrator->department_id);
            if ($administrator->isDirty(['email', 'active', 'department_id', 'is_leader'])) {
                $administrator->auth_version++;
            }
            $changed = array_keys($administrator->getDirty());
            $administrator->save();
            if ($administrator->is_leader) {
                $this->access->validateLeaderCount($oldDepartment);
                $this->access->validateLeaderCount($administrator->department_id);
            }
            Audit::record('admin.user.update', (string) $administrator->id, ['fields' => $changed]);
        });
    }

    public function status(array $validated, int $id): void
    {
        $data = $validated;
        DB::transaction(function () use ($id, $data) {
            abort_unless($this->access->actor()->permits('admin.user.status'), 403);
            $current = AdminUser::findOrFail($id);
            $this->access->lockAccounts([$current->department_id], $id);
            $locked = AdminUser::lockForUpdate()->findOrFail($id);
            $this->protect($locked);
            $locked->forceFill(['active' => $data['active'], 'auth_version' => $locked->auth_version + 1])->save();
            if ($locked->is_leader) {
                $this->access->validateLeaderCount($locked->department_id);
            }
            Audit::record('admin.user.status', (string) $locked->id, $data);
        });
    }

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id) {
            abort_unless($this->access->actor()->permits('admin.user.delete'), 403);
            $current = AdminUser::findOrFail($id);
            $this->access->lockAccounts([$current->department_id], $id);
            $administrator = AdminUser::lockForUpdate()->findOrFail($id);
            $this->protect($administrator);
            abort_if($administrator->active, 422, __('backend/admin.disable_before_delete'));
            Audit::record('admin.user.delete', (string) $administrator->id);
            $administrator->delete();
            if ($administrator->is_leader) {
                $this->access->validateLeaderCount($administrator->department_id);
            }
        });
    }

    public function password(array $validated, int $id, ?string $ip = null): void
    {
        $data = $validated;
        DB::transaction(function () use ($id, $data, $ip) {
            abort_unless($this->access->actor()->permits('admin.user.password'), 403);
            $current = AdminUser::findOrFail($id);
            $this->access->lockAccounts([$current->department_id], $id);
            $locked = AdminUser::lockForUpdate()->findOrFail($id);
            $this->protect($locked);
            $locked->forceFill(['password' => $data['password'], 'password_changed_at' => now(), 'password_changed_ip' => $ip, 'auth_version' => $locked->auth_version + 1])->save();
            Audit::record('admin.user.password', (string) $locked->id);
        });
    }

    private function protect(AdminUser $administrator): void
    {
        $this->access->requireAccount($administrator);
    }
}
