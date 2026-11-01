<?php

namespace App\Services\Backend\Member;

use App\Libs\Notifications\NotificationManager;
use App\Models\Member\User;
use App\Models\Member\UserRestriction;
use App\Services\Backend\BaseService;
use App\Services\Backend\Common\Audit;
use App\Services\Member\UserLogService;
use App\Services\Member\UserRestrictionService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserService extends BaseService
{
    public function __construct(private readonly NotificationManager $notifications, private readonly MemberVipService $memberships, private readonly UserLogService $userLogs, private readonly UserRestrictionService $restrictionService) {}

    public function list(array $validated): LengthAwarePaginator
    {
        $this->authorize('frontend.user.list');
        $query = User::with(['profile', 'socialAccounts', 'vipLevel', 'activeFreeze', 'wallets'])->latest('id');
        if ($q = $validated['q'] ?? null) {
            $query->where(fn ($query) => $query->whereHas('profile', fn ($profile) => $profile->where('nickname', 'like', '%'.$q.'%'))->orWhere('username', 'like', '%'.$q.'%')->orWhere('email', 'like', '%'.$q.'%'));
        }
        if (isset($validated['active'])) {
            $query->where('active', $validated['active']);
        }
        if (isset($validated['is_test'])) {
            $query->where('is_test', $validated['is_test']);
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

        return User::with(['profile', 'socialAccounts', 'vipLevel', 'activeFreeze', 'wallets'])->findOrFail($id);
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
            $user->fill([
                'username' => $validated['username'], 'email' => $validated['email'], 'is_test' => $validated['is_test'] ?? $user->is_test,
                'remark' => array_key_exists('remark', $validated) ? $validated['remark'] : $user->remark,
                'locale' => $validated['locale'] ?? $user->locale,
                'default_currency' => $validated['default_currency'] ?? $user->default_currency,
            ])->save();
            $user->profile->fill(['nickname' => $validated['name']])->save();
            Audit::record('frontend.user.update', (string) $user->id, ['fields' => array_keys($validated)]);
            $this->recordAdminAction($user, 'admin.updated', ['fields' => array_keys($validated)]);

            return $user->fresh()->load(['profile', 'socialAccounts', 'vipLevel', 'activeFreeze', 'wallets']);
        });
    }

    public function status(int $id, array $validated): User
    {
        $this->authorize('frontend.user.status');

        return DB::transaction(function () use ($id, $validated): User {
            $user = User::lockForUpdate()->findOrFail($id);
            $active = (bool) $validated['active'];
            $actor = Auth::guard('backend')->user()?->name ?? 'system';
            $changed = $this->restrictionService->change($user, UserRestriction::TYPE_FREEZE, ! $active, $validated['reason'] ?? null, $actor);
            if (! $changed) {
                return $this->present($user);
            }
            Audit::record('frontend.user.status', (string) $user->id, ['active' => $user->active, 'reason' => $validated['reason'] ?? null]);
            $this->recordAdminAction($user, $active ? 'admin.unfrozen' : 'admin.frozen', ['reason' => $validated['reason'] ?? null]);
            $this->notifications->send($user, 'security.account_status', ['status' => $user->active ? 'enabled' : 'disabled']);

            return $this->present($user);
        });
    }

    public function restrictions(array $validated): array
    {
        $this->authorize('frontend.user.restrictions');

        return DB::transaction(function () use ($validated): array {
            $users = User::whereIn('id', $validated['user_ids'])->orderBy('id')->lockForUpdate()->get();
            if ($users->count() !== count($validated['user_ids'])) {
                throw ValidationException::withMessages(['user_ids' => [__('validation.exists', ['attribute' => 'user ids'])]]);
            }
            $type = $validated['type'];
            $restricted = (bool) $validated['restricted'];
            $actor = Auth::guard('backend')->user()?->name ?? 'system';
            $changed = 0;
            foreach ($users as $user) {
                if (! $this->restrictionService->change($user, $type, $restricted, $validated['reason'] ?? null, $actor)) {
                    continue;
                }
                $changed++;
                Audit::record('frontend.user.restrictions', (string) $user->id, ['type' => $type, 'restricted' => $restricted, 'reason' => $validated['reason'] ?? null]);
                $this->recordAdminAction($user, 'restriction.'.$type.($restricted ? '.applied' : '.lifted'), ['reason' => $validated['reason'] ?? null]);
                if ($this->restrictionService->revokesSessions($type)) {
                    $this->notifications->send($user, 'security.account_status', ['status' => $user->active ? 'enabled' : 'disabled']);
                }
            }

            return ['count' => $changed, 'type' => $type, 'restricted' => $restricted];
        });
    }

    public function restrictionTypes(): array
    {
        $this->authorize('frontend.user.restrictions');

        return collect($this->restrictionService->types())->map(fn (array $definition, string $type): array => [
            'type' => $type, 'capabilities' => $definition['capabilities'],
        ])->values()->all();
    }

    public function restriction(int $id, string $type): array
    {
        $this->authorize('frontend.user.restriction');

        return ['type' => $type, 'restricted' => $this->restrictionService->has(User::findOrFail($id), $type)];
    }

    public function vip(int $id, array $validated): User
    {
        $this->authorize('frontend.user.vip');
        $user = $this->memberships->update($id, $validated);
        Audit::record('frontend.user.vip', (string) $id, ['vip_level_id' => $user->vip_level_id, 'vip_expires_at' => $user->vip_expires_at?->toIso8601String()]);
        $this->recordAdminAction($user, 'admin.vip_changed', ['vip_level_id' => $user->vip_level_id, 'reason' => $validated['reason'] ?? null]);

        return $this->present($user);
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

    public function logs(int $id, array $validated): LengthAwarePaginator
    {
        $this->authorize('frontend.user.logs');

        return $this->userLogs->history(User::findOrFail($id), $validated);
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
            $this->recordAdminAction($user, 'admin.password_reset');
        });
    }

    public function verification(int $id): void
    {
        $this->authorize('frontend.user.verification');
        $user = User::findOrFail($id);
        if (! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
            $this->recordAdminAction($user, 'admin.verification_sent');
        }
        Audit::record('frontend.user.verification', (string) $user->id);
    }

    private function present(User $user): User
    {
        return $user->fresh()->load(['profile', 'socialAccounts', 'vipLevel', 'activeFreeze', 'wallets']);
    }

    private function recordAdminAction(User $user, string $action, array $context = []): void
    {
        $this->userLogs->record($user, $action, 'admin', Auth::guard('backend')->user()?->name ?? 'system', $context);
    }
}
