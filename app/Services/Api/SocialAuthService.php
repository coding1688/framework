<?php

namespace App\Services\Api;

use App\Models\Member\SocialAccount;
use App\Models\Member\User;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class SocialAuthService extends BaseService
{
    public function __construct(private readonly Session $session) {}

    public function authorizationUrl(string $provider, bool $binding = false): string
    {
        $definition = $this->definition($provider);
        if ($binding) {
            $definition['redirect_uri'] = route('oauth.bind.callback', ['provider' => $provider]);
        }
        $state = Str::random(64);
        $this->session->put('oauth.'.($binding ? 'bind.' : '').$provider.'.state', $state);

        return $definition['authorize_url'].'?'.http_build_query([
            'client_id' => $definition['client_id'],
            'redirect_uri' => $definition['redirect_uri'],
            'response_type' => 'code',
            'scope' => $definition['scope'],
            'state' => $state,
        ]);
    }

    public function login(string $provider, ?string $code, ?string $state): User
    {
        $identity = $this->callbackIdentity($provider, $code, $state);
        $user = DB::transaction(fn (): User => $this->resolveUser($provider, $identity));
        Auth::guard('web')->login($user);
        $this->session->regenerate();
        $this->session->put('web.auth_version', $user->auth_version);

        return $user->load('socialAccounts');
    }

    public function bind(User $user, string $provider, ?string $code, ?string $state): User
    {
        $identity = $this->callbackIdentity($provider, $code, $state, true);
        DB::transaction(function () use ($user, $provider, $identity): void {
            $exists = SocialAccount::where('provider', $provider)
                ->where(fn ($query) => $query->where('provider_id', $identity['id'])->orWhere('user_id', $user->id))
                ->lockForUpdate()->exists();
            if ($exists) {
                throw ValidationException::withMessages(['provider' => __('auth.oauth_already_linked')]);
            }
            SocialAccount::create(['user_id' => $user->id, 'provider' => $provider, 'provider_id' => $identity['id']]);
        });

        return $user->fresh()->load('socialAccounts');
    }

    public function unlink(User $user, string $provider): User
    {
        abort_unless(in_array($provider, ['google', 'facebook'], true), 404);
        $account = $user->socialAccounts()->where('provider', $provider)->firstOrFail();
        if (! $user->password && $user->socialAccounts()->count() <= 1) {
            throw ValidationException::withMessages(['provider' => __('auth.oauth_last_login')]);
        }
        $account->delete();

        return $user->fresh()->load('socialAccounts');
    }

    /** @return array{id: string, email: string, name: string, avatar: ?string} */
    private function callbackIdentity(string $provider, ?string $code, ?string $state, bool $binding = false): array
    {
        $definition = $this->definition($provider);
        if ($binding) {
            $definition['redirect_uri'] = route('oauth.bind.callback', ['provider' => $provider]);
        }
        $expectedState = $this->session->pull('oauth.'.($binding ? 'bind.' : '').$provider.'.state');
        if (! $code || ! $state || ! $expectedState || ! hash_equals($expectedState, $state)) {
            throw ValidationException::withMessages(['state' => __('auth.oauth_state')]);
        }
        $token = Http::asForm()->connectTimeout(10)->timeout(30)->post($definition['token_url'], [
            'client_id' => $definition['client_id'], 'client_secret' => $definition['client_secret'],
            'redirect_uri' => $definition['redirect_uri'], 'code' => $code, 'grant_type' => 'authorization_code',
        ])->throw()->json('access_token');
        if (! is_string($token) || $token === '') {
            throw ValidationException::withMessages(['code' => __('auth.oauth_failed')]);
        }
        $profile = Http::withToken($token)->acceptJson()->connectTimeout(10)->timeout(30)->get($definition['userinfo_url'])->throw()->json();

        return $this->identity($provider, $profile);
    }

    private function definition(string $provider): array
    {
        abort_unless(in_array($provider, ['google', 'facebook'], true), 404);
        $config = config('services.'.$provider);
        if (empty($config['client_id']) || empty($config['client_secret'])) {
            throw new ServiceUnavailableHttpException(message: __('auth.oauth_not_configured'));
        }
        $redirect = $config['redirect'] ?: route('oauth.callback', ['provider' => $provider]);

        return match ($provider) {
            'google' => [
                ...$config, 'redirect_uri' => $redirect,
                'authorize_url' => 'https://accounts.google.com/o/oauth2/v2/auth',
                'token_url' => 'https://oauth2.googleapis.com/token',
                'userinfo_url' => 'https://openidconnect.googleapis.com/v1/userinfo',
                'scope' => 'openid email profile',
            ],
            'facebook' => [
                ...$config, 'redirect_uri' => $redirect,
                'authorize_url' => 'https://www.facebook.com/'.$config['version'].'/dialog/oauth',
                'token_url' => 'https://graph.facebook.com/'.$config['version'].'/oauth/access_token',
                'userinfo_url' => 'https://graph.facebook.com/'.$config['version'].'/me?fields=id,name,email,picture.type(large)',
                'scope' => 'email,public_profile',
            ],
        };
    }

    private function identity(string $provider, array $profile): array
    {
        $id = $provider === 'google' ? ($profile['sub'] ?? null) : ($profile['id'] ?? null);
        if (! is_string($id) || $id === '') {
            throw ValidationException::withMessages(['provider' => __('auth.oauth_failed')]);
        }
        $email = strtolower((string) ($profile['email'] ?? ''));
        $verified = $provider === 'google' ? (bool) ($profile['email_verified'] ?? false) : $email !== '';
        if ($email === '' || ! $verified) {
            throw ValidationException::withMessages(['email' => __('auth.oauth_email_unverified')]);
        }

        return [
            'id' => $id,
            'email' => $email,
            'name' => (string) ($profile['name'] ?? ''),
            'avatar' => $provider === 'google' ? ($profile['picture'] ?? null) : ($profile['picture']['data']['url'] ?? null),
        ];
    }

    private function resolveUser(string $provider, array $identity): User
    {
        $account = SocialAccount::with('user')->where('provider', $provider)->where('provider_id', $identity['id'])->first();
        if ($account) {
            if (! $account->user->active) {
                throw ValidationException::withMessages(['login' => __('auth.failed')]);
            }

            return $account->user;
        }
        $user = User::where('email', $identity['email'])->first();
        if ($user && ! $user->active) {
            throw ValidationException::withMessages(['login' => __('auth.failed')]);
        }
        if ($user) {
            throw ValidationException::withMessages(['email' => __('auth.oauth_account_exists')]);
        }
        $user = new User([
            'username' => $this->uniqueUsername($identity['email']),
            'name' => mb_substr($identity['name'] ?: Str::before($identity['email'], '@'), 0, 128),
            'email' => $identity['email'],
            'vip_level' => 0,
        ]);
        $user->forceFill([
            'avatar_url' => $user->avatar_url ?: ($identity['avatar'] ? mb_substr($identity['avatar'], 0, 512) : null),
            'email_verified_at' => $user->email_verified_at ?: now(),
        ])->save();
        SocialAccount::create(['user_id' => $user->id, 'provider' => $provider, 'provider_id' => $identity['id']]);

        return $user;
    }

    private function uniqueUsername(string $email): string
    {
        $base = strtolower(preg_replace('/[^a-z0-9_.-]+/i', '-', Str::before($email, '@')) ?? '');
        $base = mb_substr(trim($base, '.-_'), 0, 24);
        if (mb_strlen($base) < 3) {
            $base = 'user';
        }
        $username = $base;
        $suffix = 1;
        while (User::where('username', $username)->exists()) {
            $username = mb_substr($base, 0, 24).'-'.$suffix++;
        }

        return $username;
    }
}
