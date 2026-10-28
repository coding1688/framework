<?php

namespace App\Services\Backend\Admin;

use App\Models\Admin\AdminLog;
use App\Models\Admin\AdminUser;
use App\Services\Backend\BaseService;
use App\Services\Backend\Common\DepartmentAccess;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdminLogService extends BaseService
{
    public function __construct(private DepartmentAccess $access) {}

    public function list(array $validated): LengthAwarePaginator
    {
        $data = $validated;
        $query = AdminLog::latest('id');
        if (! $this->access->actor()->isBoard()) {
            $query->whereIn('administrator_id', AdminUser::whereIn('department_id', $this->access->visibleIds())->select('id'));
        }
        if ($q = $data['q'] ?? null) {
            $query->where(fn ($query) => $query->where('actor', 'like', '%'.$q.'%')->orWhere('action', 'like', '%'.$q.'%'));
        }
        $page = $query->paginate(20, ['*'], 'page', (int) ($validated['page'] ?? 1));

        return $page;
    }
}
