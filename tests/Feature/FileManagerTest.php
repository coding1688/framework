<?php

namespace Tests\Feature;

use App\Libs\File\Constants\FileType;
use App\Libs\File\FileManager;
use App\Libs\File\FileReferences;
use App\Models\Admin\AdminDepartment;
use App\Models\Admin\AdminLog;
use App\Models\Admin\AdminUser;
use App\Models\Common\File;
use App\Models\Common\FileReference;
use App\Models\Member\User;
use App\Services\Api\AuthService;
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

    public function test_avatar_replacement_syncs_references_and_deletes_the_unused_old_file(): void
    {
        $user = User::factory()->create();
        $oldFile = $this->avatarFile($user);

        $updated = app(AuthService::class)->avatar($user->fresh(), UploadedFile::fake()->create('new.jpg', 12, 'image/jpeg'));
        $newFile = File::where('id', '!=', $oldFile->id)->sole();

        $this->assertSame($newFile->publicUrl(), $updated->avatar_url);
        Storage::disk('public')->assertMissing($oldFile->path);
        Storage::disk('public')->assertExists($newFile->path);
        $this->assertDatabaseMissing('files', ['id' => $oldFile->id]);
        $this->assertDatabaseHas('file_references', ['file_id' => $newFile->id, 'reference_type' => User::class, 'reference_id' => $user->id, 'field' => 'avatar_url']);
    }

    public function test_avatar_replacement_preserves_an_old_file_that_is_still_referenced_elsewhere(): void
    {
        $user = User::factory()->create();
        $oldFile = $this->avatarFile($user);
        FileReference::create(['file_id' => $oldFile->id, 'reference_type' => 'shared', 'reference_id' => 1, 'field' => 'image']);

        app(AuthService::class)->avatar($user->fresh(), UploadedFile::fake()->create('new.jpg', 12, 'image/jpeg'));

        Storage::disk('public')->assertExists($oldFile->path);
        $this->assertDatabaseHas('files', ['id' => $oldFile->id]);
        $this->assertDatabaseMissing('file_references', ['file_id' => $oldFile->id, 'reference_type' => User::class, 'reference_id' => $user->id]);
        $this->assertDatabaseHas('file_references', ['file_id' => $oldFile->id, 'reference_type' => 'shared', 'reference_id' => 1]);
    }

    public function test_reference_rebuild_uses_registered_owner_models(): void
    {
        $user = User::factory()->create();
        $file = $this->avatarFile($user);
        FileReference::where('reference_type', User::class)->where('reference_id', $user->id)->delete();

        $this->artisan('files:rebuild-references')->assertSuccessful();

        $this->assertDatabaseHas('file_references', ['file_id' => $file->id, 'reference_type' => User::class, 'reference_id' => $user->id, 'field' => 'avatar_url']);
    }

    private function avatarFile(User $user): File
    {
        $file = app(FileManager::class)->store(UploadedFile::fake()->create('old.jpg', 12, 'image/jpeg'), 'public', FileType::UserAvatar->value, 'public');
        $user->profile->forceFill(['avatar_url' => $file->publicUrl()])->save();
        app(FileReferences::class)->syncModel($user);

        return $file;
    }
}
