<?php

namespace App\Services\Api;

use App\Libs\File\Constants\FileType;
use App\Libs\File\FileManager;
use App\Libs\File\FileReferences;
use App\Libs\File\UploadConfiguration;
use App\Libs\Notifications\NotificationManager;
use App\Models\Common\File;
use App\Models\Member\User;
use App\Services\Member\UserLogService;
use App\Services\Member\UserRestrictionService;
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
    public function __construct(private readonly Session $session, private readonly FileManager $files, private readonly UploadConfiguration $uploads, private readonly NotificationManager $notifications, private readonly FileReferences $fileReferences, private readonly UserLogService $userLogs, private readonly UserRestrictionService $restrictionService) {}

    public function register(array $validated): User
    {
        $user = DB::transaction(function () use ($validated): User {
            $user = User::create([
                'username' => $validated['username'],
                'email' => strtolower($validated['email']),
                'password' => $validated['password'],
                'vip_level' => 0,
            ]);
            $user->profile()->create(['nickname' => $validated['name'] ?? $validated['username']]);

            return $user;
        });
        $user->sendEmailVerificationNotification();
        Auth::guard('web')->login($user);
        $this->session->regenerate();
        $this->session->put('web.auth_version', $user->auth_version);
        $this->userLogs->record($user, 'auth.register');

        return $user;
    }

    public function login(array $validated, ?string $ip = null, ?string $userAgent = null): User
    {
        $identity = trim($validated['login']);
        $user = User::where('username', $identity)->orWhere('email', strtolower($identity))->first();
        if (! $user || ! $user->canAuthenticate() || ! $user->password || ! Hash::check($validated['password'], $user->password)) {
            if ($user) {
                $this->userLogs->record($user, 'auth.login_failed');
            }
            throw ValidationException::withMessages(['login' => __('auth.failed')]);
        }
        Auth::guard('web')->login($user);
        $this->session->regenerate();
        $this->session->put('web.auth_version', $user->auth_version);
        $user->forceFill([
            'last_login_at' => now(), 'last_login_ip' => $ip,
            'last_login_device' => $userAgent ? mb_substr($userAgent, 0, 512) : null,
            'last_login_device_type' => $this->userLogs->deviceType($userAgent),
        ])->save();
        $this->userLogs->record($user, 'auth.login');

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
        $this->userLogs->record($resetUser, 'security.password_reset');

        return $resetUser;
    }

    public function profile(User $user, array $validated): User
    {
        $user->fill(['username' => $validated['username']])->save();
        $user->profile->fill(['nickname' => $validated['name']])->save();
        $this->userLogs->record($user, 'profile.updated', context: ['fields' => array_keys($validated)]);

        return $user->fresh()->load(['profile', 'socialAccounts']);
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
        $this->userLogs->record($user, 'security.password_changed');

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
        $this->userLogs->record($user, 'security.email_changed');

        return $user->fresh()->load('socialAccounts');
    }

    public function avatar(User $user, UploadedFile $uploaded): User
    {
        $user = DB::transaction(function () use ($user, $uploaded): User {
            $oldFileId = $this->fileReferences->idsFromUrls($user->avatar_url)[0] ?? null;
            $oldFile = $oldFileId === null ? null : File::find($oldFileId);
            $file = $this->files->store($uploaded, $this->uploads->publicDisk(), FileType::UserAvatar->value, 'public');
            $url = $file->publicUrl();
            if (! is_string($url) || $url === '') {
                throw ValidationException::withMessages(['avatar' => __('common/file/const.no_public_storage')]);
            }
            $user->profile->forceFill(['avatar_url' => mb_substr($url, 0, 512)])->save();
            $this->fileReferences->syncModel($user);
            if ($oldFile !== null && ! $oldFile->references()->exists()) {
                $this->files->delete($oldFile);
            }

            return $user->fresh()->load(['profile', 'socialAccounts']);
        });
        $this->userLogs->record($user, 'profile.avatar_updated');

        return $user;
    }

    public function restriction(User $user, string $type): array
    {
        return ['type' => $type, 'restricted' => $this->restrictionService->has($user, $type)];
    }

    public function logout(): void
    {
        $user = Auth::guard('web')->user();
        if ($user) {
            $this->userLogs->record($user, 'auth.logout');
        }
        Auth::guard('web')->logout();
        $this->session->invalidate();
        $this->session->regenerateToken();
    }

    public function resendVerification(User $user): void
    {
        if (! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
            $this->userLogs->record($user, 'security.verification_sent');
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
            $this->userLogs->record($user, 'security.email_verified');
        }

        return $user;
    }
}
