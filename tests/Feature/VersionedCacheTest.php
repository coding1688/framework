<?php

namespace Tests\Feature;

use App\Libs\Common\VersionedCache;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class VersionedCacheTest extends TestCase
{
    public function test_modules_share_versioned_caching_without_sharing_prefix_invalidation(): void
    {
        $repository = Cache::store('array');
        $repository->flush();
        $first = new VersionedCache($repository, 'first-module');
        $second = new VersionedCache($repository, 'second-module');
        $firstCalls = 0;
        $secondCalls = 0;
        $firstValue = function () use (&$firstCalls): array {
            return ['calls' => ++$firstCalls];
        };
        $secondValue = function () use (&$secondCalls): string {
            return 'calls-'.++$secondCalls;
        };

        $this->assertSame(['calls' => 1], $first->remember('shared-key', $firstValue));
        $this->assertSame(['calls' => 1], $first->remember('shared-key', $firstValue));
        $this->assertSame('calls-1', $second->remember('shared-key', $secondValue));
        $this->assertSame('calls-1', $second->remember('shared-key', $secondValue));

        $first->forget();
        $this->assertSame(['calls' => 2], $first->remember('shared-key', $firstValue));
        $this->assertSame('calls-1', $second->remember('shared-key', $secondValue));
    }

    public function test_key_index_searches_metadata_and_invalidates_only_one_key(): void
    {
        $repository = Cache::store('array');
        $repository->flush();
        $cache = new VersionedCache($repository, 'managed-module');
        $calls = ['alpha' => 0, 'beta' => 0];
        $resolver = function (string $key) use (&$calls): int {
            return ++$calls[$key];
        };

        $this->assertSame(1, $cache->remember('alpha', fn (): int => $resolver('alpha'), ['label' => 'First cached item', 'context' => 'en']));
        $this->assertSame(1, $cache->remember('beta', fn (): int => $resolver('beta'), ['label' => 'Second cached item']));
        $this->assertSame(['alpha'], array_column($cache->entries('First'), 'key'));
        $this->assertSame('en', $cache->metadata('alpha')['context']);
        $this->assertTrue($cache->entries('alpha')[0]['exists']);

        $cache->forget('alpha');
        $this->assertSame(['beta'], array_column($cache->entries(), 'key'));
        $this->assertSame(2, $cache->remember('alpha', fn (): int => $resolver('alpha'), ['label' => 'First cached item']));
        $this->assertSame(1, $cache->remember('beta', fn (): int => $resolver('beta')));

        $cache->forget();
        $this->assertSame([], $cache->entries());
        $this->assertSame(3, $cache->remember('alpha', fn (): int => $resolver('alpha')));
        $this->assertSame(2, $cache->remember('beta', fn (): int => $resolver('beta')));
    }

    public function test_zero_ttl_bypasses_cache(): void
    {
        $cache = new VersionedCache(Cache::store('array'), 'uncached-module', 0);
        $calls = 0;
        $resolver = function () use (&$calls): int {
            return ++$calls;
        };

        $this->assertSame(1, $cache->remember('value', $resolver));
        $this->assertSame(2, $cache->remember('value', $resolver));
    }
}
