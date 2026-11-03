<?php

namespace App\Libs\File\Contracts;

use App\Libs\File\StorageAccount;

interface StorageConfigurationRepository
{
    /** @return array<string, mixed> */
    public function uploadSettings(): array;

    /** @return list<StorageAccount> */
    public function accounts(): array;

    public function find(string $disk): ?StorageAccount;
}
