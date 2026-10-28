<?php

namespace App\Jobs;

use App\Models\System\SystemScheduleRun;
use App\Services\System\ScheduleCommandRegistry;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Symfony\Component\Console\Output\BufferedOutput;
use Throwable;

class RunScheduledTask implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 3500;

    public function __construct(private readonly int $runId) {}

    public function handle(): void
    {
        $run = SystemScheduleRun::find($this->runId);
        if ($run === null) {
            return;
        }
        if (! is_string($run->command) || ! app(ScheduleCommandRegistry::class)->has($run->command)) {
            $run->update(['status' => 'failed', 'error' => 'Unregistered command', 'finished_at' => now()]);

            return;
        }
        $started = hrtime(true);
        $output = new BufferedOutput;
        $run->update(['status' => 'running', 'started_at' => now()]);
        try {
            $exitCode = Artisan::call($run->command, [], $output);
            $this->complete($run, $started, $output, $exitCode === 0 ? 'success' : 'failed', $exitCode);
        } catch (Throwable $exception) {
            $this->complete($run, $started, $output, 'failed', null, $exception->getMessage());
            report($exception);
        }
    }

    public function failed(?Throwable $exception): void
    {
        SystemScheduleRun::whereKey($this->runId)->whereIn('status', ['pending', 'running'])->update([
            'status' => 'failed', 'error' => Str::limit($exception?->getMessage() ?? 'Queue execution failed', 4000, ''), 'finished_at' => now(),
        ]);
    }

    private function complete(SystemScheduleRun $run, int $started, BufferedOutput $output, string $status, ?int $exitCode, ?string $error = null): void
    {
        $run->update([
            'status' => $status, 'exit_code' => $exitCode, 'output' => Str::limit($output->fetch(), 65535, ''),
            'error' => $error === null ? null : Str::limit($error, 4000, ''), 'finished_at' => now(),
            'duration_ms' => (int) ((hrtime(true) - $started) / 1_000_000),
        ]);
    }
}
