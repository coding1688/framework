<?php

namespace App\Services\Backend\System;

use App\Services\Backend\BaseService;
use App\Services\Backend\Common\Audit;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SystemQueueService extends BaseService
{
    public function overview(): array
    {
        $this->guard('overview');
        $jobs = DB::table('jobs')->selectRaw('queue, count(*) as total, sum(reserved_at is not null) as reserved')->groupBy('queue')->get()->keyBy('queue');
        $failed = DB::table('failed_jobs')->select('queue', DB::raw('count(*) as total'))->groupBy('queue')->get()->keyBy('queue');
        $queues = array_unique([...$jobs->keys()->all(), ...$failed->keys()->all()]);

        return collect($queues)->map(fn (string $queue): array => [
            'queue' => $queue, 'pending' => (int) (($jobs[$queue]->total ?? 0) - ($jobs[$queue]->reserved ?? 0)),
            'reserved' => (int) ($jobs[$queue]->reserved ?? 0), 'failed' => (int) ($failed[$queue]->total ?? 0),
        ])->values()->all();
    }

    public function jobs(array $validated): LengthAwarePaginator
    {
        $this->guard('jobs');
        $query = DB::table('jobs')->orderByDesc('id');
        if ($queue = $validated['queue'] ?? null) {
            $query->where('queue', $queue);
        }

        return $query->paginate(20, ['*'], 'page', (int) ($validated['page'] ?? 1))->through(fn (object $job): array => [
            'id' => $job->id, 'queue' => $job->queue, 'name' => $this->jobName($job->payload),
            'attempts' => $job->attempts, 'reserved' => $job->reserved_at !== null,
            'available_at' => $this->timestamp($job->available_at), 'created_at' => $this->timestamp($job->created_at),
        ]);
    }

    public function failed(array $validated): LengthAwarePaginator
    {
        $this->guard('failed');
        $query = DB::table('failed_jobs')->orderByDesc('failed_at');
        if ($queue = $validated['queue'] ?? null) {
            $query->where('queue', $queue);
        }

        return $query->paginate(20, ['*'], 'page', (int) ($validated['page'] ?? 1))->through(fn (object $job): array => [
            'id' => $job->id, 'uuid' => $job->uuid, 'connection' => $job->connection, 'queue' => $job->queue,
            'name' => $this->jobName($job->payload), 'exception' => Str::limit(strtok($job->exception, "\n") ?: '', 512),
            'failed_at' => $job->failed_at,
        ]);
    }

    public function detail(int $id): array
    {
        $this->guard('detail');
        $job = DB::table('failed_jobs')->where('id', $id)->first();
        abort_unless($job !== null, 404);

        return [
            'id' => $job->id, 'uuid' => $job->uuid, 'connection' => $job->connection, 'queue' => $job->queue,
            'name' => $this->jobName($job->payload), 'exception' => Str::limit($job->exception, 12000, ''),
            'failed_at' => $job->failed_at,
        ];
    }

    public function retry(string $uuid): void
    {
        $this->guard('retry');
        abort_unless(DB::table('failed_jobs')->where('uuid', $uuid)->exists(), 404);
        Artisan::call('queue:retry', ['id' => [$uuid]]);
        Audit::record('system.queue.retry', $uuid);
    }

    public function retryBatch(array $validated): void
    {
        foreach ($validated['ids'] as $uuid) {
            $this->retry($uuid);
        }
    }

    public function delete(string $uuid): void
    {
        $this->guard('delete');
        abort_unless(app('queue.failer')->forget($uuid), 404);
        Audit::record('system.queue.delete', $uuid);
    }

    private function jobName(string $payload): string
    {
        $decoded = json_decode($payload, true);

        return is_array($decoded) && is_string($decoded['displayName'] ?? null) ? Str::limit($decoded['displayName'], 256) : 'Unknown';
    }

    private function timestamp(int $timestamp): ?string
    {
        return $timestamp > 0 ? now()->setTimestamp($timestamp)->toIso8601String() : null;
    }

    private function guard(string $action): void
    {
        $this->authorize('system.queue.'.$action, true);
    }
}
