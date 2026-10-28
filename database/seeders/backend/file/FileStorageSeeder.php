<?php

namespace Database\Seeders\backend\file;

use App\Libs\File\UploadConfiguration;
use App\Libs\Settings\SettingStore;
use App\Models\Common\FileConfiguration;
use App\Models\Common\FileStorageAccount;
use App\Models\System\SystemSettingDefinition;
use App\Models\System\SystemSettingGroup;
use Illuminate\Database\Seeder;

class FileStorageSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = app(UploadConfiguration::class)->defaults();
        $legacy = app(SettingStore::class);
        FileConfiguration::firstOrCreate(['id' => 1], [
            'default_disk' => $legacy->get('file.default_disk', $defaults['default_disk']),
            'max_upload_mb' => $legacy->get('file.max_upload_mb', $defaults['max_upload_mb']),
            'allowed_extensions' => $legacy->get('file.allowed_extensions', $defaults['allowed_extensions']),
        ]);
        $enabled = $legacy->get('file.enabled_disks', $defaults['enabled_disks']);
        $names = [
            'public' => ['en' => 'Local public', 'zh_CN' => '本地公开', 'zh_TW' => '本地公開', 'th' => 'ที่จัดเก็บสาธารณะในเครื่อง', 'ja' => 'ローカル公開', 'ko' => '로컬 공개'],
            'local' => ['en' => 'Local private', 'zh_CN' => '本地私有', 'zh_TW' => '本地私有', 'th' => 'ที่จัดเก็บส่วนตัวในเครื่อง', 'ja' => 'ローカル非公開', 'ko' => '로컬 비공개'],
            's3' => ['en' => 'Amazon S3', 'zh_CN' => 'Amazon S3', 'zh_TW' => 'Amazon S3', 'th' => 'Amazon S3', 'ja' => 'Amazon S3', 'ko' => 'Amazon S3'],
            'r2' => ['en' => 'Cloudflare R2', 'zh_CN' => 'Cloudflare R2', 'zh_TW' => 'Cloudflare R2', 'th' => 'Cloudflare R2', 'ja' => 'Cloudflare R2', 'ko' => 'Cloudflare R2'],
            'oss' => ['en' => 'Alibaba OSS (S3 API)', 'zh_CN' => '阿里云 OSS（S3 接口）', 'zh_TW' => '阿里雲 OSS（S3 接口）', 'th' => 'Alibaba OSS (S3 API)', 'ja' => 'Alibaba OSS (S3 API)', 'ko' => 'Alibaba OSS (S3 API)'],
            'gcs' => ['en' => 'Google Cloud Storage (S3 API)', 'zh_CN' => 'Google Cloud Storage（S3 接口）', 'zh_TW' => 'Google Cloud Storage（S3 接口）', 'th' => 'Google Cloud Storage (S3 API)', 'ja' => 'Google Cloud Storage (S3 API)', 'ko' => 'Google Cloud Storage (S3 API)'],
            's3_compatible' => ['en' => 'S3-compatible', 'zh_CN' => 'S3 兼容存储', 'zh_TW' => 'S3 相容儲存', 'th' => 'ที่จัดเก็บที่เข้ากันได้กับ S3', 'ja' => 'S3 互換ストレージ', 'ko' => 'S3 호환 스토리지'],
        ];
        foreach ($defaults['providers'] as $disk => $provider) {
            $configuration = (array) config('filesystems.disks.'.$disk, []);
            $account = FileStorageAccount::firstOrCreate(['disk' => $disk], [
                'name_i18n' => $names[$disk],
                'provider' => $provider['provider'], 'visibility' => $configuration['visibility'] ?? $provider['visibility'],
                'enabled' => in_array($disk, (array) $enabled, true),
                'configuration' => array_intersect_key($configuration, array_flip(['bucket', 'region', 'endpoint', 'url', 'use_path_style_endpoint']))
                    + (in_array($disk, ['public', 'local'], true) ? ['directory' => $disk === 'public' ? 'public' : 'private'] : []),
                'credentials' => array_filter(['key' => $configuration['key'] ?? null, 'secret' => $configuration['secret'] ?? null]),
            ]);
            $account->forceFill(['name_i18n' => array_replace($names[$disk], supportedTranslations($account->name_i18n))])->save();
        }
        SystemSettingDefinition::whereIn('key', ['file.default_disk', 'file.enabled_disks', 'file.max_upload_mb', 'file.allowed_extensions'])->update(['active' => false]);
        $group = SystemSettingGroup::where('code', 'file')->first();
        if ($group && ! $group->definitions()->where('active', true)->exists()) {
            $group->update(['active' => false]);
        }
        $legacy->forget();
    }
}
