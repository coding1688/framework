<?php

namespace Tests\Feature;

use App\Libs\Ai\AiManager;
use App\Libs\Settings\Resolvers\AiSettingResolver;
use App\Libs\Settings\SettingStore;
use App\Models\Admin\AdminDepartment;
use App\Models\Admin\AdminUser;
use App\Models\System\SystemSettingDefinition;
use App\Models\System\SystemSettingGroup;
use App\Models\System\SystemSettingValue;
use Database\Seeders\backend\admin\AdminMenuSeeder;
use Database\Seeders\backend\setting\SystemSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SystemSettingTest extends TestCase
{
    use RefreshDatabase;

    private const string SecurityPassword = 'SettingSafe123';

    public function test_store_resolves_defaults_saved_values_and_explicit_fallbacks(): void
    {
        $definition = SystemSettingDefinition::factory()->create();
        $settings = app(SettingStore::class);

        $this->assertSame('default', $settings->get($definition->key));
        $this->assertSame('fallback', $settings->get($definition->key, 'fallback'));
        $this->assertSame('fallback', $settings->get('missing.setting', 'fallback'));

        SystemSettingValue::factory()->create(['setting_definition_id' => $definition->id, 'payload' => ['value_string' => 'saved']]);
        $settings->forget();

        $this->assertSame('saved', $settings->get($definition->key));
        $this->assertStringNotContainsString('saved', (string) DB::table('system_setting_values')->where('setting_definition_id', $definition->id)->value('payload'));
    }

    public function test_false_and_zero_are_valid_saved_values(): void
    {
        $switch = SystemSettingDefinition::factory()->create(['input_type' => 'switch', 'validation' => [], 'default_value' => ['value_boolean' => true]]);
        $number = SystemSettingDefinition::factory()->create(['input_type' => 'number', 'validation' => ['precision' => 0, 'min_value' => 0, 'max_value' => 100], 'default_value' => ['value_number' => 10]]);
        SystemSettingValue::factory()->create(['setting_definition_id' => $switch->id, 'payload' => ['value_boolean' => false]]);
        SystemSettingValue::factory()->create(['setting_definition_id' => $number->id, 'payload' => ['value_number' => 0]]);
        $settings = app(SettingStore::class);
        $settings->forget();

        $this->assertFalse($settings->get($switch->key, true));
        $this->assertSame(0.0, $settings->get($number->key, 10));
    }

    public function test_disabling_an_ancestor_disables_values_after_cache_invalidation(): void
    {
        $parent = SystemSettingGroup::factory()->create();
        $child = SystemSettingGroup::factory()->create(['parent_id' => $parent->id]);
        $definition = SystemSettingDefinition::factory()->create(['group_id' => $child->id]);
        SystemSettingValue::factory()->create(['setting_definition_id' => $definition->id, 'payload' => ['value_string' => 'saved']]);
        $settings = app(SettingStore::class);
        $settings->forget();
        $this->assertSame('saved', $settings->get($definition->key));

        $parent->update(['active' => false]);
        $this->assertSame('saved', $settings->get($definition->key));
        $settings->forget();

        $this->assertNull($settings->get($definition->key));
        $this->assertSame('fallback', $settings->get($definition->key, 'fallback'));
    }

    public function test_saving_a_setting_refreshes_runtime_cache_and_list_value(): void
    {
        $this->backend();
        $definition = SystemSettingDefinition::factory()->create();
        $settings = app(SettingStore::class);
        $this->assertSame('default', $settings->get($definition->key));

        $this->postJson(route('system.setting.update'), [
            'group_id' => $definition->group_id,
            'values' => [['definition_id' => $definition->id, 'value' => ['value_string' => 'saved']]],
            'security_password' => self::SecurityPassword,
        ])->assertOk();

        $this->assertSame('saved', $settings->get($definition->key));
        $this->getJson(route('system.setting.list', ['group_id' => $definition->group_id]))->assertOk()
            ->assertJsonPath('data.definitions.0.value.value_string', 'saved');
    }

    public function test_secret_values_are_effective_but_never_disclosed_by_the_list(): void
    {
        $this->backend();
        $definition = SystemSettingDefinition::factory()->secret()->create();
        SystemSettingValue::factory()->create(['setting_definition_id' => $definition->id, 'payload' => ['value_string' => 'top-secret']]);
        app(SettingStore::class)->forget();

        $response = $this->getJson(route('system.setting.list', ['group_id' => $definition->group_id]))->assertOk()
            ->assertJsonPath('data.definitions.0.value', null)
            ->assertJsonPath('data.definitions.0.has_effective_value', true);

        $this->assertSame('top-secret', app(SettingStore::class)->get($definition->key));
        $this->assertStringNotContainsString('top-secret', $response->getContent());
    }

    public function test_ai_provider_groups_and_saved_values_survive_reseeding(): void
    {
        $this->backend();
        $this->seed(SystemSettingSeeder::class);
        $root = SystemSettingGroup::where('code', 'ai')->sole();
        $openAi = SystemSettingDefinition::where('key', 'ai.openai.api_key')->sole();
        SystemSettingValue::factory()->create(['setting_definition_id' => $openAi->id, 'payload' => ['value_string' => 'saved-openai-key']]);
        $openAi->update(['group_id' => $root->id]);

        $this->seed(SystemSettingSeeder::class);

        $chatGpt = SystemSettingGroup::where('code', 'chatgpt')->sole();
        $this->assertSame($root->id, $chatGpt->parent_id);
        $this->assertSame($chatGpt->id, $openAi->fresh()->group_id);
        $this->assertSame('saved-openai-key', app(SettingStore::class)->get('ai.openai.api_key'));
        $this->assertSame(1, SystemSettingValue::where('setting_definition_id', $openAi->id)->count());
        foreach (['claude', 'kimi'] as $code) {
            $this->assertSame($root->id, SystemSettingGroup::where('code', $code)->sole()->parent_id);
            $this->assertTrue(SystemSettingDefinition::where('key', 'ai.'.$code.'.api_key')->sole()->secret);
        }
        $provider = SystemSettingDefinition::where('key', 'ai.provider')->sole();
        $this->assertEquals(['openai' => 'ChatGPT', 'claude' => 'Claude', 'kimi' => 'Kimi'], $provider->options_i18n['en']);
        $resolver = app(AiSettingResolver::class);
        $this->assertTrue($resolver->hasValue('ai.openai.api_key'));
        $this->assertFalse($resolver->hasValue('ai.claude.api_key'));
        $this->assertFalse($resolver->hasValue('ai.kimi.api_key'));

        $optionsResponse = $this->getJson(route('system.setting.options'))->assertOk();
        $ai = collect($optionsResponse->json('data.groups_tree'))->firstWhere('code', 'ai');
        $this->assertSame(['chatgpt', 'claude', 'kimi'], array_column($ai['children'], 'code'));
        $listResponse = $this->getJson(route('system.setting.list', ['group_id' => $chatGpt->id]))->assertOk()
            ->assertJsonPath('data.definitions.0.key', 'ai.openai.api_key')
            ->assertJsonPath('data.definitions.0.has_effective_value', true)
            ->assertJsonPath('data.definitions.0.value', null);
        $this->assertStringNotContainsString('saved-openai-key', $optionsResponse->getContent());
        $this->assertStringNotContainsString('saved-openai-key', $listResponse->getContent());
    }

    public function test_saved_provider_selection_drives_manager_capabilities(): void
    {
        $this->seed(SystemSettingSeeder::class);
        $values = [
            'ai.enabled' => ['value_boolean' => true],
            'ai.provider' => ['value_string' => 'kimi'],
            'ai.kimi.api_key' => ['value_string' => 'saved-kimi-key'],
        ];
        foreach ($values as $key => $payload) {
            SystemSettingValue::factory()->create(['setting_definition_id' => SystemSettingDefinition::where('key', $key)->sole()->id, 'payload' => $payload]);
        }
        app(SettingStore::class)->forget();

        $this->assertSame([
            'provider' => 'kimi',
            'enabled' => true,
            'configured' => true,
            'extraction' => true,
            'image_analysis' => false,
            'moderation' => false,
            'image_generation' => false,
        ], app(AiManager::class)->capabilities());
    }

    private function backend(): void
    {
        AdminDepartment::firstOrCreate(['code' => 'board'], ['name_i18n' => ['en' => 'Board'], 'active' => true]);
        $this->seed(AdminMenuSeeder::class);
        $actor = AdminUser::factory()->super()->create(['security_password' => self::SecurityPassword]);
        $this->actingAs($actor, 'backend')->withSession(['backend.auth_version' => $actor->auth_version]);
    }
}
