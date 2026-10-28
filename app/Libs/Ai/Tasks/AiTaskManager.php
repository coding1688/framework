<?php

namespace App\Libs\Ai\Tasks;

use App\Libs\Ai\Contracts\AiTaskHandler;
use App\Libs\Ai\Exceptions\AiException;
use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class AiTaskManager
{
    public function __construct(private readonly CacheManager $cacheManager) {}

    /** @param class-string<AiTaskHandler> $handlerClass
     * @param  array<string, mixed>  $payload
     * @return array{id: string, status: string}
     */
    public function submit(string $handlerClass, array $payload, string $scope, string $ownerId): array
    {
        return $this->enqueue($handlerClass, $payload, $scope, $ownerId, true);
    }

    /** @param class-string<AiTaskHandler> $handlerClass
     * @param  array<string, mixed>  $payload
     * @return array{id: string, status: string}
     */
    private function enqueue(string $handlerClass, array $payload, string $scope, string $ownerId, bool $throwOnFailure): array
    {
        if (! is_a($handlerClass, AiTaskHandler::class, true)) {
            throw new AiException('unsupported');
        }
        $id = (string) Str::uuid();
        $this->write($id, $scope, ['id' => $id, 'status' => 'queued', 'owner_id' => $ownerId, 'created_at' => now()->toIso8601String()]);
        try {
            AiTaskJob::dispatch($id, $handlerClass, $payload, $scope)
                ->onConnection(config('ai.tasks.connection', 'database'))->onQueue(config('ai.tasks.queue', 'ai'));
        } catch (Throwable $exception) {
            $this->fail($id, $scope, 'task_failed');
            if ($throwOnFailure) {
                throw new AiException('task_failed');
            }

            return ['id' => $id, 'status' => 'failed'];
        }

        return ['id' => $id, 'status' => 'queued'];
    }

    /** @return array<string, mixed> */
    public function get(string $id, string $scope, string $ownerId): array
    {
        $task = $this->cache()->get($this->key($id, $scope));
        if (! is_array($task) || ! hash_equals($task['owner_id'], $ownerId)) {
            throw new AiException('task_not_found');
        }
        unset($task['owner_id']);

        return $task;
    }

    public function consume(string $id, string $scope, string $ownerId, callable $callback): mixed
    {
        $lock = $this->cache()->lock($this->key($id, $scope).':consume', 120);
        if (! $lock->get()) {
            throw new AiException('task_busy');
        }
        try {
            $task = $this->get($id, $scope, $ownerId);
            if ($task['status'] === 'consumed') {
                throw new AiException('task_consumed');
            }
            if ($task['status'] !== 'completed') {
                throw new AiException('task_busy');
            }
            $result = $callback($task['result']);
            $this->write($id, $scope, [...$task, 'owner_id' => $ownerId, 'status' => 'consumed', 'consumed_at' => now()->toIso8601String()]);

            return $result;
        } finally {
            $lock->release();
        }
    }

    /** @param array<string, mixed> $payload */
    public function execute(string $id, string $handlerClass, array $payload, string $scope): void
    {
        $lock = $this->cache()->lock($this->key($id, $scope).':run', (int) config('ai.tasks.timeout', 240) + 30);
        if (! $lock->get()) {
            return;
        }
        try {
            $task = $this->cache()->get($this->key($id, $scope));
            if (! is_array($task) || $task['status'] !== 'queued') {
                return;
            }
            $this->write($id, $scope, [...$task, 'status' => 'running']);
            try {
                $result = app($handlerClass)->handle($payload);
                $this->write($id, $scope, [...$task, 'status' => 'completed', 'result' => $result, 'completed_at' => now()->toIso8601String()]);
            } catch (Throwable $exception) {
                $errorCode = $exception instanceof AiException ? $exception->errorCode : 'task_failed';
                $this->fail($id, $scope, $errorCode);
                Log::warning('AI task failed', ['task_id' => $id, 'handler' => $handlerClass, 'error_code' => $errorCode, 'exception_class' => $exception::class]);
            }
        } finally {
            $lock->release();
        }
    }

    public function fail(string $id, string $scope, string $errorCode): void
    {
        $task = $this->cache()->get($this->key($id, $scope));
        if (is_array($task) && in_array($task['status'], ['queued', 'running'], true)) {
            $this->write($id, $scope, [...$task, 'status' => 'failed', 'error_code' => $errorCode]);
        }
    }

    /** @param class-string<AiTaskHandler> $handlerClass
     * @param  list<array<string, mixed>>  $payloads
     * @return array{id: string, items: list<array{id: string, status: string}>}
     */
    public function submitBatch(string $handlerClass, array $payloads, string $scope, string $ownerId): array
    {
        if ($payloads === [] || count($payloads) > (int) config('ai.tasks.batch_limit', 20)) {
            throw new AiException('batch_limit');
        }
        $id = (string) Str::uuid();
        $items = [];
        $this->cache()->put($this->key($id, $scope).':batch', ['owner_id' => $ownerId, 'items' => []], $this->ttl());
        foreach ($payloads as $payload) {
            $items[] = $this->enqueue($handlerClass, $payload, $scope, $ownerId, false);
            $this->cache()->put($this->key($id, $scope).':batch', ['owner_id' => $ownerId, 'items' => array_column($items, 'id')], $this->ttl());
        }

        return ['id' => $id, 'items' => $items];
    }

    /** @return array<string, mixed> */
    public function getBatch(string $id, string $scope, string $ownerId): array
    {
        $batch = $this->cache()->get($this->key($id, $scope).':batch');
        if (! is_array($batch) || ! hash_equals($batch['owner_id'], $ownerId)) {
            throw new AiException('task_not_found');
        }
        $items = array_map(function (string $item) use ($scope, $ownerId): array {
            try {
                return $this->get($item, $scope, $ownerId);
            } catch (AiException $exception) {
                if ($exception->errorCode !== 'task_not_found') {
                    throw $exception;
                }

                return ['id' => $item, 'status' => 'expired', 'error_code' => 'task_expired'];
            }
        }, $batch['items']);

        return ['id' => $id, 'items' => $items, 'completed' => count(array_filter($items, fn (array $item): bool => in_array($item['status'], ['completed', 'consumed'], true))),
            'failed' => count(array_filter($items, fn (array $item): bool => in_array($item['status'], ['failed', 'expired'], true))), 'total' => count($items)];
    }

    /** @param array<string, mixed> $value */
    private function write(string $id, string $scope, array $value): void
    {
        $this->cache()->put($this->key($id, $scope), $value, $this->ttl());
    }

    private function cache(): Repository
    {
        return $this->cacheManager->store(config('ai.tasks.cache_store'));
    }

    private function ttl(): int
    {
        return (int) config('ai.tasks.ttl', 3600);
    }

    private function key(string $id, string $scope): string
    {
        if (! in_array($scope, ['backend', 'partner', 'api', 'global'], true) || ! Str::isUuid($id)) {
            throw new AiException('task_not_found');
        }

        return $scope.':ai:task:'.$id;
    }
}
