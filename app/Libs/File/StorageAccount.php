<?php

namespace App\Libs\File;

final readonly class StorageAccount
{
    /**
     * @param  array<string, mixed>  $configuration
     * @param  array<string, mixed>  $credentials
     */
    public function __construct(
        public string $disk,
        public string $provider,
        public string $name,
        public string $visibility,
        public bool $enabled,
        public array $configuration = [],
        public array $credentials = [],
    ) {}
}
