<?php

namespace App\Providers;

use App\Libs\Common\CacheGroupRegistry;
use App\Libs\File\Contracts\StorageConfigurationRepository;
use App\Libs\File\EloquentStorageConfigurationRepository;
use App\Libs\File\Providers\CloudflareStorageProvider;
use App\Libs\File\Providers\LocalStorageProvider;
use App\Libs\File\Providers\S3StorageProvider;
use App\Libs\File\StorageProviderRegistry;
use App\Libs\File\StorageRegistry;
use App\Models\Common\FileConfiguration;
use App\Models\Common\FileStorageAccount;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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
        $this->app->singleton(StorageConfigurationRepository::class, EloquentStorageConfigurationRepository::class);
        $this->app->singleton(StorageRegistry::class, fn (): StorageRegistry => new StorageRegistry(
            $this->app->make(StorageProviderRegistry::class),
            $this->app->make(StorageConfigurationRepository::class),
            Cache::store(config('files.cache_store') ?: config('cache.default')),
            (int) config('files.cache_ttl', 300),
        ));
        $caches = $this->app->make(CacheGroupRegistry::class);
        $caches->registerGroup('file', ['en' => 'File', 'zh_CN' => '文件模块', 'zh_TW' => '檔案模組', 'th' => 'โมดูลไฟล์', 'ja' => 'ファイルモジュール', 'ko' => '파일 모듈']);
        $caches->register('file.storage', 'file',
            ['en' => 'Storage configuration', 'zh_CN' => '存储配置', 'zh_TW' => '儲存設定', 'th' => 'การตั้งค่าที่จัดเก็บ', 'ja' => 'ストレージ設定', 'ko' => '스토리지 설정'],
            ['en' => 'Upload settings and non-sensitive storage provider summaries.', 'zh_CN' => '上传设置和非敏感的存储提供方汇总。', 'zh_TW' => '上傳設定和非敏感的儲存提供方彙總。', 'th' => 'การตั้งค่าการอัปโหลดและสรุปผู้ให้บริการที่ไม่มีข้อมูลลับ', 'ja' => 'アップロード設定と機密情報を含まないストレージプロバイダー概要。', 'ko' => '업로드 설정 및 민감하지 않은 스토리지 공급자 요약입니다.'],
            (string) (config('files.cache_store') ?: config('cache.default')), (int) config('files.cache_ttl', 300),
            fn (?string $key = null) => $this->app->make(StorageRegistry::class)->forget($key),
            fn (?string $search = null) => $this->app->make(StorageRegistry::class)->entries($search),
            fn (string $key) => $this->app->make(StorageRegistry::class)->rebuild($key),
        );
    }

    public function boot(): void
    {
        $invalidate = function (): void {
            DB::afterCommit(fn () => $this->app->make(StorageRegistry::class)->forget());
        };
        foreach ([FileConfiguration::class, FileStorageAccount::class] as $model) {
            $model::saved($invalidate);
            $model::deleted($invalidate);
        }
    }
}
