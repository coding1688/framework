<?php

namespace App\Libs\File;

use App\Libs\Common\VersionedCache;
use App\Libs\File\Contracts\StorageConfigurationRepository;
use App\Libs\File\Contracts\StorageProvider;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

class StorageRegistry
{
    /** @var array<string, FilesystemAdapter> */
    private array $disks = [];

    private ?VersionedCache $versionedCache;

    public function __construct(
        private ?StorageProviderRegistry $storageProviders = null,
        private ?StorageConfigurationRepository $configurationRepository = null,
        private readonly ?Repository $cache = null,
        private readonly int $cacheTtl = 300,
        private readonly string $cachePrefix = 'file-storage',
    ) {
        $this->versionedCache = $cache === null ? null : new VersionedCache($cache, $cachePrefix, $cacheTtl);
    }

    /** @return array<string, mixed> */
    public function uploadSettings(): array
    {
        return $this->remember('upload-settings', fn (): array => $this->configurations()->uploadSettings());
    }

    /** @return array<string, array<string, mixed>> */
    public function providers(): array
    {
        return $this->remember('providers', function (): array {
            $defaults = (array) (config('files.uploads') ?? require __DIR__.'/config.php');
            $accounts = collect($this->configurations()->accounts())->keyBy('disk');
            $providers = [];
            foreach (array_unique([...array_keys($defaults['providers']), ...$accounts->keys()->all()]) as $disk) {
                $account = $accounts->get($disk);
                $provider = $account?->provider ?? $defaults['providers'][$disk]['provider'];
                $configuration = $account ? $this->configuration($account) : (array) config('filesystems.disks.'.$disk, []);
                $visibility = $account?->visibility ?? ($configuration['visibility'] ?? $defaults['providers'][$disk]['visibility']);
                $configured = $this->storageProviders()->get($provider)->configured($configuration);
                $selected = $account?->enabled ?? in_array($disk, $defaults['enabled_disks'], true);
                $providers[$disk] = [
                    'provider' => $provider, 'name' => $account?->name ?? $disk,
                    'configured' => $configured, 'selected' => $selected, 'enabled' => $configured && $selected,
                    'visibilities' => [$visibility],
                ];
            }

            return $providers;
        });
    }

    public function disk(string $disk): FilesystemAdapter
    {
        if (isset($this->disks[$disk])) {
            return $this->disks[$disk];
        }
        $account = $this->configurations()->find($disk);
        if (! $account || (in_array($disk, ['public', 'local'], true) && empty($account->configuration['url']))) {
            return $this->disks[$disk] = Storage::disk($disk);
        }
        if (in_array($disk, ['public', 'local'], true)) {
            return $this->disks[$disk] = Storage::build(array_replace((array) config('filesystems.disks.'.$disk), Storage::disk($disk)->getConfig(), ['url' => $account->configuration['url']]));
        }
        $provider = $this->storageProviders()->get($account->provider);

        return $this->disks[$disk] = $provider->customize(Storage::build($provider->configuration($account)), $account);
    }

    /** @return list<array{key: string, label: string, exists: bool}> */
    public function entries(?string $search = null): array
    {
        return $this->versionedCache?->entries($search) ?? [];
    }

    public function forget(?string $key = null): void
    {
        if ($key === null) {
            $this->disks = [];
        }
        $this->versionedCache?->forget($key);
    }

    public function rebuild(string $key): void
    {
        if (! in_array($key, ['upload-settings', 'providers'], true)) {
            throw new \InvalidArgumentException('Unknown cache key.');
        }
        $this->forget($key);
        match ($key) {
            'upload-settings' => $this->uploadSettings(),
            'providers' => $this->providers(),
        };
    }

    /** @return array<string, mixed> */
    public function configuration(StorageAccount $account): array
    {
        return $this->storageProviders()->get($account->provider)->configuration($account);
    }

    public function hasProvider(string $key): bool
    {
        return $this->storageProviders()->has($key);
    }

    public function provider(string $key): StorageProvider
    {
        return $this->storageProviders()->get($key);
    }

    /** @return list<string> */
    public function providerKeys(): array
    {
        return $this->storageProviders()->keys();
    }

    /** @return list<string> */
    public function providerOptions(): array
    {
        return $this->storageProviders()->selectable();
    }

    /** @return list<string> */
    public function localProviderKeys(): array
    {
        return $this->storageProviders()->localKeys();
    }

    public function isLocal(string $key): bool
    {
        return $this->storageProviders()->get($key)->isLocal();
    }

    public function endpointRequired(string $key): bool
    {
        return $this->storageProviders()->get($key)->endpointRequired();
    }

    private function storageProviders(): StorageProviderRegistry
    {
        return $this->storageProviders ??= app(StorageProviderRegistry::class);
    }

    private function configurations(): StorageConfigurationRepository
    {
        return $this->configurationRepository ??= app(StorageConfigurationRepository::class);
    }

    private function remember(string $key, callable $resolver): array
    {
        if ($this->versionedCache === null) {
            return $resolver();
        }

        return $this->versionedCache->remember($key, $resolver);
    }
}
