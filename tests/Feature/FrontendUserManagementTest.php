<?php

namespace Tests\Feature;

use App\Models\Admin\AdminMenu;
use App\Models\Admin\AdminUser;
use App\Models\Member\User;
use Database\Seeders\backend\admin\AdminMenuSeeder;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class FrontendUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_menu_seeder_places_user_management_under_member_management(): void
    {
        $this->seed(AdminMenuSeeder::class);

        $member = AdminMenu::where('permission', 'member')->sole();
        $user = AdminMenu::where('permission', 'frontend.user')->sole();
        $this->assertSame('directory', $member->type);
        $this->assertSame('会员管理', $member->name_i18n['zh_CN']);
        $this->assertSame($member->id, $user->parent_id);
        $this->assertSame('用户管理', $user->name_i18n['zh_CN']);

        $root = AdminMenu::where('permission', 'admin')->sole();
        $user->forceFill(['parent_id' => $root->id, 'name_i18n' => array_replace($user->name_i18n, ['zh_CN' => '前台用户'])])->save();
        $this->seed(AdminMenuSeeder::class);
        $this->assertSame(1, AdminMenu::where('permission', 'member')->count());
        $this->assertSame($member->id, $user->fresh()->parent_id);
        $this->assertSame('用户管理', $user->fresh()->name_i18n['zh_CN']);
    }

    public function test_board_can_filter_and_view_frontend_users_without_sensitive_fields(): void
    {
        $admin = AdminUser::factory()->super()->create();
        User::factory()->create(['name' => 'Visible User', 'active' => true]);
        User::factory()->unverified()->create(['active' => false]);
        $this->actingAs($admin, 'backend')->withSession(['backend.auth_version' => 1]);

        $this->getJson(route('frontend.user.list', ['q' => 'Visible', 'active' => 1, 'verified' => 1]))
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Visible User')
            ->assertJsonMissingPath('data.0.password')->assertJsonMissingPath('data.0.auth_version');
        $user = User::where('name', 'Visible User')->sole();
        $this->getJson(route('frontend.user.detail', $user))->assertOk()->assertJsonPath('data.id', $user->id);
    }

    public function test_update_status_and_password_change_authentication_version(): void
    {
        $admin = AdminUser::factory()->super()->create();
        $user = User::factory()->create();
        $this->actingAs($admin, 'backend')->withSession(['backend.auth_version' => 1]);

        $this->postJson(route('frontend.user.update', $user), [
            'username' => 'managed_user', 'name' => 'Managed', 'email' => 'managed@example.com', 'vip_level' => 2,
        ])->assertOk()->assertJsonPath('data.email_verified', false);
        $this->postJson(route('frontend.user.status', $user), ['active' => false])->assertOk()->assertJsonPath('data.active', false);
        $this->postJson(route('frontend.user.password', $user), [
            'password' => 'NewSecurePass123', 'password_confirmation' => 'NewSecurePass123',
        ])->assertOk();

        $fresh = $user->fresh();
        $this->assertSame(4, $fresh->auth_version);
        $this->assertFalse($fresh->active);
        $this->assertTrue(Hash::check('NewSecurePass123', $fresh->password));
        $this->assertDatabaseHas('admin_logs', ['action' => 'frontend.user.password', 'subject' => (string) $user->id]);
    }

    public function test_verification_email_can_be_resent_for_unverified_user(): void
    {
        Notification::fake();
        $admin = AdminUser::factory()->super()->create();
        $user = User::factory()->unverified()->create();
        $this->actingAs($admin, 'backend')->withSession(['backend.auth_version' => 1]);

        $this->postJson(route('frontend.user.verification', $user))->assertOk();
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_non_privileged_administrator_is_denied(): void
    {
        $admin = AdminUser::factory()->create(['is_super' => false]);
        $this->actingAs($admin, 'backend')->withSession(['backend.auth_version' => 1]);

        $this->getJson(route('frontend.user.list'))->assertForbidden();
    }
}
