<?php

namespace App\Providers;

use App\Libs\File\Providers\CloudflareStorageProvider;
use App\Libs\File\Providers\LocalStorageProvider;
use App\Libs\File\Providers\S3StorageProvider;
use App\Libs\File\StorageProviderRegistry;
use Illuminate\Support\ServiceProvider;

class FileServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(StorageProviderRegistry::class, function (): StorageProviderRegistry {
            $providers = new StorageProviderRegistry;
            $providers->register('local', new LocalStorageProvider);
            $providers->register('local_private', new LocalStorageProvider, false);
            $providers->register('aws', new S3StorageProvider(false));
            $providers->register('cloudflare', new CloudflareStorageProvider);
            $providers->register('aliyun', new S3StorageProvider);
            $providers->register('google', new S3StorageProvider);
            $providers->register('s3_compatible', new S3StorageProvider);

            return $providers;
        });
    }
}
