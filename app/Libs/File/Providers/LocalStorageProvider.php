<?php

namespace App\Libs\File\Providers;

use App\Libs\File\Contracts\StorageProvider;
use App\Libs\File\StorageAccount;
use Illuminate\Filesystem\FilesystemAdapter;

final class LocalStorageProvider implements StorageProvider
{
    public function rules(): array
    {
        return [
            'configuration' => ['required', 'array:directory,url'],
            'configuration.directory' => ['nullable', 'string', 'max:128', 'regex:#^[a-zA-Z0-9_-]+(?:/[a-zA-Z0-9_-]+)*$#'],
            'configuration.url' => ['nullable', 'url:http,https', 'max:512'],
        ];
    }

    public function configuration(StorageAccount $account): array
    {
        if (in_array($account->disk, ['public', 'local'], true)) {
            $configuration = (array) config('filesystems.disks.'.$account->disk);
            if (! empty($account->configuration['url'])) {
                $configuration['url'] = $account->configuration['url'];
            }

            return $configuration;
        }
        $configuration = $account->configuration;

        return [
            'driver' => 'local', 'root' => storage_path('app/'.$configuration['directory']),
            'url' => $configuration['url'] ?? null, 'visibility' => $account->visibility,
            'throw' => true, 'report' => false,
        ];
    }

    public function configured(array $configuration): bool
    {
        return ($configuration['driver'] ?? null) === 'local' && ! empty($configuration['root']);
    }

    public function isLocal(): bool
    {
        return true;
    }

    public function endpointRequired(): bool
    {
        return false;
    }

    public function customize(FilesystemAdapter $filesystem, StorageAccount $account): FilesystemAdapter
    {
        return $filesystem;
    }
}
