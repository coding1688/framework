<?php

namespace App\Services\Backend\System;

use App\Models\System\SystemNotificationTemplate;
use App\Services\Backend\BaseService;
use App\Services\Backend\Common\Audit;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SystemNotificationTemplateService extends BaseService
{
    public function list(array $validated): LengthAwarePaginator
    {
        $this->guard('list');
        $query = SystemNotificationTemplate::latest('id');
        if ($q = $validated['q'] ?? null) {
            $query->where('code', 'like', '%'.$q.'%');
        }
        if (isset($validated['active'])) {
            $query->where('active', $validated['active']);
        }

        return $query->paginate(20, ['*'], 'page', (int) ($validated['page'] ?? 1));
    }

    public function detail(int $id): SystemNotificationTemplate
    {
        $this->guard('detail');

        return SystemNotificationTemplate::findOrFail($id);
    }

    public function create(array $validated): SystemNotificationTemplate
    {
        $this->guard('create');

        return DB::transaction(function () use ($validated): SystemNotificationTemplate {
            $template = SystemNotificationTemplate::create($validated);
            Audit::record('system.notificationTemplate.create', (string) $template->id);

            return $template;
        });
    }

    public function update(int $id, array $validated): SystemNotificationTemplate
    {
        $this->guard('update');

        return DB::transaction(function () use ($id, $validated): SystemNotificationTemplate {
            $template = SystemNotificationTemplate::lockForUpdate()->findOrFail($id);
            $template->update($validated);
            Audit::record('system.notificationTemplate.update', (string) $id);

            return $template->fresh();
        });
    }

    public function delete(int $id): void
    {
        $this->guard('delete');
        DB::transaction(function () use ($id): void {
            SystemNotificationTemplate::lockForUpdate()->findOrFail($id)->delete();
            Audit::record('system.notificationTemplate.delete', (string) $id);
        });
    }

    private function guard(string $action): void
    {
        $this->authorize('system.notificationTemplate.'.$action, true);
    }
}
