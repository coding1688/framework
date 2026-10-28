<?php

namespace App\Libs\Ai\Tasks;

use Illuminate\Support\Facades\DB;

final class DatabaseTaskReceipt
{
    public function exists(string $taskId, string $scope, string $ownerId): bool
    {
        return DB::table('ai_task_receipts')->where('task_id', $taskId)->where('scope', $scope)->where('owner_id', $ownerId)->exists();
    }

    /** Record inside the same transaction as the business resource creation. */
    public function record(string $taskId, string $scope, string $ownerId, string $resourceType, string $resourceId): void
    {
        DB::table('ai_task_receipts')->insert([
            'task_id' => $taskId,
            'scope' => $scope,
            'owner_id' => $ownerId,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'created_at' => now(),
        ]);
    }

    /** Receipts only guard tasks that can still be consumed, so drop those older than the task lifetime (at least one day). */
    public function prune(): int
    {
        $lifetime = max((int) config('ai.tasks.ttl', 3600), 86400);

        return DB::table('ai_task_receipts')->where('created_at', '<', now()->subSeconds($lifetime))->delete();
    }
}
