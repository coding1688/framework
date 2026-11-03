<?php

namespace App\Libs\Common;

use Illuminate\Contracts\Cache\Repository;
use InvalidArgumentException;

final class VersionedCache
{
    public function __construct(private readonly Repository $cache, private readonly string $prefix, private readonly int $ttl = 300)
    {
        if (trim($prefix) === '' || preg_match('/^[a-zA-Z][a-zA-Z0-9._-]{0,127}$/D', $prefix) !== 1) {
            throw new InvalidArgumentException('Invalid cache prefix.');
        }
        if ($ttl < 0) {
            throw new InvalidArgumentException('Cache TTL cannot be negative.');
        }
    }

    /** @param array<string, mixed> $metadata */
    public function remember(string $key, callable $resolver, array $metadata = []): mixed
    {
        $this->validateKey($key);
        if ($this->ttl === 0) {
            return $resolver();
        }

        $value = $this->cache->remember($this->versionedKey($key), $this->ttl, $resolver);
        $index = $this->index();
        $index[$key] = $metadata;
        $this->cache->forever($this->indexKey(), $index);

        return $value;
    }

    /** @return list<array{key: string, label: string, exists: bool}> */
    public function entries(?string $search = null): array
    {
        $entries = [];
        $search = trim((string) $search);
        foreach ($this->index() as $key => $metadata) {
            $label = is_string($metadata['label'] ?? null) && trim($metadata['label']) !== '' ? $metadata['label'] : $key;
            if ($search !== '' && mb_stripos($key.' '.$label, $search) === false) {
                continue;
            }
            $entries[] = ['key' => $key, 'label' => $label, 'exists' => $this->cache->has($this->versionedKey($key))];
        }
        usort($entries, fn (array $first, array $second): int => $first['key'] <=> $second['key']);

        return $entries;
    }

    /** @return array<string, mixed> */
    public function metadata(string $key): array
    {
        $this->validateKey($key);
        $metadata = $this->index()[$key] ?? null;
        if (! is_array($metadata)) {
            throw new InvalidArgumentException('Unknown cache key.');
        }

        return $metadata;
    }

    public function forget(?string $key = null): void
    {
        if ($key === null) {
            $versionKey = $this->prefix.':version';
            $this->cache->forever($versionKey, (int) $this->cache->get($versionKey, 0) + 1);
            $this->cache->forget($this->indexKey());

            return;
        }

        $this->metadata($key);
        $this->cache->forget($this->versionedKey($key));
        $index = $this->index();
        unset($index[$key]);
        $this->cache->forever($this->indexKey(), $index);
    }

    /** @return array<string, array<string, mixed>> */
    private function index(): array
    {
        $index = $this->cache->get($this->indexKey(), []);

        return is_array($index) ? $index : [];
    }

    private function indexKey(): string
    {
        return $this->prefix.':keys';
    }

    private function versionedKey(string $key): string
    {
        $version = (int) $this->cache->get($this->prefix.':version', 0);

        return $this->prefix.':'.$version.':'.$key;
    }

    private function validateKey(string $key): void
    {
        if (trim($key) === '' || mb_strlen($key) > 512 || preg_match('/[\x00-\x1f\x7f]/', $key) === 1) {
            throw new InvalidArgumentException('Invalid cache key.');
        }
    }
}
