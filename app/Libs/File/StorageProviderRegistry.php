<?php

namespace App\Libs\File;

use App\Libs\File\Contracts\StorageProvider;
use InvalidArgumentException;

final class StorageProviderRegistry
{
    /** @var array<string, StorageProvider> */
    private array $providers = [];

    /** @var list<string> */
    private array $selectable = [];

    public function register(string $key, StorageProvider $provider, bool $selectable = true): void
    {
        $this->providers[$key] = $provider;
        if ($selectable && ! in_array($key, $this->selectable, true)) {
            $this->selectable[] = $key;
        }
    }

    public function has(string $key): bool
    {
        return isset($this->providers[$key]);
    }

    public function get(string $key): StorageProvider
    {
        return $this->providers[$key] ?? throw new InvalidArgumentException('Unknown storage provider: '.$key);
    }

    /** @return list<string> */
    public function keys(): array
    {
        return array_keys($this->providers);
    }

    /** @return list<string> */
    public function selectable(): array
    {
        return $this->selectable;
    }

    /** @return list<string> */
    public function localKeys(): array
    {
        return array_keys(array_filter($this->providers, fn (StorageProvider $provider): bool => $provider->isLocal()));
    }
}
