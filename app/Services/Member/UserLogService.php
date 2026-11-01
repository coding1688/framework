<?php

namespace App\Services\Member;

use App\Models\Member\User;
use App\Models\Member\UserLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserLogService
{
    public function record(User $user, string $action, string $actorType = 'user', ?string $actor = null, array $context = []): UserLog
    {
        return $user->logs()->create([
            'action' => $action,
            'actor_type' => $actorType,
            'actor' => mb_substr($actor ?: $user->name, 0, 128),
            'ip' => request()->ip(),
            'user_agent' => mb_substr(request()->userAgent() ?? '', 0, 512),
            'device_type' => $this->deviceType(),
            'context' => $context ?: null,
            'created_at' => now(),
        ]);
    }

    public function deviceType(?string $userAgent = null): string
    {
        $agent = strtolower($userAgent ?? request()->userAgent() ?? '');
        if (preg_match('/harmonyos|openharmony/', $agent)) {
            return UserLog::DEVICE_TYPE_HARMONY;
        }
        if (preg_match('/iphone|ipad|ipod/', $agent)) {
            return UserLog::DEVICE_TYPE_IOS;
        }
        if (str_contains($agent, 'android')) {
            return UserLog::DEVICE_TYPE_ANDROID;
        }
        if (preg_match('/mobile|uni-app|html5plus/', $agent)) {
            return UserLog::DEVICE_TYPE_H5;
        }

        return UserLog::DEVICE_TYPE_WEB;
    }

    public function history(User $user, array $validated): LengthAwarePaginator
    {
        $query = $user->logs()->latest('id');
        if ($action = $validated['action'] ?? null) {
            $query->where('action', $action);
        }
        if ($from = $validated['date_from'] ?? null) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $validated['date_to'] ?? null) {
            $query->whereDate('created_at', '<=', $to);
        }

        return $query->paginate(20, ['*'], 'page', (int) ($validated['page'] ?? 1));
    }
}
