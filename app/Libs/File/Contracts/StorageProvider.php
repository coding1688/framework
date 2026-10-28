<?php

namespace App\Libs\File\Contracts;

use App\Models\Common\FileStorageAccount;
use Illuminate\Filesystem\FilesystemAdapter;

interface StorageProvider
{
    /** @return array<string, mixed> */
    public function rules(): array;

    /** @return array<string, mixed> */
    public function configuration(FileStorageAccount $account): array;

    /** @param array<string, mixed> $configuration */
    public function configured(array $configuration): bool;

    public function isLocal(): bool;

    public function endpointRequired(): bool;

    public function customize(FilesystemAdapter $filesystem, FileStorageAccount $account): FilesystemAdapter;
}
