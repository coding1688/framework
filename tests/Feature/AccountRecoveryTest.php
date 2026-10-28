<?php

namespace Tests\Feature;

use App\Models\Admin\AdminUser;
use App\Models\Member\User;
use App\Notifications\ResetAdminPasswordNotification;
use App\Notifications\ResetUserPasswordNotification;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AccountRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_does_not_reveal_account_existence(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->postJson(route('password.forgot'), ['email' => $user->email])->assertOk();
        $this->postJson(route('password.forgot'), ['email' => 'missing@example.com'])->assertOk();

        Notification::assertSentTo($user, ResetUserPasswordNotification::class);
        Notification::assertCount(1);
    }

    public function test_user_can_reset_password_once_and_old_token_is_rejected(): void
    {
        $user = User::factory()->create(['password' => 'OldSecurePass123']);
        $token = Password::broker('users')->createToken($user);
        $payload = [
            'email' => $user->email, 'token' => $token,
            'password' => 'NewSecurePass123', 'password_confirmation' => 'NewSecurePass123',
        ];

        $this->postJson(route('password.reset'), $payload)->assertOk();
        $this->postJson(route('password.reset'), $payload)->assertUnprocessable();
        $this->assertTrue(Hash::check('NewSecurePass123', $user->fresh()->password));
        $this->assertSame(2, $user->fresh()->auth_version);
    }

    public function test_profile_email_and_password_changes_update_account_security(): void
    {
        Notification::fake();
        $user = User::factory()->create(['password' => 'OldSecurePass123']);
        $this->actingAs($user, 'web')->withSession(['web.auth_version' => 1]);

        $this->postJson(route('profile.update'), ['username' => 'updated_name', 'name' => 'Updated'])
            ->assertOk()->assertJsonPath('data.username', 'updated_name');
        $this->postJson(route('email.update'), ['email' => 'updated@example.com', 'current_password' => 'OldSecurePass123'])
            ->assertOk()->assertJsonPath('data.email_verified', false);
        Notification::assertSentTo($user, VerifyEmail::class);
        $this->postJson(route('password.update'), [
            'current_password' => 'OldSecurePass123',
            'password' => 'NewSecurePass123', 'password_confirmation' => 'NewSecurePass123',
        ])->assertOk()->assertJsonPath('csrf_token', fn ($token) => is_string($token));

        $this->assertSame(2, $user->fresh()->auth_version);
        $this->assertTrue(Hash::check('NewSecurePass123', $user->fresh()->password));
        $this->assertSame('updated@example.com', $user->fresh()->email);
    }

    public function test_disabled_or_version_mismatched_account_is_logged_out(): void
    {
        $user = User::factory()->create(['active' => false]);
        $this->actingAs($user, 'web')->withSession(['web.auth_version' => 1]);
        $this->getJson(route('user'))->assertUnauthorized();

        $active = User::factory()->create(['auth_version' => 2]);
        $this->actingAs($active, 'web')->withSession(['web.auth_version' => 1]);
        $this->getJson(route('user'))->assertUnauthorized();
    }

    public function test_administrator_can_reset_password_with_backend_broker(): void
    {
        Notification::fake();
        $admin = AdminUser::factory()->create();
        $this->postJson(route('backend.auth.forgotPassword'), ['email' => $admin->email])->assertOk();
        Notification::assertSentTo($admin, ResetAdminPasswordNotification::class);

        $token = Password::broker('backend')->createToken($admin);
        $this->postJson(route('backend.auth.resetPassword'), [
            'email' => $admin->email, 'token' => $token,
            'password' => 'NewSecurePass123', 'password_confirmation' => 'NewSecurePass123',
        ])->assertOk();

        $this->assertTrue(Hash::check('NewSecurePass123', $admin->fresh()->password));
        $this->assertSame(2, $admin->fresh()->auth_version);
        $this->assertDatabaseHas('admin_logs', ['action' => 'auth.passwordReset', 'subject' => (string) $admin->id]);
    }
}
