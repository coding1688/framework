<?php

namespace App\Services\Backend;

use App\Libs\Notifications\NotificationManager;
use App\Libs\Security\SecurityPassword;
use App\Models\Admin\AdminMenu;
use App\Models\Admin\AdminUser;
use App\Services\Backend\Common\Audit;
use App\Services\Backend\Common\MenuNavigation;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthService extends BaseService
{
    public function __construct(private Session $session, private MenuNavigation $navigation, private SecurityPassword $securityPasswords, private NotificationManager $notifications) {}

    public function login(array $data, string $ip, ?string $userAgent = null): AdminUser
    {
        $identity = strtolower(trim($data['email']));
        $key = 'backend:login:'.hash('sha256', $identity.'|'.$ip);
        $ipKey = 'backend:login-ip:'.hash('sha256', $ip);
        if (RateLimiter::tooManyAttempts($key, 5) || RateLimiter::tooManyAttempts($ipKey, 30)) {
            abort(429, __('backend/auth.throttled'));
        }
        RateLimiter::hit($key, 60);
        RateLimiter::hit($ipKey, 60);
        if (! Auth::guard('backend')->attempt(['email' => $identity, 'password' => $data['password'], 'active' => true])) {
            Audit::record('auth.failed');
            throw ValidationException::withMessages(['email' => __('backend/auth.credentials')]);
        }
        RateLimiter::clear($key);
        $this->session->regenerate();
        $admin = Auth::guard('backend')->user();
        $this->session->put('backend.auth_version', $admin->auth_version);
        $this->session->put('backend.locale', $admin->locale);
        $admin->forceFill(['last_login_at' => now(), 'last_login_ip' => $ip, 'last_login_location' => null, 'last_login_device' => $userAgent ? mb_substr($userAgent, 0, 512) : null])->save();
        Audit::record('auth.login', (string) $admin->id);

        return $admin;
    }

    public function logout(): string
    {
        Audit::record('auth.logout');
        Auth::guard('backend')->logout();
        $this->session->invalidate();
        $this->session->regenerateToken();

        return $this->session->token();
    }

    public function userinfo(): array
    {
        $admin = Auth::guard('backend')->user();
        $permissions = $admin->permissions();
        $navigation = $this->navigation->for($admin);
        $visibleNodes = [];
        $collect = function (array $nodes) use (&$collect, &$visibleNodes): void {
            foreach ($nodes as $node) {
                if ($node['path'] !== null) {
                    $visibleNodes[$node['id']] = $node;
                }
                $collect($node['children']);
            }
        };
        $collect($navigation);
        $menus = AdminMenu::with('navigationTarget')->whereIn('id', array_keys($visibleNodes))->orderBy('sort')->orderBy('id')->get();
        foreach ($menus as $menu) {
            $node = $visibleNodes[$menu->id];
            $menu->setAttribute('context', $node['context']);
            $menu->setAttribute('navigation_title', $node['title']);
            $menu->setAttribute('component', $node['component']);
        }

        return ['user' => $admin->load('department'), 'permissions' => $permissions, 'menus' => $menus, 'navigation' => $navigation];
    }

    public function preferences(array $validated): array
    {
        $admin = Auth::guard('backend')->user();
        $preferences = array_replace($admin->themePreferences(), $validated);
        $admin->forceFill(['preferences' => $preferences])->save();
        Audit::record('auth.preferences', (string) $admin->id, ['fields' => array_keys($validated)]);

        return $preferences;
    }

    public function securityPassword(array $data, string $ip): void
    {
        $admin = Auth::guard('backend')->user();
        $saved = DB::transaction(function () use ($admin, $data, $ip): AdminUser {
            $locked = AdminUser::lockForUpdate()->findOrFail($admin->id);
            $this->securityPasswords->authorizeChange($locked, $data['current_password'] ?? null, $data['current_security_password'] ?? null, 'backend', $ip);
            $this->securityPasswords->assertDistinct($locked, $data['security_password']);
            $locked->forceFill(['security_password' => $data['security_password'], 'security_password_changed_at' => now()])->save();
            Audit::record('auth.securityPassword', (string) $locked->id);

            return $locked;
        });
        Auth::guard('backend')->setUser($saved);
    }

    public function forgotPassword(array $data): void
    {
        $admin = AdminUser::where('email', strtolower(trim($data['email'])))->first();
        if ($admin) {
            Password::broker('backend')->sendResetLink(['email' => $admin->email]);
        }
    }

    public function resetPassword(array $data, ?string $ip = null): void
    {
        $status = Password::broker('backend')->reset($data, function (AdminUser $admin, string $password) use ($ip): void {
            $admin->forceFill([
                'password' => $password, 'password_changed_at' => now(), 'password_changed_ip' => $ip,
                'auth_version' => $admin->auth_version + 1,
            ])->save();
            Audit::record('auth.passwordReset', (string) $admin->id);
            $this->notifications->send($admin, 'security.password_reset');
        });
        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => [__($status)]]);
        }
    }

    public function password(array $data, ?string $ip = null): string
    {
        $admin = Auth::guard('backend')->user();
        $version = DB::transaction(function () use ($admin, $data, $ip): int {
            $locked = AdminUser::lockForUpdate()->findOrFail($admin->id);
            if (! Hash::check($data['current_password'], $locked->password)) {
                throw ValidationException::withMessages(['current_password' => __('backend/auth.credentials')]);
            }
            $locked->forceFill(['password' => $data['password'], 'password_changed_at' => now(), 'password_changed_ip' => $ip, 'auth_version' => $locked->auth_version + 1])->save();
            Audit::record('auth.password', (string) $admin->id);
            $this->notifications->send($locked, 'security.password_changed');

            return $locked->auth_version;
        });
        $this->session->put('backend.auth_version', $version);
        $this->session->regenerate();

        return $this->session->token();
    }
}
