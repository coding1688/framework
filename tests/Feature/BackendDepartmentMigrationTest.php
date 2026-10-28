<?php

namespace Tests\Feature;

use App\Models\Admin\AdminDepartment;
use App\Models\Admin\AdminMenu;
use App\Models\Admin\AdminUser;
use App\Models\Common\FileStorageAccount;
use App\Models\System\SystemSettingDefinition;
use App\Models\System\SystemSettingGroup;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BackendDepartmentMigrationTest extends TestCase
{
    use DatabaseMigrations;

    public function test_seeders_initialize_board_and_three_led_departments_idempotently(): void
    {
        $this->assertSame(0, AdminDepartment::count());
        $this->seed(DatabaseSeeder::class);
        $admin = AdminUser::orderBy('id')->firstOrFail();
        $this->assertSame('admin', $admin->username);
        $this->assertTrue($admin->is_super);
        $this->assertTrue(Hash::check('12345qwert', $admin->password));
        $this->assertSame('board', $admin->department->code);
        $customNames = $admin->department->name_i18n;
        $customNames['en'] = 'Custom board';
        $admin->department->forceFill(['name_i18n' => $customNames])->save();
        $this->seed(DatabaseSeeder::class);
        $this->assertSame(4, AdminDepartment::count());
        $this->assertSame(4, AdminUser::count());
        $this->assertSame('Custom board', $admin->department->fresh()->name_i18n['en']);
        foreach (AdminDepartment::where('code', '!=', 'board')->get() as $department) {
            $this->assertSame(1, $department->leaders()->count());
            $this->assertSame(0, $department->menus()->count());
        }

        $models = [
            AdminDepartment::class,
            AdminMenu::class,
            SystemSettingGroup::class,
            SystemSettingDefinition::class,
            FileStorageAccount::class,
        ];
        foreach ($models as $modelClass) {
            foreach ($modelClass::all() as $model) {
                $this->assertEqualsCanonicalizing(array_keys(config('platform.locales')), array_keys($model->name_i18n), $modelClass.':'.$model->getKey());
            }
        }
        foreach (SystemSettingDefinition::whereNotNull('options_i18n')->get() as $definition) {
            $this->assertEqualsCanonicalizing(array_keys(config('platform.locales')), array_keys($definition->options_i18n), $definition->key);
        }
        $website = SystemSettingDefinition::where('key', 'website.name')->sole();
        $this->assertEqualsCanonicalizing(array_keys(config('platform.locales')), array_keys($website->default_value['value_i18n']));
    }
}
