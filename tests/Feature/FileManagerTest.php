<?php

namespace Tests\Feature;

use App\Libs\File\FileManager;
use App\Models\Admin\AdminDepartment;
use App\Models\Admin\AdminLog;
use App\Models\Admin\AdminUser;
use App\Models\Common\File;
use Database\Seeders\backend\admin\AdminMenuSeeder;
use Database\Seeders\backend\file\FileStorageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class FileManagerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        AdminDepartment::firstOrCreate(['code' => 'board'], ['name_i18n' => ['en' => 'Board'], 'active' => true]);
        $this->seed([AdminMenuSeeder::class, FileStorageSeeder::class]);
    }

    public function test_business_code_can_store_a_file_and_its_record_through_one_service(): void
    {
        $owner = AdminUser::factory()->create();

        $file = app(FileManager::class)->store(
            UploadedFile::fake()->create('notes.txt', 12, 'text/plain'),
            'public', 'general', 'public', $owner->id,
        );

        Storage::disk('public')->assertExists($file->path);
        $this->assertStringStartsWith('uploads/general/', $file->path);
        $this->assertSame('notes.txt', $file->original_name);
        $this->assertSame('text/plain', $file->mime_type);
        $this->assertSame($owner->id, $file->created_by);
        $this->assertSame(64, strlen($file->checksum));
        $this->assertDatabaseHas('files', ['id' => $file->id, 'disk' => 'public', 'path' => $file->path]);
    }

    public function test_a_failed_transaction_removes_every_file_and_record_created_by_the_batch(): void
    {
        $paths = [];
        try {
            app(FileManager::class)->storeMany([
                UploadedFile::fake()->create('first.txt', 1, 'text/plain'),
                UploadedFile::fake()->create('second.txt', 1, 'text/plain'),
            ], 'public', 'general', 'public', null, function (Collection $records) use (&$paths): void {
                $paths = $records->pluck('path')->all();
                throw new RuntimeException('Business save failed.');
            });
            $this->fail('The transaction callback should fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Business save failed.', $exception->getMessage());
        }

        $this->assertCount(2, $paths);
        $this->assertSame(0, File::count());
        foreach ($paths as $path) {
            Storage::disk('public')->assertMissing($path);
        }
    }

    public function test_existing_upload_endpoint_uses_the_shared_service_and_keeps_audit(): void
    {
        $actor = AdminUser::factory()->super()->create();
        $this->actingAs($actor, 'backend')->withSession(['backend.auth_version' => $actor->auth_version]);

        $response = $this->post(route('file.item.upload'), [
            'files' => [UploadedFile::fake()->create('endpoint.txt', 2, 'text/plain')],
            'disk' => 'public', 'type' => 'general', 'visibility' => 'public',
        ], ['Accept' => 'application/json'])->assertCreated();

        $path = $response->json('data.0.path');
        $this->assertNotEmpty($response->json('data.0.url'));
        $this->assertStringEndsWith('/storage/'.$path, $response->json('data.0.url'));
        Storage::disk('public')->assertExists($path);
        $this->assertDatabaseHas('files', ['path' => $path, 'created_by' => $actor->id]);
        $this->assertDatabaseHas('admin_logs', ['action' => 'file.item.upload', 'administrator_id' => $actor->id]);
        $this->assertSame(1, AdminLog::where('action', 'file.item.upload')->count());
    }
}
