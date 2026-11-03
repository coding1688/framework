<?php

namespace App\Services\Backend\System;

use App\Libs\Common\CacheGroupRegistry;
use App\Services\Backend\BaseService;
use App\Services\Backend\Common\Audit;
use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;
use Illuminate\Pagination\LengthAwarePaginator;

class SystemCacheService extends BaseService
{
    public function __construct(private readonly CacheGroupRegistry $caches) {}

    /** @return list<array<string, mixed>> */
    public function list(array $validated = []): array
    {
        $this->guard('list');

        return $this->caches->list();
    }

    public function keys(array $validated): LengthAwarePaginatorContract
    {
        $this->guard('keys');
        $entries = $this->caches->search($validated['key'], $validated['search'] ?? null);
        $page = (int) ($validated['page'] ?? 1);

        return new LengthAwarePaginator(array_slice($entries, ($page - 1) * 20, 20), count($entries), 20, $page);
    }

    public function clear(array $validated): void
    {
        $this->guard('clear');
        $this->caches->forget($validated['key']);
        Audit::record('system.cache.clear', $validated['key']);
    }

    public function refresh(array $validated): void
    {
        $this->guard('refresh');
        $this->caches->refresh($validated['key'], $validated['cache_key']);
        Audit::record('system.cache.refresh', $validated['key'], ['cache_key' => $validated['cache_key']]);
    }

    public function rebuild(array $validated): void
    {
        $this->guard('rebuild');
        $this->caches->rebuild($validated['key'], $validated['cache_key']);
        Audit::record('system.cache.rebuild', $validated['key'], ['cache_key' => $validated['cache_key']]);
    }

    private function guard(string $action): void
    {
        $this->authorize('system.cache.'.$action, true);
    }
}
