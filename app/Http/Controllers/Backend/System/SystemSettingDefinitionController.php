<?php

namespace App\Http\Controllers\Backend\System;

use App\Attributes\MenuAction;
use App\Attributes\MenuPage;
use App\Attributes\RequireSecurityPassword;
use App\Http\Controllers\Backend\BaseController;
use App\Http\Requests\Backend\System\SystemSettingDefinitionRequest;
use App\Http\Resources\Backend\System\SystemSettingDefinitionResource;
use App\Services\Backend\System\SystemSettingDefinitionService;
use Illuminate\Http\JsonResponse;

#[MenuPage('development.setting', ['en' => 'Setting definitions', 'zh_CN' => '设置定义', 'zh_TW' => '設定定義', 'th' => 'นิยามการตั้งค่า', 'ja' => '設定定義', 'ko' => '설정 정의'], '/admin/system/definition', 'development/setting/List', parent: 'development', icon: 'list-settings-line', sort: 1)]
class SystemSettingDefinitionController extends BaseController
{
    public function __construct(private readonly SystemSettingDefinitionService $service) {}

    #[MenuAction(['en' => 'List', 'zh_CN' => '列表', 'zh_TW' => '列表', 'th' => 'รายการ', 'ja' => '一覧', 'ko' => '목록'])]
    public function list(SystemSettingDefinitionRequest $request): JsonResponse
    {
        $page = $this->service->list($request->validated());

        return apiPaginate($page, SystemSettingDefinitionResource::collection($page));
    }

    #[MenuAction(['en' => 'Options', 'zh_CN' => '加载选项', 'zh_TW' => '載入選項', 'th' => 'ตัวเลือก', 'ja' => 'オプション', 'ko' => '옵션'], sort: 1)]
    public function options(): JsonResponse
    {
        return apiSuccess($this->service->options());
    }

    #[MenuAction(['en' => 'Load source options', 'zh_CN' => '加载来源选项', 'zh_TW' => '載入來源選項', 'th' => 'โหลดตัวเลือกจากแหล่งข้อมูล', 'ja' => 'ソースオプションを読み込む', 'ko' => '소스 옵션 불러오기'], sort: 2)]
    public function optionValues(SystemSettingDefinitionRequest $request): JsonResponse
    {
        return apiSuccess($this->service->optionValues($request->validated()));
    }

    #[MenuAction(['en' => 'Details', 'zh_CN' => '详情', 'zh_TW' => '詳情', 'th' => 'รายละเอียด', 'ja' => '詳細', 'ko' => '세부 정보'], sort: 2)]
    public function detail(int $id): JsonResponse
    {
        return apiSuccess(new SystemSettingDefinitionResource($this->service->detail($id)));
    }

    #[MenuAction(['en' => 'Create', 'zh_CN' => '新增', 'zh_TW' => '新增', 'th' => 'สร้าง', 'ja' => '作成', 'ko' => '생성'], sort: 3)]
    public function create(SystemSettingDefinitionRequest $request): JsonResponse
    {
        return apiSuccess(new SystemSettingDefinitionResource($this->service->create($request->validated())), status: 201);
    }

    #[MenuAction(['en' => 'Update', 'zh_CN' => '编辑', 'zh_TW' => '編輯', 'th' => 'แก้ไข', 'ja' => '編集', 'ko' => '편집'], sort: 4)]
    #[RequireSecurityPassword(mode: 'form')]
    public function update(SystemSettingDefinitionRequest $request, int $id): JsonResponse
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
