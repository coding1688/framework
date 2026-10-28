<?php

namespace App\Services\Api;

use App\Models\Member\User;

class NotificationService extends BaseService
{
    public function list(User $user, array $validated): array
    {
        $page = $user->notifications()->latest('created_at')->paginate(20, ['*'], 'page', (int) ($validated['page'] ?? 1));

        return ['page' => $page, 'unread' => $user->unreadNotifications()->count(), 'preferences' => $user->notification_preferences ?? (object) []];
    }

    public function read(User $user, string $id): void
    {
        $notification = $user->notifications()->whereKey($id)->firstOrFail();
        $notification->markAsRead();
    }

    public function readAll(User $user): void
    {
        $user->unreadNotifications()->update(['read_at' => now()]);
    }

    public function preferences(User $user, array $validated): array
    {
        $user->forceFill(['notification_preferences' => $validated['preferences']])->save();

        return $user->notification_preferences;
    }
}
