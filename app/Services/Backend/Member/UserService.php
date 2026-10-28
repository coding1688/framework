<?php

namespace App\Services\Backend\Member;

use App\Libs\Notifications\NotificationManager;
use App\Models\Member\User;
use App\Services\Backend\BaseService;
use App\Services\Backend\Common\Audit;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class UserService extends BaseService
{
    public function __construct(private readonly NotificationManager $notifications, private readonly MemberVipService $memberships) {}

    public function list(array $validated): LengthAwarePaginator
    {
        $this->authorize('frontend.user.list');
        $query = User::with(['socialAccounts', 'vipLevel'])->latest('id');
        if ($q = $validated['q'] ?? null) {
            $query->where(fn ($query) => $query->where('name', 'like', '%'.$q.'%')->orWhere('username', 'like', '%'.$q.'%')->orWhere('email', 'like', '%'.$q.'%'));
        }
        if (isset($validated['active'])) {
            $query->where('active', $validated['active']);
        }
        if (isset($validated['verified'])) {
            $validated['verified'] ? $query->whereNotNull('email_verified_at') : $query->whereNull('email_verified_at');
        }
        if (isset($validated['vip_level_id'])) {
            $query->where('vip_level_id', $validated['vip_level_id']);
        }
        if ($from = $validated['date_from'] ?? null) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $validated['date_to'] ?? null) {
            $query->whereDate('created_at', '<=', $to);
        }

        return $query->paginate(20, ['*'], 'page', (int) ($validated['page'] ?? 1));
    }

    public function detail(int $id): User
    {
        $this->authorize('frontend.user.detail');

        return User::with(['socialAccounts', 'vipLevel'])->findOrFail($id);
    }

    public function update(int $id, array $validated): User
    {
        $this->authorize('frontend.user.update');

        return DB::transaction(function () use ($id, $validated): User {
            $user = User::lockForUpdate()->findOrFail($id);
            if (strtolower($validated['email']) !== $user->email) {
                $user->email_verified_at = null;
                $user->auth_version++;
            }
            $user->fill($validated)->save();
            Audit::record('frontend.user.update', (string) $user->id, ['fields' => array_keys($validated)]);

            return $user->fresh()->load(['socialAccounts', 'vipLevel']);
        });
    }

    public function status(int $id, array $validated): User
    {
        $this->authorize('frontend.user.status');

        return DB::transaction(function () use ($id, $validated): User {
            $user = User::lockForUpdate()->findOrFail($id);
            $user->forceFill(['active' => $validated['active'], 'auth_version' => $user->auth_version + 1])->save();
            Audit::record('frontend.user.status', (string) $user->id, ['active' => $user->active]);
            $this->notifications->send($user, 'security.account_status', ['status' => $user->active ? 'enabled' : 'disabled']);

            return $user->fresh()->load(['socialAccounts', 'vipLevel']);
        });
    }

    public function vip(int $id, array $validated): User
    {
        $this->authorize('frontend.user.vip');
        $user = $this->memberships->update($id, $validated);
        Audit::record('frontend.user.vip', (string) $id, ['vip_level_id' => $user->vip_level_id, 'vip_expires_at' => $user->vip_expires_at?->toIso8601String()]);

        return $user;
    }

    public function vipOptions(): array
    {
        $this->authorize('frontend.user.vipOptions');

        return $this->memberships->options();
    }

    public function vipHistory(int $id, array $validated): LengthAwarePaginator
    {
        $this->authorize('frontend.user.vipHistory');
        User::findOrFail($id);

        return $this->memberships->history($id, $validated);
    }

    public function password(int $id, array $validated): void
    {
        $this->authorize('frontend.user.password');
        DB::transaction(function () use ($id, $validated): void {
            $user = User::lockForUpdate()->findOrFail($id);
            $user->forceFill([
                'password' => $validated['password'], 'password_changed_at' => now(),
                'auth_version' => $user->auth_version + 1,
            ])->save();
            Audit::record('frontend.user.password', (string) $user->id);
        });
    }

    public function verification(int $id): void
    {
        $this->authorize('frontend.user.verification');
        $user = User::findOrFail($id);
        if (! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }
        Audit::record('frontend.user.verification', (string) $user->id);
    }
}
