<?php

namespace Tests\Feature;

use App\Libs\File\Contracts\StorageProvider;
use App\Libs\File\StorageProviderRegistry;
use App\Libs\File\StorageRegistry;
use App\Models\Common\FileStorageAccount;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorageProviderRegistryTest extends TestCase
{
    use RefreshDatabase;

    public function test_builtin_providers_keep_their_capabilities_and_creation_choices(): void
    {
        $storages = app(StorageRegistry::class);

        $this->assertSame(['local', 'aws', 'cloudflare', 'aliyun', 'google', 's3_compatible'], $storages->providerOptions());
        $this->assertContains('local_private', $storages->providerKeys());
        $this->assertNotContains('local_private', $storages->providerOptions());
        $this->assertTrue($storages->isLocal('local_private'));
        $this->assertFalse($storages->endpointRequired('aws'));
        $this->assertTrue($storages->endpointRequired('cloudflare'));
    }

    public function test_a_custom_provider_only_needs_registration_before_its_disk_can_be_used(): void
    {
        app(StorageProviderRegistry::class)->register('test_plugin', new TestStorageProvider);
        FileStorageAccount::factory()->create(['disk' => 'test_plugin', 'provider' => 'test_plugin', 'configuration' => []]);
        $storages = app(StorageRegistry::class);

        $this->assertContains('test_plugin', $storages->providerOptions());
        $disk = $storages->disk('test_plugin');
        $disk->put('registered.txt', 'registered provider');
        $this->assertSame('registered provider', $disk->get('registered.txt'));
        $disk->delete('registered.txt');
    }
}

final class TestStorageProvider implements StorageProvider
{
    public function rules(): array
    {
        return ['configuration' => ['required', 'array']];
    }

    public function configuration(FileStorageAccount $account): array
    {
        return ['driver' => 'local', 'root' => storage_path('framework/testing/disks/provider-plugin'), 'visibility' => $account->visibility, 'throw' => true];
    }

    public function configured(array $configuration): bool
    {
        return ! empty($configuration['root']);
    }

    public function isLocal(): bool
    {
        return false;
    }

    public function endpointRequired(): bool
    {
        return false;
    }

    public function customize(FilesystemAdapter $filesystem, FileStorageAccount $account): FilesystemAdapter
    {
        return $filesystem;
    }
}
