<?php

namespace Tests\Feature;

use App\Models\Admin\AdminDepartment;
use App\Models\Admin\AdminUser;
use Database\Seeders\backend\admin\AdminDeparturementSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FrameworkInitializationTest extends TestCase
{
    use RefreshDatabase;

    public function test_local_seeding_creates_demo_accounts_without_overwriting_credentials(): void
    {
        $this->seed(AdminDeparturementSeeder::class);

        $this->assertSame(4, AdminDepartment::count());
        $this->assertSame(4, AdminUser::count());
        $administrator = AdminUser::where('username', 'admin')->sole();
        $password = $administrator->password;

        $this->seed(AdminDeparturementSeeder::class);

        $this->assertSame($password, $administrator->fresh()->password);
        $this->assertSame(4, AdminUser::count());
    }

    public function test_non_local_seeding_creates_board_without_demo_accounts(): void
    {
        $this->app['env'] = 'production';

        (new AdminDeparturementSeeder)->run();

        $this->assertSame(1, AdminDepartment::count());
        $this->assertSame('board', AdminDepartment::sole()->code);
        $this->assertSame(0, AdminUser::count());
    }
}
