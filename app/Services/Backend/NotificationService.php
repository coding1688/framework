<?php

namespace App\Services\Backend;

use App\Models\Admin\AdminUser;

class NotificationService extends BaseService
{
    public function list(AdminUser $user, array $validated): array
    {
        $page = $user->notifications()->latest('created_at')->paginate(20, ['*'], 'page', (int) ($validated['page'] ?? 1));

        return ['page' => $page, 'unread' => $user->unreadNotifications()->count(), 'preferences' => $user->notification_preferences ?? (object) []];
    }

    public function read(AdminUser $user, string $id): void
    {
        $user->notifications()->whereKey($id)->firstOrFail()->markAsRead();
    }

    public function readAll(AdminUser $user): void
    {
        $user->unreadNotifications()->update(['read_at' => now()]);
    }

    public function preferences(AdminUser $user, array $validated): array
    {
        $user->forceFill(['notification_preferences' => $validated['preferences']])->save();

        return $user->notification_preferences;
    }
}
