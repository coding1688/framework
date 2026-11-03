<?php

namespace Tests\Feature;

use App\Http\Resources\Backend\System\SystemSettingDefinitionResource;
use App\Libs\Fields\FieldDependency;
use App\Libs\Fields\FieldRegistry;
use App\Libs\Fields\Options\OptionContext;
use App\Libs\Fields\Options\OptionSourceRegistry;
use App\Models\Admin\AdminDepartment;
use App\Models\Admin\AdminUser;
use App\Models\System\SystemSettingDefinition;
use App\Models\System\SystemSettingGroup;
use App\Models\System\SystemSettingValue;
use App\Services\Backend\System\SystemSettingDefinitionService;
use Database\Seeders\backend\admin\AdminMenuSeeder;
use Database\Seeders\backend\setting\SystemSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FieldSystemTest extends TestCase
{
    use RefreshDatabase;

    private const string SecurityPassword = 'FieldSafe123';

    public function test_every_registered_field_has_a_component_storage_and_required_contract(): void
    {
        $fields = new FieldRegistry(['en']);
        $expected = [
            'text' => ['input', 'text', 'value_string'],
            'localized_text' => ['localized-input', 'localized_text', 'value_i18n'],
            'number' => ['input-number', 'number', 'value_number'],
            'checkbox' => ['checkbox', 'multi_select', 'value_json'],
            'radio' => ['radio', 'select', 'value_string'],
            'switch' => ['switch', 'boolean', 'value_boolean'],
            'select' => ['select', 'select', 'value_string'],
        ];
        $this->assertSame(array_keys($expected), $fields->keys());
        foreach ($expected as $key => [$component, $dataType, $storageKey]) {
            $field = $fields->get($key);
            $multiple = $field->defaultMultiple();
            $this->assertSame($component, $field->component());
            $this->assertSame($dataType, $field->dataType($multiple));
            $actualStorageKey = match ($field->dataType($multiple)) {
                'text', 'select' => 'value_string',
                'localized_text' => 'value_i18n',
                'number' => 'value_number',
                'boolean' => 'value_boolean',
                'multi_select' => 'value_json',
            };
            $this->assertSame($storageKey, $actualStorageKey);
        }
        $this->assertFalse($fields->get('text')->required(false, ['min_length' => 0]));
        $this->assertTrue($fields->get('text')->required(false, ['min_length' => 1]));
        $this->assertTrue($fields->get('localized_text')->required(false, ['required_locales' => ['en']]));
        $this->assertFalse($fields->get('checkbox')->required(true, ['min_selected' => 0]));
        $this->assertTrue($fields->get('select')->required(true, ['min_selected' => 1]));
        $this->assertTrue($fields->get('select')->required(false, []));
        $this->assertTrue($fields->get('number')->required(false, []));
        $this->assertTrue($fields->get('radio')->required(false, []));
        $this->assertTrue($fields->get('switch')->required(false, []));
    }

    public function test_option_cache_is_reused_isolated_by_context_and_invalidated_explicitly(): void
    {
        $cache = Cache::store('array');
        $cache->flush();
        $calls = 0;
        $options = new OptionSourceRegistry(['en', 'zh_CN'], cache: $cache, cachePrefix: 'test-field-options');
        $options->registerCategory('test', ['en' => 'Test']);
        $options->registerDatabase('items', 'test', ['en' => 'Items'], function (OptionContext $context) use (&$calls): array {
            $calls++;

            return ['one' => $context->locale.':'.$context->actorId];
        });
        $first = new OptionContext('en', 'runtime', 1);
        $second = new OptionContext('en', 'runtime', 2);
        $localized = new OptionContext('zh_CN', 'runtime', 1);

        $this->assertSame(['one' => 'en:1'], $options->options('database', 'items', $first));
        $this->assertSame(['one' => 'en:1'], $options->options('database', 'items', $first));
        $this->assertSame(1, $calls);
        $options->options('database', 'items', $second);
        $this->assertSame(['one' => 'zh_CN:1'], $options->options('database', 'items', $localized));
        $this->assertSame(3, $calls);
        $options->forget();
        $options->options('database', 'items', $first);
        $this->assertSame(4, $calls);
    }

    public function test_dependency_comparison_supports_scalars_arrays_and_translation_dictionaries(): void
    {
        $this->assertTrue(FieldDependency::satisfied(true, '1'));
        $this->assertTrue(FieldDependency::satisfied(0, '0'));
        $this->assertTrue(FieldDependency::satisfied(['CN', 'US'], 'US'));
        $this->assertTrue(FieldDependency::satisfied(['en' => 'Framework', 'zh_CN' => '框架'], '框架'));
        $this->assertTrue(FieldDependency::satisfied(false));
        $this->assertFalse(FieldDependency::satisfied([], null));
        $this->assertFalse(FieldDependency::satisfied(['en' => '', 'zh_CN' => ''], null));
        $this->assertFalse(FieldDependency::satisfied('CN', 'US'));
        $this->assertFalse(FieldDependency::satisfied(null, null));
    }

    public function test_seeded_option_sources_resolve_and_resource_exposes_required_and_option_errors(): void
    {
        $this->seed(SystemSettingSeeder::class);
        $definitions = SystemSettingDefinition::where('option_source_type', 'constant')->get();
        $this->assertNotEmpty($definitions);
        foreach ($definitions as $definition) {
            $this->assertNotSame([], $definition->options, $definition->key);
            $this->assertFalse($definition->getAttribute('options_error'), $definition->key);
        }
        $this->assertSame('common.language', SystemSettingDefinition::where('key', 'site.default_language')->sole()->option_source_key);

        $invalid = SystemSettingDefinition::factory()->create(['input_type' => 'select', 'option_source_type' => 'constant', 'option_source_key' => 'missing.source', 'validation' => []]);
        $resource = (new SystemSettingDefinitionResource($invalid))->toArray(request());
        $this->assertTrue($resource['required']);
        $this->assertTrue($resource['options_error']);
    }

    public function test_dependencies_require_same_group_non_secret_sources_and_acyclic_updates(): void
    {
        $this->backend();
        $group = SystemSettingGroup::factory()->create();
        $otherGroup = SystemSettingGroup::factory()->create();
        $service = app(SystemSettingDefinitionService::class);
        $first = $service->create($this->definitionData($group, 'test.first'));
        $service->create($this->definitionData($group, 'test.second', ['depends_on' => 'test.first']));

        try {
            $service->create($this->definitionData($otherGroup, 'test.invalid', ['depends_on' => 'test.first']));
            $this->fail('Cross-group dependencies must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('depends_on', $exception->errors());
        }
        try {
            $service->update($this->definitionData($group, 'test.first', ['depends_on' => 'test.second']), $first->id);
            $this->fail('Circular dependencies must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('depends_on', $exception->errors());
        }
        try {
            $service->delete($first->id);
            $this->fail('Definitions with dependents must not be deleted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('id', $exception->errors());
        }
    }

    public function test_setting_writes_are_rejected_until_their_dependency_is_satisfied(): void
    {
        $this->backend();
        $group = SystemSettingGroup::factory()->create();
        $parent = SystemSettingDefinition::factory()->create(['group_id' => $group->id, 'key' => 'test.enabled', 'input_type' => 'switch', 'validation' => [], 'default_value' => ['value_boolean' => false]]);
        $child = SystemSettingDefinition::factory()->create(['group_id' => $group->id, 'key' => 'test.child', 'depends_on' => $parent->key, 'depends_value' => '1']);
        $payload = ['group_id' => $group->id, 'values' => [['definition_id' => $child->id, 'value' => ['value_string' => 'saved']]], 'security_password' => self::SecurityPassword];

        $this->postJson(route('system.setting.update'), $payload)->assertUnprocessable();
        $this->assertNull(SystemSettingValue::where('setting_definition_id', $child->id)->first());

        $payload['values'][] = ['definition_id' => $parent->id, 'value' => ['value_boolean' => true]];
        $this->postJson(route('system.setting.update'), $payload)->assertOk();
        $this->assertSame('saved', SystemSettingValue::where('setting_definition_id', $child->id)->sole()->payload['value_string']);
    }

    /** @return array<string, mixed> */
    private function definitionData(SystemSettingGroup $group, string $key, array $overrides = []): array
    {
        return array_merge([
            'group_id' => $group->id, 'key' => $key, 'name_i18n' => ['en' => $key], 'description_i18n' => null,
            'input_type' => 'text', 'multiple' => false, 'option_source_type' => 'manual', 'option_source_key' => null,
            'validation' => ['min_length' => 0, 'max_length' => 128], 'options_i18n' => null, 'default_value' => null,
            'secret' => false, 'active' => true, 'sort' => 0, 'depends_on' => null, 'depends_value' => null,
        ], $overrides);
    }

    private function backend(): void
    {
        AdminDepartment::firstOrCreate(['code' => 'board'], ['name_i18n' => ['en' => 'Board'], 'active' => true]);
        $this->seed(AdminMenuSeeder::class);
        $actor = AdminUser::factory()->super()->create(['security_password' => self::SecurityPassword]);
        $this->actingAs($actor, 'backend')->withSession(['backend.auth_version' => $actor->auth_version]);
    }
}
