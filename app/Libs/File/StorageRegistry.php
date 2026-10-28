<?php

namespace App\Libs\File;

use App\Libs\File\Contracts\StorageProvider;
use App\Models\Common\FileConfiguration;
use App\Models\Common\FileStorageAccount;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class StorageRegistry
{
    private ?StorageProviderRegistry $storageProviders = null;

    public function __construct(?StorageProviderRegistry $storageProviders = null)
    {
        $this->storageProviders = $storageProviders;
    }

    private function storageProviders(): StorageProviderRegistry
    {
        return $this->storageProviders ??= app(StorageProviderRegistry::class);
    }

    /** @return array<string, mixed> */
    public function uploadSettings(): array
    {
        if (! Schema::hasTable('file_configurations')) {
            return [];
        }

        return FileConfiguration::find(1)?->only(['default_disk', 'max_upload_mb', 'allowed_extensions']) ?? [];
    }

    /** @return array<string, array<string, mixed>> */
    public function providers(): array
    {
        $defaults = (array) (config('files.uploads') ?? require __DIR__.'/config.php');
        $accounts = Schema::hasTable('file_storages') ? FileStorageAccount::all()->keyBy('disk') : collect();
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
    }

    public function disk(string $disk): FilesystemAdapter
    {
        $account = Schema::hasTable('file_storages') ? FileStorageAccount::where('disk', $disk)->first() : null;
        if (! $account || (in_array($disk, ['public', 'local'], true) && empty($account->configuration['url']))) {
            return Storage::disk($disk);
        }
        if (in_array($disk, ['public', 'local'], true)) {
            return Storage::build(array_replace((array) config('filesystems.disks.'.$disk), Storage::disk($disk)->getConfig(), ['url' => $account->configuration['url']]));
        }
        $provider = $this->storageProviders()->get($account->provider);

        return $provider->customize(Storage::build($provider->configuration($account)), $account);
    }

    /** @return array<string, mixed> */
    public function configuration(FileStorageAccount $account): array
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
}
