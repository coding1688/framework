<?php

namespace Tests\Feature;

use App\Models\Admin\AdminUser;
use App\Models\Member\MemberVipLevel;
use App\Models\Member\MemberVipRecord;
use App\Models\Member\User;
use Database\Seeders\backend\member\MemberVipSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberVipSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_manage_vip_levels_and_used_levels_cannot_be_deleted(): void
    {
        $admin = AdminUser::factory()->super()->create();
        $this->actingAs($admin, 'backend')->withSession(['backend.auth_version' => 1]);

        $response = $this->postJson(route('member.vipLevel.create'), $this->payload())->assertCreated()->assertJsonPath('data.level', 4);
        $id = $response->json('data.id');
        $this->postJson(route('member.vipLevel.update', $id), $this->payload(['duration_days' => 90]))->assertOk()->assertJsonPath('data.duration_days', 90);
        $this->postJson(route('member.vipLevel.status', $id), ['active' => false])->assertOk()->assertJsonPath('data.active', false);
        $this->assertDatabaseHas('admin_logs', ['action' => 'member.vipLevel.update', 'subject' => (string) $id]);

        User::factory()->create(['vip_level' => 4, 'vip_level_id' => $id]);
        $this->postJson(route('member.vipLevel.delete', $id))->assertUnprocessable();
        $this->assertDatabaseHas('member_vip_levels', ['id' => $id]);
    }

    public function test_vip_assignment_extension_cancellation_and_history_are_transactional(): void
    {
        $admin = AdminUser::factory()->super()->create();
        $this->actingAs($admin, 'backend')->withSession(['backend.auth_version' => 1]);
        $level = MemberVipLevel::factory()->create(['level' => 2, 'duration_days' => 30]);
        $user = User::factory()->create();
        $startedAt = now()->startOfSecond();

        $this->postJson(route('frontend.user.vip', $user->id), ['vip_level_id' => $level->id, 'started_at' => $startedAt->toDateTimeString(), 'reason' => 'New membership'])
            ->assertOk()->assertJsonPath('data.vip_level', 2)->assertJsonPath('data.vip_status', 'active');
        $fresh = $user->fresh();
        $this->assertSame($startedAt->copy()->addDays(30)->toDateTimeString(), $fresh->vip_expires_at->toDateTimeString());

        $expiresAt = now()->addDays(90)->startOfSecond();
        $this->postJson(route('frontend.user.vip', $user->id), ['vip_level_id' => $level->id, 'started_at' => $startedAt->toDateTimeString(), 'expires_at' => $expiresAt->toDateTimeString(), 'reason' => 'Renewal'])->assertOk();
        $this->postJson(route('frontend.user.vip', $user->id), ['vip_level_id' => null, 'reason' => 'Revoked'])->assertOk()->assertJsonPath('data.vip_level', 0)->assertJsonPath('data.vip_status', 'none');
        $this->assertSame(3, MemberVipRecord::where('user_id', $user->id)->count());
        $this->getJson(route('frontend.user.vipHistory', $user->id))->assertOk()->assertJsonCount(3, 'data')->assertJsonPath('data.0.to_level', null);
        $this->assertDatabaseHas('admin_logs', ['action' => 'frontend.user.vip', 'subject' => (string) $user->id]);
    }

    public function test_expired_membership_has_zero_effective_level_and_permanent_membership_stays_active(): void
    {
        $admin = AdminUser::factory()->super()->create();
        $this->actingAs($admin, 'backend')->withSession(['backend.auth_version' => 1]);
        $level = MemberVipLevel::factory()->create(['level' => 3]);
        $user = User::factory()->create(['vip_level' => 3, 'vip_level_id' => $level->id, 'vip_started_at' => now()->subYear(), 'vip_expires_at' => now()->subDay()]);

        $this->getJson(route('frontend.user.detail', $user->id))->assertOk()->assertJsonPath('data.vip_level', 0)->assertJsonPath('data.vip_status', 'expired');
        $user->forceFill(['vip_expires_at' => null])->save();
        $this->getJson(route('frontend.user.detail', $user->id))->assertOk()->assertJsonPath('data.vip_level', 3)->assertJsonPath('data.vip_status', 'permanent');
    }

    public function test_seeder_preserves_legacy_levels_and_marks_them_permanent(): void
    {
        $user = User::factory()->create(['vip_level' => 7]);
        $this->seed(MemberVipSeeder::class);

        $level = MemberVipLevel::where('level', 7)->sole();
        $fresh = $user->fresh();
        $this->assertSame($level->id, $fresh->vip_level_id);
        $this->assertSame('permanent', $fresh->vip_status);
        $this->assertSame(7, $fresh->effective_vip_level);
        $this->assertSame([1, 2, 3, 7], MemberVipLevel::orderBy('level')->pluck('level')->all());
    }

    public function test_effective_level_uses_the_related_level_when_the_legacy_snapshot_is_stale(): void
    {
        $level = MemberVipLevel::factory()->create(['level' => 5]);
        $user = User::factory()->create(['vip_level' => 99, 'vip_level_id' => $level->id]);

        $this->assertSame(5, $user->fresh()->effective_vip_level);
    }

    public function test_invalid_period_and_non_privileged_access_are_rejected(): void
    {
        $admin = AdminUser::factory()->super()->create();
        $this->actingAs($admin, 'backend')->withSession(['backend.auth_version' => 1]);
        $level = MemberVipLevel::factory()->create();
        $user = User::factory()->create();
        $this->postJson(route('frontend.user.vip', $user->id), ['vip_level_id' => $level->id, 'started_at' => '2026-10-28 12:00:00', 'expires_at' => '2026-10-27 12:00:00'])->assertUnprocessable()->assertJsonValidationErrors(['expires_at']);

        $ordinary = AdminUser::factory()->create();
        $this->actingAs($ordinary, 'backend')->withSession(['backend.auth_version' => 1]);
        $this->getJson(route('member.vipLevel.list'))->assertForbidden();
        $this->postJson(route('frontend.user.vip', $user->id), ['vip_level_id' => $level->id])->assertForbidden();
    }

    private function payload(array $overrides = []): array
    {
        return array_replace([
            'level' => 4, 'name_i18n' => ['en' => 'VIP 4'], 'description_i18n' => [], 'benefits_i18n' => ['en' => 'Premium features'],
            'duration_days' => 30, 'active' => true, 'sort' => 0,
        ], $overrides);
    }
}
