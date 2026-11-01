<?php

namespace Tests\Feature;

use App\Models\Admin\AdminMenu;
use App\Models\Admin\AdminUser;
use App\Models\Member\User;
use App\Models\Member\UserLog;
use App\Services\Member\UserRestrictionService;
use Database\Seeders\backend\admin\AdminMenuSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class FrontendUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_frontend_users_are_seeded_once_with_expected_types_and_password(): void
    {
        $this->seed(UserSeeder::class);

        $testUser = User::where('username', 'test001')->sole();
        $regularUser = User::where('username', 'user001')->sole();
        $this->assertTrue($testUser->is_test);
        $this->assertFalse($regularUser->is_test);
        $this->assertTrue($testUser->hasVerifiedEmail());
        $this->assertTrue($regularUser->hasVerifiedEmail());
        $this->assertTrue(Hash::check('12345qwert', $testUser->password));
        $this->assertTrue(Hash::check('12345qwert', $regularUser->password));
        $this->assertSame('test001', $testUser->profile->nickname);
        $this->assertSame('user001', $regularUser->profile->nickname);

        $testUser->forceFill(['password' => 'ChangedSafe123'])->save();
        $this->seed(UserSeeder::class);
        $this->assertSame(2, User::whereIn('username', ['test001', 'user001'])->count());
        $this->assertTrue(Hash::check('ChangedSafe123', $testUser->fresh()->password));
    }

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
        $regularUser = User::factory()->create(['name' => 'Visible User', 'active' => true]);
        $regularUser->wallets()->create(['currency' => 'RMB', 'balance_minor' => 12345]);
        $regularUser->forceFill(['last_login_device_type' => UserLog::DEVICE_TYPE_WEB])->save();
        User::factory()->unverified()->create(['active' => false]);
        $testUser = User::factory()->test()->create(['name' => 'Sandbox User']);
        $this->assertFalse($regularUser->is_test);
        $this->assertTrue($testUser->is_test);
        $this->actingAs($admin, 'backend')->withSession(['backend.auth_version' => 1]);

        $this->getJson(route('frontend.user.list', ['q' => 'Visible', 'active' => 1, 'verified' => 1, 'is_test' => 0]))
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Visible User')
            ->assertJsonPath('data.0.is_test', false)->assertJsonPath('data.0.default_currency', 'RMB')
            ->assertJsonPath('data.0.total_balance', '123.45')->assertJsonPath('data.0.available_balance', '123.45')
            ->assertJsonPath('data.0.last_login_device_type', UserLog::DEVICE_TYPE_WEB)
            ->assertJsonMissingPath('data.0.password')->assertJsonMissingPath('data.0.auth_version');
        $this->getJson(route('frontend.user.list', ['is_test' => 1]))->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $testUser->id)->assertJsonPath('data.0.is_test', true);
        $this->getJson(route('frontend.user.detail', $regularUser))->assertOk()
            ->assertJsonPath('data.id', $regularUser->id)->assertJsonPath('data.is_test', false);
    }

    public function test_freeze_unfreeze_and_password_changes_update_sessions_and_logs(): void
    {
        $admin = AdminUser::factory()->super()->create(['security_password' => 'SeparateSafe123']);
        $user = User::factory()->create();
        $this->actingAs($admin, 'backend')->withSession(['backend.auth_version' => 1]);

        $this->postJson(route('frontend.user.update', $user), [
            'username' => 'managed_user', 'name' => 'Managed', 'email' => 'managed@example.com', 'vip_level' => 2, 'is_test' => true,
            'remark' => 'Managed account', 'locale' => 'zh_CN', 'default_currency' => 'USDT',
        ])->assertOk()->assertJsonPath('data.email_verified', false)->assertJsonPath('data.is_test', true)
            ->assertJsonPath('data.remark', 'Managed account')->assertJsonPath('data.locale', 'zh_CN')
            ->assertJsonPath('data.default_currency', 'USDT')->assertJsonPath('data.total_balance', '0.000000');
        $freshUser = $user->fresh();
        $this->assertTrue($freshUser->is_test);
        $this->assertSame('Managed account', $freshUser->remark);
        $this->assertSame('zh_CN', $freshUser->locale);
        $this->assertSame('USDT', $freshUser->default_currency);
        $this->getJson(route('frontend.user.detail', $user))->assertOk()
            ->assertJsonPath('data.locale_options.en', 'English')->assertJsonPath('data.currency_options.0', 'RMB');
        $this->postJson(route('frontend.user.status', $user), ['active' => false])
            ->assertUnprocessable()->assertJsonValidationErrors('reason');
        $this->postJson(route('frontend.user.status', $user), ['active' => false, 'reason' => 'Suspicious activity'])
            ->assertOk()->assertJsonPath('data.active', false)->assertJsonPath('data.frozen_reason', 'Suspicious activity');
        $this->postJson(route('frontend.user.password', $user), [
            'password' => 'NewSecurePass123', 'password_confirmation' => 'NewSecurePass123',
        ])->assertUnprocessable()->assertJsonValidationErrors('security_password');
        $this->postJson(route('frontend.user.password', $user), [
            'password' => 'NewSecurePass123', 'password_confirmation' => 'NewSecurePass123', 'security_password' => 'SeparateSafe123',
        ])->assertOk();

        $frozen = $user->fresh();
        $this->assertSame(4, $frozen->auth_version);
        $this->assertFalse($frozen->active);
        $this->assertNotNull($frozen->activeFreeze);
        $this->assertSame($admin->name, $frozen->activeFreeze->created_by);
        $this->assertTrue(Hash::check('NewSecurePass123', $frozen->password));
        $this->assertDatabaseHas('admin_logs', ['action' => 'frontend.user.password', 'subject' => (string) $user->id]);
        $this->assertDatabaseHas('user_logs', ['user_id' => $user->id, 'action' => 'admin.frozen', 'actor' => $admin->name]);

        $this->getJson(route('frontend.user.logs', ['id' => $user->id, 'action' => 'admin.frozen']))
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.context.reason', 'Suspicious activity');
        $this->postJson(route('frontend.user.status', $user), ['active' => true, 'reason' => 'Review completed'])
            ->assertOk()->assertJsonPath('data.active', true)->assertJsonPath('data.frozen_at', null);

        $active = $user->fresh();
        $this->assertSame(5, $active->auth_version);
        $this->assertNull($active->activeFreeze);
        $this->assertNotNull($active->restrictions()->where('type', 'login')->sole()->lifted_at);
        $this->getJson(route('frontend.user.logs', $user))->assertOk()->assertJsonCount(4, 'data');
    }

    public function test_batch_restrictions_are_unique_and_scoped_to_their_capabilities(): void
    {
        $admin = AdminUser::factory()->super()->create();
        $users = User::factory()->count(2)->create();
        $ids = $users->pluck('id')->all();
        $this->actingAs($admin, 'backend')->withSession(['backend.auth_version' => 1]);

        $this->postJson(route('frontend.user.restrictions'), [
            'user_ids' => $ids, 'type' => 'comment', 'restricted' => true, 'reason' => 'Comment abuse',
        ])->assertOk()->assertJsonPath('data.count', 2);
        $restrictions = app(UserRestrictionService::class);
        foreach ($users as $user) {
            $fresh = $user->fresh();
            $this->assertTrue($fresh->active);
            $this->assertSame(1, $fresh->auth_version);
            $this->assertTrue($fresh->canAuthenticate());
            $this->assertTrue($restrictions->has($fresh, 'comment'));
            $this->assertFalse($restrictions->allows($fresh, 'comment'));
            $this->assertDatabaseHas('user_restrictions', ['user_id' => $fresh->id, 'type' => 'comment', 'active_key' => $fresh->id.':comment']);
        }

        $this->postJson(route('frontend.user.restrictions'), [
            'user_ids' => $ids, 'type' => 'comment', 'restricted' => true, 'reason' => 'Duplicate request',
        ])->assertOk()->assertJsonPath('data.count', 0);
        $this->assertSame(1, $users->first()->restrictions()->where('type', 'comment')->count());
        $this->getJson(route('frontend.user.restriction', ['id' => $ids[0], 'type' => 'comment']))
            ->assertOk()->assertJsonPath('data.restricted', true);
        $this->getJson(route('frontend.user.restrictionTypes'))->assertOk()->assertJsonCount(2, 'data');

        $this->actingAs($users->first(), 'web')->withSession(['web.auth_version' => 1]);
        $this->getJson(route('restriction.check', ['type' => 'comment']))->assertOk()->assertJsonPath('data.restricted', true);
        $this->actingAs($admin, 'backend')->withSession(['backend.auth_version' => 1]);
        $this->postJson(route('frontend.user.restrictions'), [
            'user_ids' => $ids, 'type' => 'comment', 'restricted' => false, 'reason' => 'Reviewed',
        ])->assertOk()->assertJsonPath('data.count', 2);
        $this->assertSame(1, $users->first()->restrictions()->where('type', 'comment')->whereNotNull('lifted_at')->count());

        $this->postJson(route('frontend.user.restrictions'), [
            'user_ids' => [$ids[0]], 'type' => 'login', 'restricted' => true, 'reason' => 'Security review',
        ])->assertOk()->assertJsonPath('data.count', 1);
        $this->assertFalse($users->first()->fresh()->canAuthenticate());
        $this->assertTrue($users->last()->fresh()->canAuthenticate());
        $this->postJson(route('frontend.user.restrictions'), [
            'user_ids' => $ids, 'type' => 'unknown', 'restricted' => true, 'reason' => 'Invalid',
        ])->assertUnprocessable();
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
        $user = User::factory()->create();
        $this->actingAs($admin, 'backend')->withSession(['backend.auth_version' => 1]);

        $this->getJson(route('frontend.user.list'))->assertForbidden();
        $this->getJson(route('frontend.user.logs', $user))->assertForbidden();
        $this->postJson(route('frontend.user.restrictions'), ['user_ids' => [$user->id], 'type' => 'comment', 'restricted' => true, 'reason' => 'Denied'])->assertForbidden();
        $this->getJson(route('frontend.user.restriction', ['id' => $user->id, 'type' => 'comment']))->assertForbidden();
    }
}
