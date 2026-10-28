<?php

namespace App\Http\Controllers\Backend\System;

use App\Attributes\MenuAction;
use App\Attributes\MenuPage;
use App\Http\Controllers\Backend\BaseController;
use App\Http\Requests\Backend\System\SystemQueueRequest;
use App\Services\Backend\System\SystemQueueService;
use Illuminate\Http\JsonResponse;

#[MenuPage('system.queue', ['en' => 'Queue operations', 'zh_CN' => '队列运维', 'zh_TW' => '佇列維運', 'th' => 'การจัดการคิว', 'ja' => 'キュー運用', 'ko' => '큐 운영'], '/admin/system/queue', 'system/queue/List', parent: 'development', icon: 'stack-line', sort: 30)]
class SystemQueueController extends BaseController
{
    public function __construct(private readonly SystemQueueService $service) {}

    #[MenuAction(['en' => 'Overview', 'zh_CN' => '队列概览', 'zh_TW' => '佇列概覽', 'th' => 'ภาพรวมคิว', 'ja' => 'キュー概要', 'ko' => '큐 개요'])]
    public function overview(): JsonResponse
    {
        return apiSuccess($this->service->overview());
    }

    #[MenuAction(['en' => 'Pending jobs', 'zh_CN' => '待处理任务', 'zh_TW' => '待處理任務', 'th' => 'งานรอดำเนินการ', 'ja' => '保留中のジョブ', 'ko' => '대기 작업'], sort: 1)]
    public function jobs(SystemQueueRequest $request): JsonResponse
    {
        return apiPaginate($this->service->jobs($request->validated()));
    }

    #[MenuAction(['en' => 'Failed jobs', 'zh_CN' => '失败任务', 'zh_TW' => '失敗任務', 'th' => 'งานที่ล้มเหลว', 'ja' => '失敗したジョブ', 'ko' => '실패 작업'], sort: 2)]
    public function failed(SystemQueueRequest $request): JsonResponse
    {
        return apiPaginate($this->service->failed($request->validated()));
    }

    #[MenuAction(['en' => 'Failure details', 'zh_CN' => '失败详情', 'zh_TW' => '失敗詳情', 'th' => 'รายละเอียดความล้มเหลว', 'ja' => '失敗詳細', 'ko' => '실패 상세'], sort: 3)]
    public function detail(int $id): JsonResponse
    {
        return apiSuccess($this->service->detail($id));
    }

    #[MenuAction(['en' => 'Retry', 'zh_CN' => '重试', 'zh_TW' => '重試', 'th' => 'ลองอีกครั้ง', 'ja' => '再試行', 'ko' => '재시도'], sort: 4)]
    public function retry(string $uuid): JsonResponse
    {
        $this->service->retry($uuid);

        return apiSuccess();
    }

    #[MenuAction(['en' => 'Batch retry', 'zh_CN' => '批量重试', 'zh_TW' => '批次重試', 'th' => 'ลองอีกครั้งเป็นชุด', 'ja' => '一括再試行', 'ko' => '일괄 재시도'], sort: 5)]
    public function retryBatch(SystemQueueRequest $request): JsonResponse
    {
        $this->service->retryBatch($request->validated());

        return apiSuccess();
    }

    #[MenuAction(['en' => 'Delete failure', 'zh_CN' => '删除失败记录', 'zh_TW' => '刪除失敗記錄', 'th' => 'ลบรายการที่ล้มเหลว', 'ja' => '失敗レコード削除', 'ko' => '실패 기록 삭제'], sort: 6)]
    public function delete(string $uuid): JsonResponse
    {
        $this->service->delete($uuid);

        return apiSuccess();
    }
}
