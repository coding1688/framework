<?php

namespace Tests\Feature;

use App\Libs\File\FileReferences;
use App\Libs\File\StorageRegistry;
use App\Libs\File\UploadConfiguration;
use App\Models\Admin\AdminUser;
use App\Models\Common\File;
use App\Models\Common\FileConfiguration;
use App\Models\Common\FileStorageAccount;
use Database\Seeders\backend\file\FileStorageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UploadConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_module_defaults_work_without_platform_uploads_or_system_settings(): void
    {
        $uploads = app(UploadConfiguration::class);
        $this->assertNull(config('platform.uploads'));
        $this->assertSame(['public', 'local'], $uploads->enabledDisks());
        $this->assertSame('public', $uploads->defaultDisk());
        $this->assertSame('public', $uploads->publicDisk());
        $this->assertSame(32768, $uploads->maxSizeKb());
        $this->assertSame($uploads->defaults()['allowed_extensions'], $uploads->allowedExtensions());
        $this->assertFalse($uploads->providers()['r2']['configured']);
        config(['files.uploads' => null]);
        $this->assertSame(32, app(UploadConfiguration::class)->defaults()['max_upload_mb']);
    }

    public function test_file_configuration_can_increase_size_above_default_and_clearing_restores_default(): void
    {
        $this->seed(FileStorageSeeder::class);
        $this->setValue('file.max_upload_mb', ['value_number' => 64]);
        $uploads = app(UploadConfiguration::class);
        $this->assertSame(65536, $uploads->maxSizeKb());
        $rules = $uploads->fileRules();
        $this->assertContains('max:65536', $rules);
        $this->assertTrue(Validator::make(['file' => UploadedFile::fake()->create('notes.txt', 40000, 'text/plain')], ['file' => $rules])->passes());
        FileConfiguration::find(1)->delete();
        $this->assertSame(32768, $uploads->maxSizeKb());
    }

    public function test_empty_storage_and_extensions_are_real_overrides_not_fallbacks(): void
    {
        $this->seed(FileStorageSeeder::class);
        $this->setValue('file.enabled_disks', ['value_json' => []]);
        $this->setValue('file.allowed_extensions', ['value_json' => []]);
        $uploads = app(UploadConfiguration::class);
        $this->assertSame([], $uploads->enabledDisks());
        $this->assertNull($uploads->defaultDisk());
        $this->assertSame([], $uploads->allowedExtensions());
        $this->assertSame(['prohibited'], $uploads->fileRules());
        $this->assertFalse(Validator::make(['file' => UploadedFile::fake()->create('notes.txt', 1, 'text/plain')], ['file' => $uploads->fileRules()])->passes());
        $this->expectException(ValidationException::class);
        $uploads->publicDisk();
    }

    public function test_zero_size_disables_uploads_instead_of_restoring_default(): void
    {
        $this->seed(FileStorageSeeder::class);
        $this->setValue('file.max_upload_mb', ['value_number' => 0]);
        $uploads = app(UploadConfiguration::class);
        $this->assertSame(0, $uploads->maxSizeKb());
        $this->assertSame(['prohibited'], $uploads->fileRules());
    }

    public function test_extension_overrides_are_not_intersected_with_the_default_list(): void
    {
        $this->seed(FileStorageSeeder::class);
        $this->setValue('file.allowed_extensions', ['value_json' => ['json']]);
        $uploads = app(UploadConfiguration::class);
        $this->assertNotContains('json', $uploads->defaults()['allowed_extensions']);
        $this->assertSame(['json'], $uploads->allowedExtensions());
        $this->assertContains('mimes:json', $uploads->fileRules());
        $this->assertSame(['prohibited'], $uploads->fileRules(['png']));
        $this->seed(FileStorageSeeder::class);
        $this->assertSame(['json'], $uploads->allowedExtensions());
    }

    public function test_explicit_safety_limits_are_separate_from_defaults(): void
    {
        $settings = $this->createStub(StorageRegistry::class);
        $settings->method('uploadSettings')->willReturn(['max_upload_mb' => 2048, 'allowed_extensions' => ['php']]);
        $uploads = new UploadConfiguration($settings);
        $this->assertSame(1048576, $uploads->maxSizeKb());
        $this->assertSame([], $uploads->allowedExtensions());
        $this->assertSame(['prohibited'], $uploads->fileRules());
    }

    public function test_configured_cloud_storage_is_not_enabled_until_selected(): void
    {
        config(['filesystems.disks.r2.bucket' => 'test-bucket', 'filesystems.disks.r2.visibility' => 'public', 'filesystems.disks.r2.key' => 'test-access', 'filesystems.disks.r2.secret' => 'test-secret', 'filesystems.disks.r2.endpoint' => 'https://test.r2.cloudflarestorage.com']);
        $this->seed(FileStorageSeeder::class);
        $uploads = app(UploadConfiguration::class);
        $this->assertTrue($uploads->availableProviders()['r2']['configured']);
        $this->assertFalse($uploads->providers()['r2']['enabled']);
        $this->setValue('file.enabled_disks', ['value_json' => ['r2']]);
        $this->setValue('file.default_disk', ['value_string' => 'r2']);
        $this->assertSame(['r2'], $uploads->enabledDisks());
        $this->assertSame('r2', $uploads->defaultDisk());
        $this->assertSame('r2', $uploads->publicDisk());
    }

    public function test_disabling_uploads_does_not_lose_existing_file_references(): void
    {
        Storage::fake('public');
        $this->seed(FileStorageSeeder::class);
        $file = File::factory()->create();
        $owner = AdminUser::factory()->create();
        app(FileReferences::class)->sync($owner, ['attachment' => [$file->id]]);
        $this->setValue('file.enabled_disks', ['value_json' => []]);
        $this->assertSame(1, $file->references()->count());
    }

    public function test_reseeding_does_not_overwrite_saved_choices(): void
    {
        $this->seed(FileStorageSeeder::class);
        $this->setValue('file.max_upload_mb', ['value_number' => 64]);
        $this->setValue('file.enabled_disks', ['value_json' => []]);
        $this->seed(FileStorageSeeder::class);
        $this->assertSame(65536, app(UploadConfiguration::class)->maxSizeKb());
        $this->assertSame([], app(UploadConfiguration::class)->enabledDisks());
        $this->assertSame(7, FileStorageAccount::count());
        $this->assertSame(1024, app(UploadConfiguration::class)->defaults()['limits']['hard_max_upload_mb']);
    }

    private function setValue(string $key, array $payload): void
    {
        if ($key === 'file.enabled_disks') {
            FileStorageAccount::query()->update(['enabled' => false]);
            FileStorageAccount::whereIn('disk', $payload['value_json'])->update(['enabled' => true]);
        } else {
            FileConfiguration::find(1)->update([substr($key, 5) => array_values($payload)[0]]);
        }
    }
}
