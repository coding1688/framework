<?php

namespace Tests\Feature;

use App\Libs\Common\CacheGroupRegistry;
use App\Libs\Fields\Options\OptionContext;
use App\Libs\Fields\Options\OptionSourceRegistry;
use App\Libs\File\StorageRegistry;
use App\Models\Admin\AdminDepartment;
use App\Models\Admin\AdminUser;
use Database\Seeders\backend\admin\AdminMenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CacheManagementTest extends TestCase
{
    use RefreshDatabase;

    private const string SecurityPassword = 'CacheSafe123';

    public function test_registry_groups_entries_and_invalidates_only_the_selected_callback(): void
    {
        $registry = new CacheGroupRegistry;
        $calls = [];
        $entries = [
            ['key' => 'first-key', 'label' => 'First cached item', 'exists' => true],
            ['key' => 'other-key', 'label' => 'Other cached item', 'exists' => true],
        ];
        $registry->registerGroup('first', ['en' => 'First', 'zh_CN' => '第一组']);
        $registry->registerGroup('second', ['en' => 'Second']);
        $registry->register('first.items', 'first', ['en' => 'Items', 'zh_CN' => '项目'], ['en' => 'Cached items'], 'array', 300, function (?string $key = null) use (&$calls): void {
            $calls[] = $key === null ? 'first' : 'refresh:'.$key;
        }, fn (?string $search = null): array => array_values(array_filter($entries, fn (array $entry): bool => $search === null || mb_stripos($entry['key'].' '.$entry['label'], $search) !== false)), function (string $key) use (&$calls): void {
            $calls[] = 'rebuild:'.$key;
        });
        $registry->register('second.items', 'second', ['en' => 'Items'], [], 'array', 60, function () use (&$calls): void {
            $calls[] = 'second';
        }, fn (): array => [], function (): void {});
        app()->setLocale('zh_CN');

        $groups = $registry->list();
        $this->assertSame('第一组', $groups[0]['name']);
        $this->assertSame('项目', $groups[0]['entries'][0]['name']);
        $this->assertSame('Second', $groups[1]['name']);
        $this->assertSame(['first.items', 'second.items'], $registry->keys());
        $this->assertSame(['first-key'], array_column($registry->search('first.items', 'First'), 'key'));
        $this->assertTrue($registry->hasKey('first.items', 'first-key'));
        $this->assertFalse($registry->hasKey('first.items', 'unknown'));
        $registry->forget('first.items');
        $registry->refresh('first.items', 'first-key');
        $registry->rebuild('first.items', 'first-key');
        $this->assertSame(['first', 'refresh:first-key', 'rebuild:first-key'], $calls);
    }

    public function test_option_cache_rebuilds_the_saved_context_without_invalidating_other_locales(): void
    {
        $repository = Cache::store('array');
        $repository->flush();
        $sources = new OptionSourceRegistry(['en', 'zh_CN'], cache: $repository);
        $sources->registerCategory('common', 'Common');
        $calls = [];
        $sources->registerConstant('items', 'common', 'Items', function (OptionContext $context) use (&$calls): array {
            $calls[] = $context->locale.':'.$context->parameters['group'];

            return ['value' => 'call-'.count($calls)];
        });
        $english = new OptionContext('en', 'runtime', parameters: ['group' => 'first']);
        $chinese = new OptionContext('zh_CN', 'runtime', parameters: ['group' => 'first']);

        $this->assertSame('call-1', $sources->options('constant', 'items', $english)['value']);
        $this->assertSame('call-2', $sources->options('constant', 'items', $chinese)['value']);
        $englishEntry = collect($sources->entries())->first(fn (array $entry): bool => str_contains($entry['label'], ' · en'));
        $this->assertNotNull($englishEntry);
        $sources->rebuild($englishEntry['key']);

        $this->assertSame(['en:first', 'zh_CN:first', 'en:first'], $calls);
        $this->assertSame('call-3', $sources->options('constant', 'items', $english)['value']);
        $this->assertSame('call-2', $sources->options('constant', 'items', $chinese)['value']);
        $sources->forget($englishEntry['key']);
        $this->assertSame('call-4', $sources->options('constant', 'items', $english)['value']);
        $this->assertSame('call-2', $sources->options('constant', 'items', $chinese)['value']);
    }

    public function test_backend_lists_registered_caches_and_clear_is_secured_audited_and_scoped(): void
    {
        $actor = $this->backend();
        $this->getJson(route('system.cache.list'))->assertOk()
            ->assertJsonPath('data.0.key', 'field')
            ->assertJsonPath('data.0.entries.0.key', 'field.options')
            ->assertJsonPath('data.1.key', 'file')
            ->assertJsonPath('data.1.entries.0.key', 'file.storage');
        $cache = Cache::store(config('cache.default'));
        $cache->forever('field-options:version', 3);
        $cache->forever('file-storage:version', 4);

        $this->postJson(route('system.cache.clear'), ['key' => 'field.options'])->assertUnprocessable()->assertJsonValidationErrors('security_password');
        $this->postJson(route('system.cache.clear'), ['key' => 'unknown.cache', 'security_password' => self::SecurityPassword])->assertUnprocessable()->assertJsonValidationErrors('key');
        $this->postJson(route('system.cache.clear'), ['key' => 'field.options', 'security_password' => self::SecurityPassword])->assertOk();

        $this->assertSame(4, $cache->get('field-options:version'));
        $this->assertSame(4, $cache->get('file-storage:version'));
        $this->assertDatabaseHas('admin_logs', ['administrator_id' => $actor->id, 'action' => 'system.cache.clear', 'subject' => 'field.options']);
        $this->assertDatabaseHas('admin_menus', ['permission' => 'system.cache.clear', 'type' => 'button']);
    }

    public function test_backend_searches_refreshes_and_rebuilds_individual_storage_keys(): void
    {
        config()->set('files.cache_store', 'array');
        $actor = $this->backend();
        $cache = Cache::store('array');
        $cache->flush();
        $cache->forever('file-storage:version', 4);
        $storage = app(StorageRegistry::class);
        $storage->uploadSettings();
        $storage->providers();

        $this->getJson(route('system.cache.keys', ['key' => 'file.storage', 'search' => 'upload']))->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.key', 'upload-settings')->assertJsonPath('total', 1);
        $this->getJson(route('system.cache.keys', ['key' => 'file.storage', 'search' => 'missing']))->assertOk()->assertJsonCount(0, 'data');
        $this->getJson(route('system.cache.keys', ['key' => 'unknown.cache']))->assertUnprocessable()->assertJsonValidationErrors('key');
        $this->postJson(route('system.cache.refresh'), ['key' => 'file.storage', 'cache_key' => 'upload-settings'])->assertUnprocessable()->assertJsonValidationErrors('security_password');
        $this->postJson(route('system.cache.refresh'), ['key' => 'file.storage', 'cache_key' => 'unknown', 'security_password' => self::SecurityPassword])->assertUnprocessable()->assertJsonValidationErrors('cache_key');
        $this->postJson(route('system.cache.refresh'), ['key' => 'file.storage', 'cache_key' => 'upload-settings', 'security_password' => self::SecurityPassword])->assertOk();
        $this->getJson(route('system.cache.keys', ['key' => 'file.storage']))->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.key', 'providers');

        $cache->forget('file-storage:4:providers');
        $this->getJson(route('system.cache.keys', ['key' => 'file.storage']))->assertOk()->assertJsonPath('data.0.exists', false);
        $this->postJson(route('system.cache.rebuild'), ['key' => 'file.storage', 'cache_key' => 'providers'])->assertUnprocessable()->assertJsonValidationErrors('security_password');
        $this->postJson(route('system.cache.rebuild'), ['key' => 'file.storage', 'cache_key' => 'providers', 'security_password' => self::SecurityPassword])->assertOk();
        $this->getJson(route('system.cache.keys', ['key' => 'file.storage']))->assertOk()->assertJsonPath('data.0.exists', true);

        $this->assertSame(4, $cache->get('file-storage:version'));
        $this->assertDatabaseHas('admin_logs', ['administrator_id' => $actor->id, 'action' => 'system.cache.refresh', 'subject' => 'file.storage']);
        $this->assertDatabaseHas('admin_logs', ['administrator_id' => $actor->id, 'action' => 'system.cache.rebuild', 'subject' => 'file.storage']);
        foreach (['keys', 'refresh', 'rebuild'] as $action) {
            $this->assertDatabaseHas('admin_menus', ['permission' => 'system.cache.'.$action, 'type' => 'button']);
        }
    }

    public function test_backend_cache_keys_are_paginated(): void
    {
        config()->set('fields.cache_store', 'array');
        $this->backend();
        Cache::store('array')->flush();
        $sources = app(OptionSourceRegistry::class);
        foreach (range(1, 25) as $index) {
            $sources->options('constant', 'common.language', new OptionContext('en', parameters: ['index' => $index]));
        }

        $this->getJson(route('system.cache.keys', ['key' => 'field.options', 'page' => 2]))->assertOk()
            ->assertJsonCount(5, 'data')->assertJsonPath('total', 25)->assertJsonPath('last_page', 2);
        $this->getJson(route('system.cache.keys', ['key' => 'field.options', 'search' => 'common.language']))->assertOk()->assertJsonPath('total', 25);
    }

    public function test_non_board_accounts_cannot_access_cache_management(): void
    {
        $this->backend();
        $actor = AdminUser::factory()->create();
        $this->actingAs($actor, 'backend')->withSession(['backend.auth_version' => $actor->auth_version]);

        $this->getJson(route('system.cache.list'))->assertForbidden();
        $this->getJson(route('system.cache.keys', ['key' => 'file.storage']))->assertForbidden();
    }

    private function backend(): AdminUser
    {
        AdminDepartment::firstOrCreate(['code' => 'board'], ['name_i18n' => ['en' => 'Board'], 'active' => true]);
        $this->seed(AdminMenuSeeder::class);
        $actor = AdminUser::factory()->super()->create(['security_password' => self::SecurityPassword]);
        $this->actingAs($actor, 'backend')->withSession(['backend.auth_version' => $actor->auth_version]);

        return $actor;
    }
}
