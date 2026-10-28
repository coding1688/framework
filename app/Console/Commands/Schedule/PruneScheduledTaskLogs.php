<?php

namespace App\Console\Commands\Schedule;

use App\Attributes\Schedulable;
use App\Libs\Settings\SettingStore;
use App\Models\System\SystemScheduleRun;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('system-schedule:prune-logs')]
#[Description('Prune expired scheduled task logs')]
#[Schedulable(['en' => 'Prune scheduled task logs', 'zh_CN' => '清理计划任务日志', 'zh_TW' => '清理排程任務日誌', 'th' => 'ล้างบันทึกงานตามกำหนดเวลา', 'ja' => 'スケジュールタスクログの清理', 'ko' => '예약 작업 로그 정리'], cron: '0 2 * * *', sort: 20)]
class PruneScheduledTaskLogs extends Command
{
    public function handle(SettingStore $settings): int
    {
        $days = (int) $settings->get('schedule.log_retention_days', config('schedule.defaults.log_retention_days'));
        $count = SystemScheduleRun::whereIn('status', ['success', 'failed'])->where('created_at', '<', now()->subDays($days))->delete();
        $this->info("Pruned {$count} scheduled task logs.");

        return self::SUCCESS;
    }
}
