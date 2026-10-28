<?php

namespace Tests\Feature;

use App\Http\Middleware\Backend\BackendLogMid;
use App\Jobs\RunScheduledTask;
use App\Models\Admin\AdminMenu;
use App\Models\Admin\AdminUser;
use App\Models\System\SystemScheduleRun;
use App\Models\System\SystemScheduleTask;
use Database\Seeders\backend\admin\AdminMenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class SystemOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_menu_seeder_moves_operations_under_development_management(): void
    {
        $this->seed(AdminMenuSeeder::class);

        $development = AdminMenu::where('permission', 'development')->sole();
        $system = AdminMenu::where('permission', 'system')->sole();
        $queue = AdminMenu::where('permission', 'system.queue')->sole();
        $schedule = AdminMenu::where('permission', 'system.schedule')->sole();
        $this->assertSame('开发管理', $development->name_i18n['zh_CN']);
        $otherRootSort = AdminMenu::whereNull('parent_id')->where('permission', '!=', 'development')->max('sort');
        $this->assertGreaterThan($otherRootSort, $development->sort);
        $this->assertSame($development->id, $queue->parent_id);
        $this->assertSame($development->id, $schedule->parent_id);

        $development->forceFill(['name_i18n' => array_replace($development->name_i18n, ['zh_CN' => '开发配置'])])->save();
        $queue->forceFill(['parent_id' => $system->id])->save();
        $this->seed(AdminMenuSeeder::class);
        $this->assertSame('开发管理', $development->fresh()->name_i18n['zh_CN']);
        $this->assertSame($development->id, $queue->fresh()->parent_id);
    }

    public function test_queue_overview_failure_detail_retry_and_delete_are_controlled(): void
    {
        $this->withoutMiddleware(BackendLogMid::class);
        $admin = AdminUser::factory()->super()->create();
        $uuid = $this->failedJob();
        $this->actingAs($admin, 'backend')->withSession(['backend.auth_version' => 1]);

        $this->getJson(route('system.queue.overview'))->assertOk()->assertJsonPath('data.0.failed', 1);
        $this->getJson(route('system.queue.failed'))->assertOk()->assertJsonCount(1, 'data')->assertJsonMissingPath('data.0.payload');
        $id = DB::table('failed_jobs')->where('uuid', $uuid)->value('id');
        $this->getJson(route('system.queue.detail', $id))->assertOk()->assertJsonPath('data.name', 'App\\Jobs\\RunScheduledTask')->assertJsonMissingPath('data.payload');

        $this->postJson(route('system.queue.retry', $uuid))->assertOk();
        $this->assertDatabaseMissing('failed_jobs', ['uuid' => $uuid]);
        $this->assertDatabaseCount('jobs', 1);

        $second = $this->failedJob();
        $this->postJson(route('system.queue.delete', $second))->assertOk();
        $this->assertDatabaseMissing('failed_jobs', ['uuid' => $second]);
        $this->assertDatabaseHas('admin_logs', ['action' => 'system.queue.delete', 'subject' => $second]);
    }

    public function test_registered_schedule_runs_once_and_records_result(): void
    {
        $admin = AdminUser::factory()->super()->create();
        $this->actingAs($admin, 'backend')->withSession(['backend.auth_version' => 1]);

        $task = SystemScheduleTask::factory()->create(['key' => 'notifications:prune']);
        $this->getJson(route('system.schedule.list'))->assertOk()->assertJsonFragment(['key' => 'notifications:prune']);
        $response = $this->postJson(route('system.schedule.run', $task->id))->assertStatus(202);
        $run = SystemScheduleRun::findOrFail($response->json('data.id'));
        $this->postJson(route('system.schedule.run', $task->id))->assertStatus(409);
        $this->assertDatabaseHas('jobs', ['queue' => 'maintenance']);

        (new RunScheduledTask($run->id))->handle();
        $this->assertSame('success', $run->fresh()->status);
        $this->assertSame(0, $run->fresh()->exit_code);
        $this->assertNotNull($run->fresh()->duration_ms);
        $this->getJson(route('system.schedule.runs'))->assertOk()->assertJsonPath('data.0.status', 'success');
        $this->getJson(route('system.schedule.runDetail', $run->id))->assertOk()->assertJsonPath('data.command', 'notifications:prune');
        $this->assertDatabaseHas('admin_logs', ['action' => 'system.schedule.run', 'subject' => (string) $task->id]);
    }

    public function test_operations_are_denied_to_non_board_administrator(): void
    {
        $admin = AdminUser::factory()->create();
        $this->actingAs($admin, 'backend')->withSession(['backend.auth_version' => 1]);

        $this->getJson(route('system.queue.overview'))->assertForbidden();
        $this->getJson(route('system.schedule.list'))->assertForbidden();
    }

    private function failedJob(): string
    {
        $jobId = Queue::connection('maintenance')->push(new RunScheduledTask(1));
        $payload = DB::table('jobs')->where('id', $jobId)->value('payload');
        DB::table('jobs')->where('id', $jobId)->delete();
        $uuid = (string) Str::uuid();
        DB::table('failed_jobs')->insert([
            'uuid' => $uuid, 'connection' => 'database', 'queue' => 'default', 'payload' => $payload,
            'exception' => "RuntimeException: Example failure\nStack trace",
            'failed_at' => now(),
        ]);

        return $uuid;
    }
}
