<?php

namespace App\Http\Controllers\Backend\System;

use App\Attributes\MenuAction;
use App\Attributes\MenuPage;
use App\Http\Controllers\Backend\BaseController;
use App\Http\Requests\Backend\System\SystemNotificationTemplateRequest;
use App\Http\Resources\Backend\System\SystemNotificationTemplateResource;
use App\Services\Backend\System\SystemNotificationTemplateService;
use Illuminate\Http\JsonResponse;

#[MenuPage('system.notificationTemplate', ['en' => 'Notification templates', 'zh_CN' => '通知模板', 'zh_TW' => '通知範本', 'th' => 'เทมเพลตการแจ้งเตือน', 'ja' => '通知テンプレート', 'ko' => '알림 템플릿'], '/admin/system/notification-template', 'system/notificationTemplate/List', parent: 'system', icon: 'notification-2-line', sort: 20)]
class SystemNotificationTemplateController extends BaseController
{
    public function __construct(private readonly SystemNotificationTemplateService $service) {}

    #[MenuAction(['en' => 'List', 'zh_CN' => '列表', 'zh_TW' => '列表', 'th' => 'รายการ', 'ja' => '一覧', 'ko' => '목록'])]
    public function list(SystemNotificationTemplateRequest $request): JsonResponse
    {
        $page = $this->service->list($request->validated());

        return apiPaginate($page, SystemNotificationTemplateResource::collection($page));
    }

    #[MenuAction(['en' => 'Details', 'zh_CN' => '详情', 'zh_TW' => '詳情', 'th' => 'รายละเอียด', 'ja' => '詳細', 'ko' => '세부 정보'], sort: 1)]
    public function detail(int $id): JsonResponse
    {
        return apiSuccess(new SystemNotificationTemplateResource($this->service->detail($id)));
    }

    #[MenuAction(['en' => 'Create', 'zh_CN' => '新增', 'zh_TW' => '新增', 'th' => 'สร้าง', 'ja' => '作成', 'ko' => '생성'], sort: 2)]
    public function create(SystemNotificationTemplateRequest $request): JsonResponse
    {
        return apiSuccess(new SystemNotificationTemplateResource($this->service->create($request->validated())), status: 201);
    }

    #[MenuAction(['en' => 'Edit', 'zh_CN' => '编辑', 'zh_TW' => '編輯', 'th' => 'แก้ไข', 'ja' => '編集', 'ko' => '편집'], sort: 3)]
    public function update(int $id, SystemNotificationTemplateRequest $request): JsonResponse
    {
        return apiSuccess(new SystemNotificationTemplateResource($this->service->update($id, $request->validated())));
    }

    #[MenuAction(['en' => 'Delete', 'zh_CN' => '删除', 'zh_TW' => '刪除', 'th' => 'ลบ', 'ja' => '削除', 'ko' => '삭제'], sort: 4)]
    public function delete(int $id): JsonResponse
    {
        $this->service->delete($id);

        return apiSuccess();
    }
}
