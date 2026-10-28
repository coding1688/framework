<?php

namespace Tests\Feature;

use App\Models\Admin\AdminDepartment;
use App\Models\Admin\AdminUser;
use App\Models\Common\File;
use App\Models\Content\ContentCarousel;
use Database\Seeders\backend\admin\AdminMenuSeeder;
use Database\Seeders\backend\file\FileStorageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ContentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_announcement_crud_and_filters(): void
    {
        $this->backend();
        $response = $this->postJson(route('content.announcement.create'), $this->announcementPayload())
            ->assertCreated()
            ->assertJsonPath('data.title', 'Release notes');
        $id = $response->json('data.id');

        $this->getJson(route('content.announcement.list', ['q' => 'Release', 'active' => 1]))
            ->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.id', $id);
        $this->getJson(route('content.announcement.detail', $id))->assertOk()->assertJsonPath('data.content_i18n.en', '<p>Announcement content</p>');

        $this->postJson(route('content.announcement.update', $id), $this->announcementPayload(['title_i18n' => ['en' => 'Updated announcement'], 'active' => false]))
            ->assertOk()->assertJsonPath('data.title', 'Updated announcement')->assertJsonPath('data.active', false);
        $this->postJson(route('content.announcement.status', $id), ['active' => true])->assertOk()->assertJsonPath('data.active', true);
        $this->postJson(route('content.announcement.delete', $id))->assertOk();
        $this->assertDatabaseMissing('content_announcements', ['id' => $id]);
    }

    public function test_announcement_requires_english_title_and_content(): void
    {
        $this->backend();
        $this->postJson(route('content.announcement.create'), $this->announcementPayload([
            'title_i18n' => ['en' => ''],
            'content_i18n' => ['en' => ''],
        ]))->assertUnprocessable()->assertJsonValidationErrors(['title_i18n.en', 'content_i18n.en']);
    }

    public function test_carousel_image_upload_uses_public_storage(): void
    {
        Storage::fake('public');
        $this->backend();
        $response = $this->post(route('content.carousel.upload'), [
            'image' => UploadedFile::fake()->createWithContent('slide.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=')),
        ], ['Accept' => 'application/json'])->assertCreated();

        $path = $response->json('data.path');
        $this->assertNotEmpty($response->json('data.url'));
        $this->assertSame('system', $response->json('data.type'));
        Storage::disk('public')->assertExists($path);
    }

    public function test_carousel_crud_synchronizes_and_releases_image_references(): void
    {
        $this->backend();
        $firstImage = File::factory()->create();
        $secondImage = File::factory()->create();
        $response = $this->postJson(route('content.carousel.create'), $this->carouselPayload($firstImage->id))
            ->assertCreated()->assertJsonPath('data.title', 'Homepage carousel');
        $id = $response->json('data.id');
        $referenceType = (new ContentCarousel)->getMorphClass();

        $this->assertDatabaseHas('file_references', ['file_id' => $firstImage->id, 'reference_type' => $referenceType, 'reference_id' => $id, 'field' => 'image']);
        $this->postJson(route('content.carousel.update', $id), $this->carouselPayload($secondImage->id, ['active' => false]))
            ->assertOk()->assertJsonPath('data.image_id', $secondImage->id)->assertJsonPath('data.active', false);
        $this->assertDatabaseMissing('file_references', ['file_id' => $firstImage->id, 'reference_type' => $referenceType, 'reference_id' => $id]);
        $this->assertDatabaseHas('file_references', ['file_id' => $secondImage->id, 'reference_type' => $referenceType, 'reference_id' => $id]);

        $this->postJson(route('content.carousel.status', $id), ['active' => true])->assertOk()->assertJsonPath('data.active', true);
        $this->postJson(route('content.carousel.delete', $id))->assertOk();
        $this->assertDatabaseMissing('file_references', ['reference_type' => $referenceType, 'reference_id' => $id]);
        $this->assertDatabaseHas('files', ['id' => $secondImage->id]);
    }

    public function test_content_routes_reject_users_without_permission(): void
    {
        AdminDepartment::firstOrCreate(['code' => 'board'], ['name_i18n' => ['en' => 'Board'], 'active' => true]);
        $this->seed([AdminMenuSeeder::class, FileStorageSeeder::class]);
        $department = AdminDepartment::firstOrCreate(['code' => 'content-staff'], ['name_i18n' => ['en' => 'Content staff'], 'active' => true]);
        $actor = AdminUser::factory()->create(['department_id' => $department->id]);
        $this->actingAs($actor, 'backend')->withSession(['backend.auth_version' => $actor->auth_version]);

        $this->getJson(route('content.announcement.list'))->assertForbidden();
        $this->getJson(route('content.carousel.list'))->assertForbidden();
    }

    private function backend(): void
    {
        Storage::fake('public');
        AdminDepartment::firstOrCreate(['code' => 'board'], ['name_i18n' => ['en' => 'Board'], 'active' => true]);
        $this->seed([AdminMenuSeeder::class, FileStorageSeeder::class]);
        $actor = AdminUser::factory()->super()->create();
        $this->actingAs($actor, 'backend')->withSession(['backend.auth_version' => $actor->auth_version]);
    }

    private function announcementPayload(array $overrides = []): array
    {
        return array_replace([
            'title_i18n' => ['en' => 'Release notes'],
            'content_i18n' => ['en' => '<p>Announcement content</p>'],
            'published_at' => '2026-10-27 12:00:00',
            'sort' => 10,
            'active' => true,
        ], $overrides);
    }

    private function carouselPayload(int $imageId, array $overrides = []): array
    {
        return array_replace([
            'title_i18n' => ['en' => 'Homepage carousel'],
            'image_id' => $imageId,
            'link_url' => '/featured',
            'sort' => 10,
            'active' => true,
        ], $overrides);
    }
}
