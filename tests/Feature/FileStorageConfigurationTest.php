<?php

namespace Tests\Feature;

use App\Libs\File\StorageRegistry;
use App\Libs\File\UploadConfiguration;
use App\Libs\Settings\SettingStore;
use App\Models\Admin\AdminDepartment;
use App\Models\Admin\AdminMenu;
use App\Models\Admin\AdminUser;
use App\Models\Common\File;
use App\Models\Common\FileConfiguration;
use App\Models\Common\FileStorageAccount;
use App\Models\System\SystemSettingDefinition;
use App\Models\System\SystemSettingGroup;
use App\Models\System\SystemSettingValue;
use Aws\MockHandler;
use Aws\Result;
use Database\Seeders\backend\admin\AdminMenuSeeder;
use Database\Seeders\backend\file\FileStorageSeeder;
use Database\Seeders\backend\setting\SystemSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class FileStorageConfigurationTest extends TestCase
{
    use RefreshDatabase;

    private const BASE = 'http://a.base.local/admin/file/storage/';

    protected function setUp(): void
    {
        parent::setUp();
        AdminDepartment::unguarded(fn () => AdminDepartment::firstOrCreate(['code' => 'board'], ['name_i18n' => ['en' => 'Board'], 'active' => true]));
        $this->seed([AdminMenuSeeder::class, FileStorageSeeder::class, SystemSettingSeeder::class]);
        $actor = AdminUser::factory()->super()->create(['security_password' => 'StorageSafe123']);
        $this->actingAs($actor, 'backend')->withSession(['backend.auth_version' => $actor->auth_version]);
    }

    public function test_storage_page_and_permissions_are_discovered_and_rules_are_independent(): void
    {
        $page = AdminMenu::where('permission', 'file.storage')->sole();
        $this->assertSame('file/storage/List', $page->component);
        $this->assertSame(AdminMenu::where('permission', 'development')->sole()->id, $page->parent_id);
        foreach (['list', 'create', 'update', 'delete', 'settings', 'saveSettings', 'tasks', 'sync', 'retry'] as $action) {
            $this->assertDatabaseHas('admin_menus', ['permission' => 'file.storage.'.$action, 'parent_id' => $page->id]);
        }
        $this->getJson(self::BASE.'list')->assertOk()->assertJsonCount(7, 'data');
        $this->getJson(self::BASE.'settings')->assertOk()->assertJsonPath('data.max_upload_mb', 32)->assertJsonPath('data.hard_max_upload_mb', 1024);
        $this->assertDatabaseMissing('system_setting_definitions', ['key' => 'file.max_upload_mb']);
    }

    public function test_r2_can_be_configured_in_database_with_encrypted_non_disclosed_credentials(): void
    {
        $r2 = FileStorageAccount::where('disk', 'r2')->sole();
        $payload = $this->payload($r2);
        $this->protectedPostJson(self::BASE.'update/'.$r2->id, $payload)->assertOk()
            ->assertJsonPath('data.configured', true)->assertJsonPath('data.has_access_key', true)
            ->assertJsonPath('data.has_secret_key', true)->assertJsonMissing(['secret_key' => 'test-secret'])
            ->assertJsonMissing(['access_key' => 'test-access']);
        $raw = DB::table('file_storages')->where('id', $r2->id)->value('credentials');
        $this->assertStringNotContainsString('test-secret', $raw);
        $this->assertStringNotContainsString('test-access', $raw);
        $this->assertStringNotContainsString('test-secret', $this->getJson(self::BASE.'list')->getContent());
        $this->assertStringNotContainsString('test-secret', json_encode(DB::table('admin_logs')->where('action', 'file.storage.update')->get()));
        $this->assertSame('test-secret', $r2->fresh()->credentials['secret']);
        $this->assertStringNotContainsString('test-secret', $r2->fresh()->toJson());
        $payload['access_key'] = '';
        $payload['secret_key'] = '';
        $this->protectedPostJson(self::BASE.'update/'.$r2->id, $payload)->assertOk();
        $this->assertSame('test-secret', $r2->fresh()->credentials['secret']);
    }

    public function test_r2_removes_acl_for_single_and_multipart_commands_without_network_calls(): void
    {
        $r2 = FileStorageAccount::where('disk', 'r2')->sole();
        $this->protectedPostJson(self::BASE.'update/'.$r2->id, $this->payload($r2))->assertOk();
        $client = app(StorageRegistry::class)->disk('r2')->getClient();
        $client->getHandlerList()->setHandler(new MockHandler([new Result([]), new Result([])]));
        foreach (['PutObject', 'CreateMultipartUpload'] as $action) {
            $command = $client->getCommand($action, ['Bucket' => 'test-bucket', 'Key' => 'test.txt', 'ACL' => 'public-read']);
            $client->execute($command);
            $this->assertFalse(isset($command['ACL']));
        }
    }

    public function test_upload_rules_apply_to_all_upload_options_and_reject_unsafe_extensions(): void
    {
        $this->protectedPostJson(self::BASE.'save-settings', ['default_disk' => 'public', 'max_upload_mb' => 64, 'allowed_extensions' => ['png', 'json']])->assertOk();
        $this->getJson('http://a.base.local/admin/file/item/options')->assertOk()
            ->assertJsonPath('data.max_size_kb', 65536)->assertJsonPath('data.allowed_extensions', ['png', 'json']);
        $this->protectedPostJson(self::BASE.'save-settings', ['default_disk' => 'public', 'max_upload_mb' => 2048, 'allowed_extensions' => ['php']])->assertUnprocessable()
            ->assertJsonValidationErrors(['max_upload_mb', 'allowed_extensions.0']);
        $this->protectedPostJson(self::BASE.'save-settings', ['default_disk' => 'public', 'max_upload_mb' => 0, 'allowed_extensions' => []])->assertOk();
        $this->assertSame(['prohibited'], app(UploadConfiguration::class)->fileRules());
    }

    public function test_multiple_accounts_support_same_provider_but_not_the_same_physical_bucket(): void
    {
        $r2 = FileStorageAccount::where('disk', 'r2')->sole();
        $payload = $this->payload($r2);
        $payload['disk'] = 'r2_backup';
        $this->protectedPostJson(self::BASE.'create', $payload)->assertCreated();
        $payload['disk'] = 'r2_duplicate';
        $this->protectedPostJson(self::BASE.'create', $payload)->assertUnprocessable()->assertJsonValidationErrors('configuration.bucket');
        $payload['configuration']['bucket'] = 'another-bucket';
        $this->protectedPostJson(self::BASE.'create', $payload)->assertCreated();
    }

    public function test_populated_storage_location_default_storage_and_builtins_are_protected(): void
    {
        $r2 = FileStorageAccount::where('disk', 'r2')->sole();
        $payload = $this->payload($r2);
        $this->protectedPostJson(self::BASE.'update/'.$r2->id, $payload)->assertOk();
        File::factory()->create(['disk' => 'r2']);
        $payload['configuration']['bucket'] = 'changed-bucket';
        $this->protectedPostJson(self::BASE.'update/'.$r2->id, $payload)->assertUnprocessable()->assertJsonValidationErrors('configuration.bucket');
        $this->protectedPostJson(self::BASE.'delete/'.$r2->id)->assertUnprocessable();
        $this->protectedPostJson(self::BASE.'delete/'.FileStorageAccount::where('disk', 'public')->sole()->id)->assertUnprocessable();
        $public = FileStorageAccount::where('disk', 'public')->sole();
        $payload = $public->only(['disk', 'name_i18n', 'provider', 'visibility', 'enabled', 'configuration']);
        $payload['enabled'] = false;
        $this->protectedPostJson(self::BASE.'update/'.$public->id, $payload)->assertUnprocessable()->assertJsonValidationErrors('enabled');
    }

    public function test_local_paths_cannot_escape_or_overlap_existing_storage(): void
    {
        $payload = ['disk' => 'backup', 'name_i18n' => ['en' => 'Backup'], 'provider' => 'local', 'visibility' => 'private', 'enabled' => true, 'configuration' => ['directory' => '../outside']];
        $this->protectedPostJson(self::BASE.'create', $payload)->assertUnprocessable()->assertJsonValidationErrors('configuration.directory');
        $payload['configuration']['directory'] = 'public/subdir';
        $this->protectedPostJson(self::BASE.'create', $payload)->assertUnprocessable()->assertJsonValidationErrors('configuration.directory');
        $payload['configuration']['directory'] = 'independent-backup';
        $this->protectedPostJson(self::BASE.'create', $payload)->assertCreated();
    }

    public function test_storage_permissions_are_separate_from_file_upload_permissions(): void
    {
        $department = AdminDepartment::create(['parent_id' => AdminDepartment::where('code', 'board')->sole()->id, 'name_i18n' => ['en' => 'Editors'], 'active' => true]);
        $department->menus()->attach(AdminMenu::where('permission', 'file.item.upload')->sole()->id);
        $actor = AdminUser::factory()->create(['department_id' => $department->id, 'security_password' => 'StorageSafe123']);
        $this->actingAs($actor, 'backend')->withSession(['backend.auth_version' => $actor->auth_version]);
        $this->getJson(self::BASE.'list')->assertForbidden();
        $this->protectedPostJson(self::BASE.'save-settings', ['max_upload_mb' => 1, 'allowed_extensions' => ['txt']])->assertForbidden();
        $this->protectedPostJson(self::BASE.'sync', ['source_disk' => 'local', 'target_disk' => 'public'])->assertForbidden();
    }

    public function test_reseeding_preserves_storage_credentials_and_upload_rules(): void
    {
        $r2 = FileStorageAccount::where('disk', 'r2')->sole();
        $this->protectedPostJson(self::BASE.'update/'.$r2->id, $this->payload($r2))->assertOk();
        FileConfiguration::find(1)->update(['max_upload_mb' => 64, 'allowed_extensions' => ['json']]);
        $this->seed([FileStorageSeeder::class, SystemSettingSeeder::class]);
        $this->assertSame('test-secret', $r2->fresh()->credentials['secret']);
        $this->assertSame(65536, app(UploadConfiguration::class)->maxSizeKb());
        $this->assertSame(['json'], app(UploadConfiguration::class)->allowedExtensions());
    }

    public function test_legacy_settings_are_imported_once_and_old_page_is_disabled_without_deleting_values(): void
    {
        FileConfiguration::find(1)->delete();
        $group = SystemSettingGroup::factory()->create(['code' => 'file', 'active' => true]);
        $definition = SystemSettingDefinition::factory()->create(['group_id' => $group->id, 'key' => 'file.max_upload_mb', 'input_type' => 'number', 'multiple' => false, 'active' => true, 'validation' => ['min_value' => 0, 'max_value' => 1024, 'precision' => 0]]);
        SystemSettingValue::create(['setting_definition_id' => $definition->id, 'payload' => ['value_number' => 64]]);
        app(SettingStore::class)->forget();
        $this->seed(FileStorageSeeder::class);
        $this->assertSame(64, FileConfiguration::find(1)->max_upload_mb);
        $this->assertFalse($definition->fresh()->active);
        $this->assertFalse($group->fresh()->active);
        $this->assertEquals(64, $definition->value->payload['value_number']);
        $this->seed([FileStorageSeeder::class, SystemSettingSeeder::class]);
        $this->assertSame(64, FileConfiguration::find(1)->max_upload_mb);
    }

    public function test_storage_writes_require_security_password_without_changing_configuration(): void
    {
        $r2 = FileStorageAccount::where('disk', 'r2')->sole();
        $before = $r2->getRawOriginal();
        foreach (['create', 'update/'.$r2->id, 'delete/'.$r2->id, 'save-settings'] as $action) {
            $this->postJson(self::BASE.$action, [])->assertUnprocessable()->assertJsonValidationErrors('security_password');
            $this->postJson(self::BASE.$action, ['security_password' => 'WrongSafe123'])->assertUnprocessable()->assertJsonValidationErrors('security_password');
        }
        $this->assertSame($before, $r2->fresh()->getRawOriginal());
        $this->assertSame(32, FileConfiguration::find(1)->max_upload_mb);
    }

    private function protectedPostJson(string $uri, array $data = []): TestResponse
    {
        return $this->postJson($uri, [...$data, 'security_password' => 'StorageSafe123']);
    }

    private function payload(FileStorageAccount $account): array
    {
        return ['disk' => $account->disk, 'name_i18n' => ['en' => 'R2'], 'provider' => 'cloudflare', 'visibility' => 'public', 'enabled' => true,
            'configuration' => ['bucket' => 'test-bucket', 'region' => 'auto', 'endpoint' => 'https://test-account.r2.cloudflarestorage.com', 'url' => 'https://assets.example.com', 'use_path_style_endpoint' => false],
            'access_key' => 'test-access', 'secret_key' => 'test-secret'];
    }
}
