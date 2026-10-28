<?php

namespace Tests\Feature;

use App\Jobs\FileSyncBatch;
use App\Libs\File\StorageRegistry;
use App\Libs\File\StorageSynchronizer;
use App\Models\Admin\AdminDepartment;
use App\Models\Admin\AdminUser;
use App\Models\Common\File;
use App\Models\Common\FileStorageAccount;
use App\Models\Common\FileSyncTask;
use Database\Seeders\backend\admin\AdminMenuSeeder;
use Database\Seeders\backend\file\FileStorageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileStorageSyncTest extends TestCase
{
    use RefreshDatabase;

    private const BASE = 'http://a.base.local/admin/file/storage/';

    protected function setUp(): void
    {
        parent::setUp();
        AdminDepartment::unguarded(fn () => AdminDepartment::firstOrCreate(['code' => 'board'], ['name_i18n' => ['en' => 'Board'], 'active' => true]));
        $this->seed([AdminMenuSeeder::class, FileStorageSeeder::class]);
        $actor = AdminUser::factory()->super()->create(['security_password' => 'StorageSafe123']);
        $this->actingAs($actor, 'backend')->withSession(['backend.auth_version' => $actor->auth_version]);
        FileStorageAccount::factory()->create(['disk' => 'backup', 'visibility' => 'private']);
        FileStorageAccount::factory()->create(['disk' => 'backup2', 'visibility' => 'private']);
        foreach (['public', 'local', 'backup', 'backup2'] as $disk) {
            Storage::fake($disk);
        }
        $this->partialMock(StorageRegistry::class, fn ($mock) => $mock->shouldReceive('disk')->andReturnUsing(fn (string $disk) => Storage::disk($disk)));
        Queue::fake();
    }

    public function test_sync_is_queued_copies_registered_files_only_and_retains_originals_and_urls(): void
    {
        $file = $this->file('source content');
        Storage::disk('local')->put('unregistered.txt', 'not in registry');
        $response = $this->postJson(self::BASE.'sync', ['source_disk' => 'local', 'target_disk' => 'backup'])->assertStatus(202);
        $id = $response->json('data.id');
        Queue::assertPushed(FileSyncBatch::class, fn ($job) => $job->taskId === $id && $job->connection === 'file-sync' && $job->queue === 'file-sync');
        Storage::disk('backup')->assertMissing($file->path);
        $this->finish($id);
        Storage::disk('backup')->assertExists($file->path);
        Storage::disk('local')->assertExists($file->path);
        Storage::disk('backup')->assertMissing('unregistered.txt');
        $this->assertSame('source content', Storage::disk('backup')->get($file->path));
        $this->assertSame('local', $file->fresh()->disk);
        $this->assertSame('completed', FileSyncTask::find($id)->status);
        $this->assertDatabaseHas('file_replicas', ['file_id' => $file->id, 'disk' => 'backup', 'checksum' => $file->checksum]);
        $this->getJson('http://a.base.local/admin/file/item/detail/'.$file->id)->assertOk()->assertJsonPath('data.replicas.0.disk', 'backup');
    }

    public function test_repeat_sync_skips_verified_copies_and_replica_can_be_used_as_source(): void
    {
        $file = $this->file('same bytes');
        $this->finish($this->start('local', 'backup'));
        $id = $this->start('local', 'backup');
        $this->finish($id);
        $this->assertEquals(1, FileSyncTask::find($id)->skipped);
        $this->assertSame(1, $file->replicas()->count());
        $this->finish($this->start('backup', 'backup2'));
        $this->assertSame('same bytes', Storage::disk('backup2')->get($file->path));
        $this->assertSame(2, $file->replicas()->count());
    }

    public function test_private_files_cannot_be_synchronized_to_public_storage_and_same_disk_is_rejected(): void
    {
        $this->file('private content');
        $this->postJson(self::BASE.'sync', ['source_disk' => 'local', 'target_disk' => 'public'])->assertUnprocessable()->assertJsonValidationErrors('target_disk');
        $this->postJson(self::BASE.'sync', ['source_disk' => 'local', 'target_disk' => 'local'])->assertUnprocessable()->assertJsonValidationErrors('target_disk');
        $this->assertSame(0, FileSyncTask::count());
    }

    public function test_conflicting_target_is_never_overwritten_and_failed_task_can_retry(): void
    {
        $file = $this->file('correct content');
        Storage::disk('backup')->put($file->path, 'conflicting content');
        $id = $this->start('local', 'backup');
        $this->finish($id);
        $this->assertSame('conflicting content', Storage::disk('backup')->get($file->path));
        $this->assertSame('failed', FileSyncTask::find($id)->status);
        $this->assertSame('destination_conflict', FileSyncTask::find($id)->errors[0]['message']);
        $this->assertSame(0, $file->replicas()->count());
        Storage::disk('backup')->delete($file->path);
        $this->postJson(self::BASE.'retry/'.$id)->assertStatus(202);
        $this->finish($id);
        $this->assertSame('completed', FileSyncTask::find($id)->status);
        $this->assertSame('correct content', Storage::disk('backup')->get($file->path));
    }

    public function test_source_checksum_failure_keeps_source_and_does_not_register_replica(): void
    {
        $file = $this->file('before corruption');
        Storage::disk('local')->put($file->path, 'corrupted');
        $id = $this->start('local', 'backup');
        $this->finish($id);
        $this->assertSame('failed', FileSyncTask::find($id)->status);
        $this->assertSame('corrupted', Storage::disk('local')->get($file->path));
        Storage::disk('backup')->assertMissing($file->path);
        $this->assertSame(0, $file->replicas()->count());
    }

    public function test_active_sync_blocks_other_tasks_and_storage_deletion(): void
    {
        $this->file('content');
        $id = $this->start('local', 'backup');
        $this->postJson(self::BASE.'sync', ['source_disk' => 'local', 'target_disk' => 'backup2'])->assertUnprocessable();
        $backup = FileStorageAccount::where('disk', 'backup')->sole();
        $this->postJson(self::BASE.'delete/'.$backup->id, ['security_password' => 'StorageSafe123'])->assertUnprocessable();
        $this->postJson(self::BASE.'retry/'.$id)->assertUnprocessable();
        $this->finish($id);
    }

    public function test_deleting_unreferenced_file_removes_original_and_all_replicas(): void
    {
        $file = $this->file('content to delete');
        $this->finish($this->start('local', 'backup'));
        $this->postJson('http://a.base.local/admin/file/item/delete/'.$file->id)->assertOk();
        Storage::disk('local')->assertMissing($file->path);
        Storage::disk('backup')->assertMissing($file->path);
        $this->assertDatabaseMissing('file_replicas', ['file_id' => $file->id]);
    }

    public function test_worker_failure_is_recorded_without_exposing_exception_secrets(): void
    {
        $id = $this->start('local', 'backup');
        (new FileSyncBatch($id))->failed(new \RuntimeException('secret-key=hidden'));
        $task = FileSyncTask::find($id);
        $this->assertSame('failed', $task->status);
        $this->assertStringNotContainsString('hidden', $task->toJson());
    }

    public function test_sync_snapshot_excludes_new_uploads_and_accounts_for_deleted_files(): void
    {
        $original = $this->file('original');
        $id = $this->start('local', 'backup');
        $new = $this->file('new after snapshot');
        $original->delete();
        $this->finish($id);
        Storage::disk('backup')->assertMissing($new->path);
        $this->assertEquals(1, FileSyncTask::find($id)->skipped);
        $this->assertSame('completed', FileSyncTask::find($id)->status);
    }

    private function file(string $contents): File
    {
        $file = File::factory()->create(['disk' => 'local', 'visibility' => 'private', 'extension' => 'txt', 'mime_type' => 'text/plain', 'size' => strlen($contents), 'checksum' => hash('sha256', $contents)]);
        Storage::disk('local')->put($file->path, $contents);

        return $file;
    }

    private function start(string $source, string $target): int
    {
        return $this->postJson(self::BASE.'sync', ['source_disk' => $source, 'target_disk' => $target])->assertStatus(202)->json('data.id');
    }

    private function finish(int $id): void
    {
        for ($batch = 0; $batch < 5; $batch++) {
            app(StorageSynchronizer::class)->process($id);
            if (in_array(FileSyncTask::find($id)->status, ['completed', 'failed'], true)) {
                return;
            }
        }
        $this->fail('Sync task did not terminate within the fixture batch count.');
    }
}
