<?php

namespace App\Services\Api;

use App\Libs\File\Constants\FileType;
use App\Libs\File\FileManager;
use App\Libs\File\UploadConfiguration;
use App\Libs\Notifications\NotificationManager;
use App\Models\Member\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class AuthService extends BaseService
{
    public function __construct(private readonly Session $session, private readonly FileManager $files, private readonly UploadConfiguration $uploads, private readonly NotificationManager $notifications) {}

    public function register(array $validated): User
    {
        $user = DB::transaction(fn (): User => User::create([
            'username' => $validated['username'],
            'name' => $validated['name'] ?? $validated['username'],
            'email' => strtolower($validated['email']),
            'password' => $validated['password'],
            'vip_level' => 0,
        ]));
        $user->sendEmailVerificationNotification();
        Auth::guard('web')->login($user);
        $this->session->regenerate();
        $this->session->put('web.auth_version', $user->auth_version);

        return $user;
    }

    public function login(array $validated, ?string $ip = null, ?string $userAgent = null): User
    {
        $identity = trim($validated['login']);
        $user = User::where('username', $identity)->orWhere('email', strtolower($identity))->first();
        if (! $user || ! $user->active || ! $user->password || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages(['login' => __('auth.failed')]);
        }
        Auth::guard('web')->login($user);
        $this->session->regenerate();
        $this->session->put('web.auth_version', $user->auth_version);
        $user->forceFill([
            'last_login_at' => now(), 'last_login_ip' => $ip,
            'last_login_device' => $userAgent ? mb_substr($userAgent, 0, 512) : null,
        ])->save();

        return $user;
    }

    public function forgotPassword(array $validated): void
    {
        $user = User::where('email', strtolower($validated['email']))->first();
        if ($user) {
            Password::broker('users')->sendResetLink(['email' => $user->email]);
        }
    }

    public function resetPassword(array $validated): User
    {
        $resetUser = null;
        $status = Password::broker('users')->reset($validated, function (User $user, string $password) use (&$resetUser): void {
            $user->forceFill([
                'password' => $password, 'password_changed_at' => now(),
                'auth_version' => $user->auth_version + 1,
            ])->save();
            $resetUser = $user;
        });
        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => [__($status)]]);
        }
        $this->notifications->send($resetUser, 'security.password_reset');

        return $resetUser;
    }

    public function profile(User $user, array $validated): User
    {
        $user->fill($validated)->save();

        return $user->fresh()->load('socialAccounts');
    }

    public function password(User $user, array $validated): string
    {
        if ($user->password && ! Hash::check($validated['current_password'] ?? '', $user->password)) {
            throw ValidationException::withMessages(['current_password' => __('auth.failed')]);
        }
        $user->forceFill([
            'password' => $validated['password'], 'password_changed_at' => now(),
            'auth_version' => $user->auth_version + 1,
        ])->save();
        $this->notifications->send($user, 'security.password_changed');
        $this->session->put('web.auth_version', $user->auth_version);
        $this->session->regenerate();

        return $this->session->token();
    }

    public function email(User $user, array $validated): User
    {
        if ($user->password && ! Hash::check($validated['current_password'] ?? '', $user->password)) {
            throw ValidationException::withMessages(['current_password' => __('auth.failed')]);
        }
        $user->forceFill(['email' => strtolower($validated['email']), 'email_verified_at' => null])->save();
        $this->notifications->send($user, 'security.email_changed', ['email' => $user->email]);
        $user->sendEmailVerificationNotification();

        return $user->fresh()->load('socialAccounts');
    }

    public function avatar(User $user, UploadedFile $uploaded): User
    {
        $file = $this->files->store($uploaded, $this->uploads->publicDisk(), FileType::UserAvatar->value, 'public');
        $url = $file->publicUrl();
        if (! is_string($url) || $url === '') {
            throw ValidationException::withMessages(['avatar' => __('common/file/const.no_public_storage')]);
        }
        $user->forceFill(['avatar_url' => mb_substr($url, 0, 512)])->save();

        return $user->fresh()->load('socialAccounts');
    }

    public function logout(): void
    {
        Auth::guard('web')->logout();
        $this->session->invalidate();
        $this->session->regenerateToken();
    }

    public function resendVerification(User $user): void
    {
        if (! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }
    }

    public function verifyEmail(User $user, int $id, string $hash): User
    {
        abort_unless($user->id === $id, 403);
        abort_unless(hash_equals(sha1($user->email), $hash), 403);
        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            $user->save();
            event(new Verified($user));
        }

        return $user;
    }
}
