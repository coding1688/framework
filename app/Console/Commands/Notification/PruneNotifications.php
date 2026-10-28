<?php

namespace App\Console\Commands\Notification;

use App\Attributes\Schedulable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('notifications:prune')]
#[Description('Prune old read notifications')]
#[Schedulable(['en' => 'Prune read notifications', 'zh_CN' => '清理已读通知', 'zh_TW' => '清理已讀通知', 'th' => 'ล้างการแจ้งเตือนที่อ่านแล้ว', 'ja' => '既読通知の清理', 'ko' => '읽은 알림 정리'], cron: '30 0 * * *', sort: 10)]
class PruneNotifications extends Command
{
    public function handle(): int
    {
        DB::table('notifications')->whereNotNull('read_at')
            ->where('read_at', '<', now()->subDays((int) config('notifications.prune_after_days', 90)))->delete();

        return self::SUCCESS;
    }
}
