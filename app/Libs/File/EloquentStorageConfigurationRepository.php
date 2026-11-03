<?php

namespace App\Libs\File;

use App\Libs\File\Contracts\StorageConfigurationRepository;
use App\Models\Common\FileConfiguration;
use App\Models\Common\FileStorageAccount;
use Illuminate\Support\Facades\Schema;

final class EloquentStorageConfigurationRepository implements StorageConfigurationRepository
{
    public function uploadSettings(): array
    {
        if (! Schema::hasTable('file_configurations')) {
            return [];
        }

        return FileConfiguration::find(1)?->only(['default_disk', 'max_upload_mb', 'allowed_extensions']) ?? [];
    }

    public function accounts(): array
    {
        if (! Schema::hasTable('file_storages')) {
            return [];
        }

        return FileStorageAccount::all()->map(fn (FileStorageAccount $account): StorageAccount => $this->account($account))->values()->all();
    }

    public function find(string $disk): ?StorageAccount
    {
        if (! Schema::hasTable('file_storages')) {
            return null;
        }
        $account = FileStorageAccount::where('disk', $disk)->first();

        return $account === null ? null : $this->account($account);
    }

    private function account(FileStorageAccount $account): StorageAccount
    {
        return new StorageAccount(
            disk: $account->disk,
            provider: $account->provider,
            name: $account->name,
            visibility: $account->visibility,
            enabled: $account->enabled,
            configuration: $account->configuration ?? [],
            credentials: $account->credentials ?? [],
        );
    }
}
