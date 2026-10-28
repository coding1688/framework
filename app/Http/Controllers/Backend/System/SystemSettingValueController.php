<?php

namespace App\Http\Controllers\Backend\System;

use App\Attributes\MenuAction;
use App\Attributes\MenuPage;
use App\Attributes\RequireSecurityPassword;
use App\Http\Controllers\Backend\BaseController;
use App\Http\Requests\Backend\System\SystemSettingValueRequest;
use App\Http\Resources\Backend\System\SystemSettingValueResource;
use App\Services\Backend\System\SystemSettingValueService;
use Illuminate\Http\JsonResponse;

#[MenuPage('system.setting', ['en' => 'System settings', 'zh_CN' => '系统设置', 'zh_TW' => '系統設定', 'th' => 'การตั้งค่าระบบ', 'ja' => 'システム設定', 'ko' => '시스템 설정'], '/admin/system/setting', 'system/setting/List', parent: 'system', icon: 'equalizer-line')]
class SystemSettingValueController extends BaseController
{
    public function __construct(private readonly SystemSettingValueService $service) {}

    #[MenuAction(['en' => 'List', 'zh_CN' => '列表', 'zh_TW' => '列表', 'th' => 'รายการ', 'ja' => '一覧', 'ko' => '목록'])]
    public function list(SystemSettingValueRequest $request): JsonResponse
    {
        return apiSuccess(['definitions' => SystemSettingValueResource::collection($this->service->list($request->validated()))]);
    }

    #[MenuAction(['en' => 'Options', 'zh_CN' => '加载选项', 'zh_TW' => '載入選項', 'th' => 'ตัวเลือก', 'ja' => 'オプション', 'ko' => '옵션'], sort: 1)]
    public function options(): JsonResponse
    {
        return apiSuccess($this->service->options());
    }

    #[MenuAction(['en' => 'Update settings', 'zh_CN' => '保存设置', 'zh_TW' => '儲存設定', 'th' => 'บันทึกการตั้งค่า', 'ja' => '設定を保存', 'ko' => '설정 저장'], sort: 2)]
    #[RequireSecurityPassword(mode: 'form')]
    public function update(SystemSettingValueRequest $request): JsonResponse
    {
        return apiSuccess($this->service->update($request->validated()));
    }

    #[MenuAction(['en' => 'Change status', 'zh_CN' => '启用/禁用', 'zh_TW' => '啟用/停用', 'th' => 'เปลี่ยนสถานะ', 'ja' => 'ステータスを変更', 'ko' => '상태 변경'], sort: 3)]
    #[RequireSecurityPassword(mode: 'dialog')]
    public function status(SystemSettingValueRequest $request, int $id): JsonResponse
    {
        return apiSuccess($this->service->status($request->validated(), $id));
    }
}
