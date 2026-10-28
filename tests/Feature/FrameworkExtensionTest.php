<?php

namespace Tests\Feature;

use App\Libs\Fields\Contracts\FieldType;
use App\Libs\Fields\FieldRegistry;
use App\Libs\File\FileReferenceOwnerRegistry;
use App\Libs\Settings\Contracts\SettingResolver;
use App\Libs\Settings\SettingResolverRegistry;
use App\Models\Content\ContentCarousel;
use Tests\TestCase;

class FrameworkExtensionTest extends TestCase
{
    public function test_field_types_can_be_registered_without_changing_the_registry(): void
    {
        $fields = new FieldRegistry(['en']);
        $field = $this->createMock(FieldType::class);
        $field->method('key')->willReturn('extension');

        $fields->register($field);

        $this->assertSame($field, $fields->get('extension'));
    }

    public function test_setting_resolvers_can_be_registered_by_modules(): void
    {
        $settings = new SettingResolverRegistry;
        $settings->register(new class implements SettingResolver
        {
            public function values(): array
            {
                return ['extension.enabled' => ['value_boolean' => true]];
            }

            public function hasValue(string $key): ?bool
            {
                return $key === 'extension.secret' ? true : null;
            }
        });

        $this->assertSame(['extension.enabled' => ['value_boolean' => true]], $settings->values());
        $this->assertTrue($settings->hasValue('extension.secret'));
        $this->assertNull($settings->hasValue('other.secret'));
    }

    public function test_file_reference_owners_are_provided_by_modules(): void
    {
        $owners = new FileReferenceOwnerRegistry;
        $owners->register(ContentCarousel::class);

        $this->assertSame([ContentCarousel::class], $owners->classes());
        $this->assertContains(ContentCarousel::class, app(FileReferenceOwnerRegistry::class)->classes());
    }
}
