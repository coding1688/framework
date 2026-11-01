<?php

namespace Tests\Feature;

use App\Models\Member\SocialAccount;
use App\Models\Member\User;
use App\Models\Member\UserLog;
use App\Services\Member\UserLogService;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class UserSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_defaults_and_username_or_email_login(): void
    {
        Notification::fake();
        $response = $this->postJson(route('register'), [
            'username' => 'reader',
            'name' => 'Reader',
            'email' => 'reader@example.com',
            'password' => 'SecurePass123',
            'password_confirmation' => 'SecurePass123',
        ])->assertCreated()
            ->assertJsonPath('data.username', 'reader')
            ->assertJsonPath('data.vip_level', 0)
            ->assertJsonPath('data.email_verified', false);
        $user = User::findOrFail($response->json('data.id'));

        $this->assertStringContainsString('default-avatar.svg', $response->json('data.avatar'));
        $this->assertSame('Reader', $user->profile->nickname);
        $this->assertArrayNotHasKey('name', $user->getAttributes());
        $this->assertDatabaseHas('user_profiles', ['user_id' => $user->id, 'nickname' => 'Reader']);
        $this->assertAuthenticatedAs($user, 'web');
        Notification::assertSentTo($user, VerifyEmail::class);

        $this->postJson(route('logout'))->assertOk();
        $this->assertGuest('web');
        $this->postJson(route('login'), ['login' => 'reader', 'password' => 'SecurePass123'])->assertOk()->assertJsonPath('data.username', 'reader');
        $this->postJson(route('logout'))->assertOk();
        $this->postJson(route('login'), ['login' => 'reader@example.com', 'password' => 'SecurePass123'])->assertOk()->assertJsonPath('data.email_verified', false);
        $this->getJson(route('user'))->assertOk()->assertJsonPath('data.id', $user->id);
    }

    public function test_authentication_actions_create_user_logs_without_sensitive_data(): void
    {
        Notification::fake();
        $response = $this->withHeader('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 19_0 like Mac OS X) Mobile')->postJson(route('register'), [
            'username' => 'audit_reader', 'name' => 'Audit Reader', 'email' => 'audit@example.com',
            'password' => 'SecurePass123', 'password_confirmation' => 'SecurePass123',
        ])->assertCreated();
        $user = User::findOrFail($response->json('data.id'));

        $this->postJson(route('logout'))->assertOk();
        $this->postJson(route('login'), ['login' => 'audit_reader', 'password' => 'WrongPass123'])->assertUnprocessable();
        $this->postJson(route('login'), ['login' => 'audit_reader', 'password' => 'SecurePass123'])->assertOk();

        $this->assertSame(['auth.register', 'auth.logout', 'auth.login_failed', 'auth.login'], $user->logs()->orderBy('id')->pluck('action')->all());
        $this->assertSame(UserLog::DEVICE_TYPE_IOS, $user->fresh()->last_login_device_type);
        $registration = $user->logs()->where('action', 'auth.register')->sole();
        $this->assertSame(UserLog::DEVICE_TYPE_IOS, $registration->device_type);
        $this->assertNotNull($registration->ip);
        $this->assertNotNull($registration->created_at);
        $this->assertStringContainsString('iPhone', $registration->user_agent);
        $this->assertStringNotContainsString('SecurePass123', json_encode($user->logs()->get()->toArray()));
        $this->assertStringNotContainsString('WrongPass123', json_encode($user->logs()->get()->toArray()));
    }

    public function test_device_types_are_detected_using_defined_constants(): void
    {
        $service = app(UserLogService::class);
        $cases = [
            UserLog::DEVICE_TYPE_WEB => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/140.0 Safari/537.36',
            UserLog::DEVICE_TYPE_H5 => 'Mozilla/5.0 Mobile Safari/537.36',
            UserLog::DEVICE_TYPE_ANDROID => 'Mozilla/5.0 (Linux; Android 15) AppleWebKit/537.36 Mobile Safari/537.36',
            UserLog::DEVICE_TYPE_IOS => 'Mozilla/5.0 (iPhone; CPU iPhone OS 19_0 like Mac OS X) Mobile',
            UserLog::DEVICE_TYPE_HARMONY => 'Mozilla/5.0 (Linux; HarmonyOS 5.0) AppleWebKit/537.36 Mobile Safari/537.36',
        ];

        foreach ($cases as $expected => $userAgent) {
            $this->assertSame($expected, $service->deviceType($userAgent));
        }
    }

    public function test_signed_email_verification_marks_the_account_verified(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();
        $this->actingAs($user, 'web');
        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $user->id, 'hash' => sha1($user->email)]);

        $this->getJson($url)->assertOk()->assertJsonPath('data.email_verified', true);
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->postJson(route('verification.send'))->assertOk();
        Notification::assertNothingSentTo($user);
    }

    public function test_google_login_accepts_an_already_linked_account(): void
    {
        $this->configureProvider('google');
        $user = User::factory()->create(['email' => 'existing@example.com']);
        SocialAccount::create(['user_id' => $user->id, 'provider' => 'google', 'provider_id' => 'google-user-1']);
        $state = $this->oauthState('google');
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'google-token']),
            'https://openidconnect.googleapis.com/v1/userinfo' => Http::response([
                'sub' => 'google-user-1', 'name' => 'Existing User', 'email' => 'existing@example.com', 'email_verified' => true, 'picture' => 'https://images.example.com/google.jpg',
            ]),
        ]);

        $this->getJson(route('oauth.callback', ['provider' => 'google', 'code' => 'code', 'state' => $state]))
            ->assertOk()->assertJsonPath('data.id', $user->id)->assertJsonPath('data.providers.0', 'google');
        $this->assertAuthenticatedAs($user->fresh(), 'web');
    }

    public function test_google_login_rejects_an_unlinked_existing_email(): void
    {
        $this->configureProvider('google');
        $user = User::factory()->unverified()->create(['email' => 'existing@example.com']);
        $state = $this->oauthState('google');
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'google-token']),
            'https://openidconnect.googleapis.com/v1/userinfo' => Http::response([
                'sub' => 'google-user-1', 'name' => 'Existing User', 'email' => 'existing@example.com', 'email_verified' => true, 'picture' => 'https://images.example.com/google.jpg',
            ]),
        ]);

        $this->getJson(route('oauth.callback', ['provider' => 'google', 'code' => 'code', 'state' => $state]))
            ->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->assertGuest('web');
        $this->assertNull($user->fresh()->email_verified_at);
        $this->assertDatabaseMissing('social_accounts', ['user_id' => $user->id, 'provider' => 'google', 'provider_id' => 'google-user-1']);
    }

    public function test_facebook_login_creates_a_user_with_profile_defaults(): void
    {
        $this->configureProvider('facebook');
        $state = $this->oauthState('facebook');
        Http::fake([
            'https://graph.facebook.com/v23.0/oauth/access_token' => Http::response(['access_token' => 'facebook-token']),
            'https://graph.facebook.com/v23.0/me?fields=id,name,email,picture.type(large)' => Http::response([
                'id' => 'facebook-user-1', 'name' => 'Facebook User', 'email' => 'facebook@example.com', 'picture' => ['data' => ['url' => 'https://images.example.com/facebook.jpg']],
            ]),
        ]);

        $response = $this->getJson(route('oauth.callback', ['provider' => 'facebook', 'code' => 'code', 'state' => $state]))
            ->assertOk()->assertJsonPath('data.username', 'facebook')->assertJsonPath('data.vip_level', 0)->assertJsonPath('data.email_verified', true);
        $user = User::findOrFail($response->json('data.id'));
        $this->assertSame('https://images.example.com/facebook.jpg', $response->json('data.avatar'));
        $this->assertAuthenticatedAs($user, 'web');
        $this->assertSame(1, SocialAccount::where('user_id', $user->id)->where('provider', 'facebook')->count());
    }

    public function test_oauth_rejects_an_invalid_state(): void
    {
        $this->configureProvider('google');
        $this->oauthState('google');
        $this->getJson(route('oauth.callback', ['provider' => 'google', 'code' => 'code', 'state' => 'invalid']))->assertUnprocessable();
        $this->assertGuest('web');
    }

    public function test_oauth_rejects_an_unverified_provider_email(): void
    {
        $this->configureProvider('google');
        $state = $this->oauthState('google');
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'google-token']),
            'https://openidconnect.googleapis.com/v1/userinfo' => Http::response([
                'sub' => 'google-user-2', 'email' => 'unverified@example.com', 'email_verified' => false,
            ]),
        ]);

        $this->getJson(route('oauth.callback', ['provider' => 'google', 'code' => 'code', 'state' => $state]))->assertUnprocessable();
        $this->assertDatabaseMissing('users', ['email' => 'unverified@example.com']);
        $this->assertGuest('web');
    }

    private function configureProvider(string $provider): void
    {
        config()->set("services.$provider.client_id", 'test-client');
        config()->set("services.$provider.client_secret", 'test-secret');
        config()->set("services.$provider.redirect", null);
    }

    private function oauthState(string $provider): string
    {
        $this->get(route('oauth.redirect', ['provider' => $provider]))->assertRedirect();

        return session('oauth.'.$provider.'.state');
    }
}
