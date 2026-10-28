<?php

namespace App\Http\Controllers\Backend\System;

use App\Attributes\MenuAction;
use App\Attributes\MenuPage;
use App\Http\Controllers\Backend\BaseController;
use App\Http\Requests\Backend\System\SystemScheduleRequest;
use App\Http\Resources\Backend\System\SystemScheduleRunResource;
use App\Http\Resources\Backend\System\SystemScheduleTaskResource;
use App\Services\Backend\System\SystemScheduleService;
use Illuminate\Http\JsonResponse;

#[MenuPage('system.schedule', ['en' => 'Scheduled tasks', 'zh_CN' => '计划任务', 'zh_TW' => '排程任務', 'th' => 'งานตามกำหนดเวลา', 'ja' => 'スケジュールタスク', 'ko' => '예약 작업'], '/admin/system/schedule', 'system/schedule/List', parent: 'development', icon: 'timer-line', sort: 40)]
class SystemScheduleController extends BaseController
{
    public function __construct(private readonly SystemScheduleService $service) {}

    #[MenuAction(['en' => 'List', 'zh_CN' => '列表', 'zh_TW' => '列表', 'th' => 'รายการ', 'ja' => '一覧', 'ko' => '목록'])]
    public function list(SystemScheduleRequest $request): JsonResponse
    {
        $page = $this->service->list($request->validated());

        return apiPaginate($page, SystemScheduleTaskResource::collection($page));
    }

    #[MenuAction(['en' => 'Options', 'zh_CN' => '加载选项', 'zh_TW' => '載入選項', 'th' => 'ตัวเลือก', 'ja' => 'オプション', 'ko' => '옵션'], sort: 1)]
    public function options(): JsonResponse
    {
        return apiSuccess($this->service->options());
    }

    #[MenuAction(['en' => 'Details', 'zh_CN' => '详情', 'zh_TW' => '詳情', 'th' => 'รายละเอียด', 'ja' => '詳細', 'ko' => '세부 정보'], sort: 2)]
    public function detail(int $id): JsonResponse
    {
        return apiSuccess(new SystemScheduleTaskResource($this->service->detail($id)));
    }

    #[MenuAction(['en' => 'Create', 'zh_CN' => '新增', 'zh_TW' => '新增', 'th' => 'สร้าง', 'ja' => '作成', 'ko' => '생성'], sort: 3)]
    public function create(SystemScheduleRequest $request): JsonResponse
    {
        return apiSuccess(new SystemScheduleTaskResource($this->service->create($request->validated())), status: 201);
    }

    #[MenuAction(['en' => 'Edit', 'zh_CN' => '编辑', 'zh_TW' => '編輯', 'th' => 'แก้ไข', 'ja' => '編集', 'ko' => '편집'], sort: 4)]
    public function update(SystemScheduleRequest $request, int $id): JsonResponse
    {
        return apiSuccess(new SystemScheduleTaskResource($this->service->update($request->validated(), $id)));
    }

    #[MenuAction(['en' => 'Change status', 'zh_CN' => '状态切换', 'zh_TW' => '狀態切換', 'th' => 'เปลี่ยนสถานะ', 'ja' => 'ステータス変更', 'ko' => '상태 변경'], sort: 5)]
    public function status(SystemScheduleRequest $request, int $id): JsonResponse
    {
        return apiSuccess(new SystemScheduleTaskResource($this->service->status($request->validated(), $id)));
    }

    #[MenuAction(['en' => 'Delete', 'zh_CN' => '删除', 'zh_TW' => '刪除', 'th' => 'ลบ', 'ja' => '削除', 'ko' => '삭제'], sort: 6)]
    public function delete(int $id): JsonResponse
    {
        $this->service->delete($id);

        return apiSuccess();
    }

    #[MenuAction(['en' => 'Run history', 'zh_CN' => '运行记录', 'zh_TW' => '執行記錄', 'th' => 'ประวัติการทำงาน', 'ja' => '実行履歴', 'ko' => '실행 기록'], sort: 7)]
    public function runs(SystemScheduleRequest $request): JsonResponse
    {
        $page = $this->service->runs($request->validated());

        return apiPaginate($page, SystemScheduleRunResource::collection($page));
    }

    #[MenuAction(['en' => 'View log', 'zh_CN' => '查看日志', 'zh_TW' => '查看日誌', 'th' => 'ดูบันทึก', 'ja' => 'ログを表示', 'ko' => '로그 보기'], sort: 8)]
    public function runDetail(int $id): JsonResponse
    {
        return apiSuccess(new SystemScheduleRunResource($this->service->runDetail($id)));
    }

    #[MenuAction(['en' => 'Run now', 'zh_CN' => '立即执行', 'zh_TW' => '立即執行', 'th' => 'ทำงานทันที', 'ja' => '今すぐ実行', 'ko' => '지금 실행'], sort: 9)]
    public function run(int $id): JsonResponse
    {
        return apiSuccess(new SystemScheduleRunResource($this->service->run($id)), status: 202);
    }

    #[MenuAction(['en' => 'View settings', 'zh_CN' => '查看设置', 'zh_TW' => '查看設定', 'th' => 'ดูการตั้งค่า', 'ja' => '設定を表示', 'ko' => '설정 보기'], sort: 10)]
    public function settings(): JsonResponse
    {
        return apiSuccess($this->service->settings());
    }

    #[MenuAction(['en' => 'Save settings', 'zh_CN' => '保存设置', 'zh_TW' => '儲存設定', 'th' => 'บันทึกการตั้งค่า', 'ja' => '設定を保存', 'ko' => '설정 저장'], sort: 11)]
    public function updateSettings(SystemScheduleRequest $request): JsonResponse
    {
        return apiSuccess($this->service->updateSettings($request->validated()));
    }
}
