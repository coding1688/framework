<?php

namespace Tests\Feature;

use App\Attributes\Schedulable;
use App\Jobs\RunScheduledTask;
use App\Libs\Settings\SettingStore;
use App\Models\Admin\AdminUser;
use App\Models\System\SystemScheduleRun;
use App\Models\System\SystemScheduleTask;
use App\Services\System\ScheduleCommandRegistry;
use App\Services\System\SystemScheduleDispatcher;
use Database\Seeders\backend\schedule\SystemScheduleSeeder;
use Database\Seeders\backend\setting\SystemSettingSeeder;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class SystemScheduleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_manage_tasks_and_only_registered_commands_are_accepted(): void
    {
        $admin = AdminUser::factory()->super()->create();
        $this->actingAs($admin, 'backend')->withSession(['backend.auth_version' => 1]);

        $response = $this->postJson(route('system.schedule.create'), $this->payload())->assertCreated()->assertJsonPath('data.key', 'test-task');
        $id = $response->json('data.id');
        $this->assertDatabaseHas('system_schedule_tasks', ['id' => $id, 'command' => 'notifications:prune']);
        $this->assertDatabaseHas('admin_logs', ['action' => 'system.schedule.create', 'subject' => (string) $id]);

        $this->postJson(route('system.schedule.update', $id), $this->payload(['cron' => '*/5 * * * *', 'active' => false]))->assertOk()->assertJsonPath('data.cron', '*/5 * * * *');
        $this->postJson(route('system.schedule.status', $id), ['active' => true])->assertOk()->assertJsonPath('data.active', true);
        $this->postJson(route('system.schedule.create'), $this->payload(['key' => 'bad-command', 'command' => 'rm -rf /']))->assertUnprocessable()->assertJsonValidationErrors(['command']);
        $this->postJson(route('system.schedule.create'), $this->payload(['key' => 'bad-cron', 'cron' => 'not-cron']))->assertUnprocessable()->assertJsonValidationErrors(['cron']);
        $this->postJson(route('system.schedule.create'), $this->payload(['key' => 'bad-timezone', 'timezone' => 'Invalid/Zone']))->assertUnprocessable()->assertJsonValidationErrors(['timezone']);

        $this->postJson(route('system.schedule.delete', $id))->assertOk();
        $this->assertSoftDeleted('system_schedule_tasks', ['id' => $id]);
    }

    public function test_due_tasks_are_dispatched_once_and_inactive_tasks_are_skipped(): void
    {
        $this->travelTo(Carbon::parse('2026-10-28 12:00:00', 'UTC'));
        $task = SystemScheduleTask::factory()->create(['cron' => '* * * * *']);
        SystemScheduleTask::factory()->create(['cron' => '* * * * *', 'active' => false]);
        $dispatcher = app(SystemScheduleDispatcher::class);

        $this->assertSame(1, $dispatcher->dispatchDue());
        $this->assertSame(0, $dispatcher->dispatchDue());
        $this->assertDatabaseHas('system_schedule_runs', ['system_schedule_task_id' => $task->id, 'trigger' => 'schedule', 'status' => 'pending']);
        $this->assertSame(1, SystemScheduleRun::count());
    }

    public function test_global_switch_disables_automatic_dispatch_but_not_manual_execution(): void
    {
        $this->seed(SystemSettingSeeder::class);
        $this->assertSame('Asia/Shanghai', app(SettingStore::class)->get('schedule.timezone'));
        $admin = AdminUser::factory()->super()->create();
        $this->actingAs($admin, 'backend')->withSession(['backend.auth_version' => 1]);
        $this->postJson(route('system.schedule.updateSettings'), ['enabled' => false, 'timezone' => 'Asia/Shanghai', 'log_retention_days' => 30])
            ->assertOk()->assertJsonPath('data.enabled', false)->assertJsonPath('data.timezone', 'Asia/Shanghai');
        $this->assertFalse(app(SettingStore::class)->get('schedule.enabled'));
        $task = SystemScheduleTask::factory()->create(['cron' => '* * * * *']);

        $this->assertSame(0, app(SystemScheduleDispatcher::class)->dispatchDue());
        $this->postJson(route('system.schedule.run', $task->id))->assertStatus(202);
        $this->assertDatabaseHas('system_schedule_runs', ['system_schedule_task_id' => $task->id, 'trigger' => 'manual']);
    }

    public function test_overlapping_policy_can_allow_multiple_pending_runs(): void
    {
        $task = SystemScheduleTask::factory()->create(['without_overlapping' => false]);
        $dispatcher = app(SystemScheduleDispatcher::class);

        $this->assertNotNull($dispatcher->dispatch($task, 'manual'));
        $this->assertNotNull($dispatcher->dispatch($task, 'manual'));
        $this->assertSame(2, SystemScheduleRun::where('system_schedule_task_id', $task->id)->count());
    }

    public function test_executor_captures_output_and_rejects_unregistered_run_commands(): void
    {
        $task = SystemScheduleTask::factory()->create(['command' => 'system-schedule:prune-logs']);
        $run = SystemScheduleRun::create(['system_schedule_task_id' => $task->id, 'task_key' => $task->key, 'command' => $task->command, 'trigger' => 'manual', 'status' => 'pending']);
        (new RunScheduledTask($run->id))->handle();
        $this->assertSame('success', $run->fresh()->status);
        $this->assertStringContainsString('Pruned', $run->fresh()->output);
        $this->assertNotNull($run->fresh()->duration_ms);

        $invalid = SystemScheduleRun::create(['task_key' => 'invalid', 'command' => 'unregistered:command', 'trigger' => 'manual', 'status' => 'pending']);
        (new RunScheduledTask($invalid->id))->handle();
        $this->assertSame('failed', $invalid->fresh()->status);
        $this->assertSame('Unregistered command', $invalid->fresh()->error);
    }

    public function test_log_retention_command_only_prunes_expired_finished_runs(): void
    {
        $task = SystemScheduleTask::factory()->create();
        $old = SystemScheduleRun::create(['system_schedule_task_id' => $task->id, 'task_key' => $task->key, 'command' => $task->command, 'trigger' => 'schedule', 'status' => 'success']);
        $old->forceFill(['created_at' => now()->subDays(100)])->save();
        $recent = SystemScheduleRun::create(['system_schedule_task_id' => $task->id, 'task_key' => $task->key, 'command' => $task->command, 'trigger' => 'schedule', 'status' => 'failed']);
        $pending = SystemScheduleRun::create(['system_schedule_task_id' => $task->id, 'task_key' => $task->key, 'command' => $task->command, 'trigger' => 'schedule', 'status' => 'pending']);
        $pending->forceFill(['created_at' => now()->subDays(100)])->save();

        $this->artisan('system-schedule:prune-logs')->assertSuccessful();
        $this->assertDatabaseMissing('system_schedule_runs', ['id' => $old->id]);
        $this->assertDatabaseHas('system_schedule_runs', ['id' => $recent->id]);
        $this->assertDatabaseHas('system_schedule_runs', ['id' => $pending->id]);
    }

    public function test_module_command_is_registered_and_seeded_from_its_attribute(): void
    {
        Artisan::registerCommand(new PluginSchedulableCommand);
        $registry = app(ScheduleCommandRegistry::class);

        $this->assertTrue($registry->has('tests:plugin-scheduled'));
        $this->assertSame('15 3 * * *', $registry->all()['tests:plugin-scheduled']['cron']);
        $this->seed(SystemScheduleSeeder::class);
        $this->assertDatabaseHas('system_schedule_tasks', [
            'key' => 'plugin-storage-sync', 'command' => 'tests:plugin-scheduled',
            'cron' => '15 3 * * *', 'without_overlapping' => false, 'sort' => 50,
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_replace([
            'key' => 'test-task', 'name_i18n' => ['en' => 'Test task'], 'description_i18n' => [],
            'command' => 'notifications:prune', 'cron' => '* * * * *', 'timezone' => 'UTC',
            'active' => true, 'without_overlapping' => true, 'sort' => 0,
        ], $overrides);
    }
}

#[Schedulable(['en' => 'Plugin storage sync', 'zh_CN' => '插件存储同步'], cron: '15 3 * * *', key: 'plugin-storage-sync', withoutOverlapping: false, sort: 50)]
class PluginSchedulableCommand extends Command
{
    protected $signature = 'tests:plugin-scheduled';

    public function handle(): int
    {
        return self::SUCCESS;
    }
}
