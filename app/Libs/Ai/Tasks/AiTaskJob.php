<?php

namespace App\Libs\Ai\Tasks;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class AiTaskJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout;

    public bool $failOnTimeout = true;

    /** @param array<string, mixed> $payload */
    public function __construct(public readonly string $taskId, public readonly string $handlerClass, public readonly array $payload, public readonly string $scope)
    {
        $this->timeout = (int) config('ai.tasks.timeout', 240);
    }

    public function handle(AiTaskManager $tasks): void
    {
        $tasks->execute($this->taskId, $this->handlerClass, $this->payload, $this->scope);
    }

    public function failed(?Throwable $exception): void
    {
        app(AiTaskManager::class)->fail($this->taskId, $this->scope, 'task_failed');
    }
}
