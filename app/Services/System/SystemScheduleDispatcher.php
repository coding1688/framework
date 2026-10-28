<?php

namespace App\Services\System;

use App\Jobs\RunScheduledTask;
use App\Libs\Settings\SettingStore;
use App\Models\System\SystemScheduleRun;
use App\Models\System\SystemScheduleTask;
use Cron\CronExpression;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SystemScheduleDispatcher
{
    public function __construct(private readonly SettingStore $settings) {}

    public function dispatchDue(): int
    {
        if (! $this->settings->get('schedule.enabled', config('schedule.defaults.enabled'))) {
            return 0;
        }
        $dispatched = 0;
        SystemScheduleTask::where('active', true)->orderBy('sort')->orderBy('id')->get()->each(function (SystemScheduleTask $task) use (&$dispatched): void {
            if ((new CronExpression($task->cron))->isDue(now($task->timezone)) && $this->dispatch($task, 'schedule') !== null) {
                $dispatched++;
            }
        });

        return $dispatched;
    }

    public function dispatch(SystemScheduleTask $task, string $trigger, ?int $administratorId = null): ?SystemScheduleRun
    {
        $run = Cache::lock('system.schedule.task.'.$task->id, 10)->block(2, function () use ($task, $trigger, $administratorId): ?SystemScheduleRun {
            return DB::transaction(function () use ($task, $trigger, $administratorId): ?SystemScheduleRun {
                $locked = SystemScheduleTask::lockForUpdate()->find($task->id);
                if ($locked === null || $locked->without_overlapping && SystemScheduleRun::where('system_schedule_task_id', $task->id)->whereIn('status', ['pending', 'running'])->exists()) {
                    return null;
                }

                return SystemScheduleRun::create([
                    'system_schedule_task_id' => $locked->id, 'task_key' => $locked->key, 'command' => $locked->command,
                    'trigger' => $trigger, 'administrator_id' => $administratorId, 'status' => 'pending',
                ]);
            });
        });
        if ($run !== null) {
            RunScheduledTask::dispatch($run->id)->onConnection('maintenance');
        }

        return $run;
    }
}
