<?php

namespace App\Http\Controllers\Backend\Admin;

use App\Attributes\MenuAction;
use App\Attributes\MenuPage;
use App\Http\Controllers\Backend\BaseController;
use App\Http\Requests\Backend\Admin\AdminLogRequest;
use App\Http\Resources\Backend\Admin\AdminLogResource;
use App\Services\Backend\Admin\AdminLogService;
use Illuminate\Http\JsonResponse;

#[MenuPage('admin.log', ['en' => 'Audit log', 'zh_CN' => '操作日志', 'zh_TW' => '操作日誌', 'th' => 'บันทึกการตรวจสอบ', 'ja' => '監査ログ', 'ko' => '감사 로그'], '/admin/log', 'admin/log/List', parent: 'admin', icon: 'file-history-line')]
class AdminLogController extends BaseController
{
    public function __construct(private AdminLogService $service) {}

    #[MenuAction(['en' => 'List', 'zh_CN' => '列表', 'zh_TW' => '列表', 'th' => 'รายการ', 'ja' => '一覧', 'ko' => '목록'], sort: 0)]
    public function list(AdminLogRequest $request): JsonResponse
    {
        $page = $this->service->list($request->validated());

        return apiPaginate($page, AdminLogResource::collection($page));
    }
}
