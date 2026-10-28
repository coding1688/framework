<?php

namespace App\Http\Controllers\Backend\System;

use App\Attributes\MenuAction;
use App\Attributes\MenuPage;
use App\Attributes\RequireSecurityPassword;
use App\Http\Controllers\Backend\BaseController;
use App\Http\Requests\Backend\System\SystemSettingGroupRequest;
use App\Http\Resources\Backend\System\SystemSettingGroupResource;
use App\Services\Backend\System\SystemSettingGroupService;
use Illuminate\Http\JsonResponse;

#[MenuPage('development.settingGroup', ['en' => 'Setting categories', 'zh_CN' => '配置分类', 'zh_TW' => '設定分類', 'th' => 'หมวดหมู่การตั้งค่า', 'ja' => '設定カテゴリー', 'ko' => '설정 분류'], '/admin/system/group', 'development/setting/List', parent: 'development', icon: 'folder-settings-line', navigable: false)]
class SystemSettingGroupController extends BaseController
{
    public function __construct(private readonly SystemSettingGroupService $service) {}

    #[MenuAction(['en' => 'List', 'zh_CN' => '列表', 'zh_TW' => '列表', 'th' => 'รายการ', 'ja' => '一覧', 'ko' => '목록'])]
    public function list(): JsonResponse
    {
        return apiSuccess($this->service->list());
    }

    #[MenuAction(['en' => 'Options', 'zh_CN' => '加载选项', 'zh_TW' => '載入選項', 'th' => 'ตัวเลือก', 'ja' => 'オプション', 'ko' => '옵션'], sort: 1)]
    public function options(): JsonResponse
    {
        return apiSuccess($this->service->options());
    }

    #[MenuAction(['en' => 'Details', 'zh_CN' => '详情', 'zh_TW' => '詳情', 'th' => 'รายละเอียด', 'ja' => '詳細', 'ko' => '세부 정보'], sort: 2)]
    public function detail(int $id): JsonResponse
    {
        return apiSuccess(new SystemSettingGroupResource($this->service->detail($id)));
    }

    #[MenuAction(['en' => 'Create', 'zh_CN' => '新增', 'zh_TW' => '新增', 'th' => 'สร้าง', 'ja' => '作成', 'ko' => '생성'], sort: 3)]
    public function create(SystemSettingGroupRequest $request): JsonResponse
    {
        return apiSuccess(new SystemSettingGroupResource($this->service->create($request->validated())), status: 201);
    }

    #[MenuAction(['en' => 'Update', 'zh_CN' => '编辑', 'zh_TW' => '編輯', 'th' => 'แก้ไข', 'ja' => '編集', 'ko' => '편집'], sort: 4)]
    #[RequireSecurityPassword(mode: 'form')]
    public function update(SystemSettingGroupRequest $request, int $id): JsonResponse
    {
        $this->service->update($request->validated(), $id);

        return apiSuccess();
    }

    #[MenuAction(['en' => 'Delete', 'zh_CN' => '删除', 'zh_TW' => '刪除', 'th' => 'ลบ', 'ja' => '削除', 'ko' => '삭제'], sort: 5)]
    public function delete(int $id): JsonResponse
    {
        $this->service->delete($id);

        return apiSuccess();
    }
}
