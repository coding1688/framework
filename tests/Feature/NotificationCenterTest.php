<?php

namespace Tests\Feature;

use App\Libs\Notifications\NotificationManager;
use App\Models\Admin\AdminUser;
use App\Models\Member\User;
use App\Models\System\SystemNotificationTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class NotificationCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_creates_localized_database_notification_and_queues_mail(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $this->template();

        app(NotificationManager::class)->send($user, 'security.password_changed');

        $this->assertDatabaseCount('notifications', 1);
        $notification = $user->notifications()->sole();
        $this->assertSame('密码已修改', $notification->data['subject_i18n']['zh_CN']);
        Queue::assertPushed(SendQueuedNotifications::class);
    }

    public function test_user_can_only_read_own_notifications_and_save_preferences(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->template();
        $manager = app(NotificationManager::class);
        $manager->send($user, 'security.password_changed');
        $manager->send($other, 'security.password_changed');
        $own = $user->notifications()->sole();
        $foreign = $other->notifications()->sole();
        $this->actingAs($user, 'web')->withSession(['web.auth_version' => 1]);

        $this->getJson(route('notifications.list'))->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('unread', 1);
        $this->postJson(route('notifications.read', $foreign))->assertNotFound();
        $this->postJson(route('notifications.read', $own))->assertOk();
        $this->assertNotNull($own->fresh()->read_at);
        $this->postJson(route('notifications.preferences'), ['preferences' => ['security.password_changed' => ['mail']]])
            ->assertOk()->assertJson(['data' => ['security.password_changed' => ['mail']]]);
        $this->assertSame(['mail'], $user->fresh()->notification_preferences['security.password_changed']);
    }

    public function test_administrator_has_a_separate_notification_feed(): void
    {
        Queue::fake();
        $admin = AdminUser::factory()->super()->create();
        $this->template();
        app(NotificationManager::class)->send($admin, 'security.password_changed');
        $this->actingAs($admin, 'backend')->withSession(['backend.auth_version' => 1]);

        $this->getJson(route('backend.notifications.list'))->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('unread', 1);
        $this->postJson(route('backend.notifications.readAll'))->assertOk();
        $this->assertSame(0, $admin->unreadNotifications()->count());
    }

    public function test_board_can_manage_templates_and_non_board_is_denied(): void
    {
        $admin = AdminUser::factory()->super()->create();
        $this->actingAs($admin, 'backend')->withSession(['backend.auth_version' => 1]);
        $payload = [
            'code' => 'custom.notice', 'name_i18n' => ['en' => 'Custom notice'],
            'subject_i18n' => ['en' => 'Notice'], 'content_i18n' => ['en' => 'Hello {{ name }}'],
            'channels' => ['database'], 'active' => true,
        ];

        $response = $this->postJson(route('system.notificationTemplate.create'), $payload)->assertCreated();
        $id = $response->json('data.id');
        $this->postJson(route('system.notificationTemplate.update', $id), [...$payload, 'active' => false])
            ->assertOk()->assertJsonPath('data.active', false);
        $this->postJson(route('system.notificationTemplate.delete', $id))->assertOk();

        $ordinary = AdminUser::factory()->create();
        $this->actingAs($ordinary, 'backend')->withSession(['backend.auth_version' => 1]);
        $this->getJson(route('system.notificationTemplate.list'))->assertForbidden();
    }

    private function template(): void
    {
        SystemNotificationTemplate::create([
            'code' => 'security.password_changed',
            'name_i18n' => ['en' => 'Password changed', 'zh_CN' => '密码已修改'],
            'subject_i18n' => ['en' => 'Password changed', 'zh_CN' => '密码已修改'],
            'content_i18n' => ['en' => 'Your password changed.', 'zh_CN' => '你的密码已修改。'],
            'channels' => ['database', 'mail'], 'active' => true,
        ]);
    }
}
