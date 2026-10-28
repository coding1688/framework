<?php

namespace App\Console\Commands\Ai;

use App\Attributes\Schedulable;
use App\Libs\Ai\Tasks\DatabaseTaskReceipt;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('ai-task-receipts:prune')]
#[Description('Prune expired AI task receipts')]
#[Schedulable(['en' => 'Prune AI task receipts', 'zh_CN' => '清理 AI 任务回执', 'zh_TW' => '清理 AI 任務回執', 'th' => 'ล้างใบเสร็จงาน AI', 'ja' => 'AI タスク受領の清理', 'ko' => 'AI 작업 접수 정리'], cron: '0 0 * * *')]
class PruneAiTaskReceipts extends Command
{
    public function handle(DatabaseTaskReceipt $receipts): int
    {
        $receipts->prune();

        return self::SUCCESS;
    }
}
