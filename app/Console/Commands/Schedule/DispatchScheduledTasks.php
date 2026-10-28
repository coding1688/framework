<?php

namespace App\Console\Commands\Schedule;

use App\Services\System\SystemScheduleDispatcher;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('system-schedule:dispatch')]
#[Description('Dispatch due scheduled tasks')]
class DispatchScheduledTasks extends Command
{
    public function handle(SystemScheduleDispatcher $dispatcher): int
    {
        $count = $dispatcher->dispatchDue();
        $this->info("Dispatched {$count} scheduled tasks.");

        return self::SUCCESS;
    }
}
