<?php

namespace Tests\Feature;

use App\Models\Admin\AdminDepartment;
use App\Models\Admin\AdminUser;
use App\Models\Content\ContentDocumentCategory;
use Database\Seeders\backend\admin\AdminMenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_crud_and_hierarchy_guards(): void
    {
        $this->backend();
        $rootId = $this->postJson(route('documentation.category.create'), $this->categoryPayload())
            ->assertCreated()->assertJsonPath('data.name', 'Guides')->json('data.id');
        $childId = $this->postJson(route('documentation.category.create'), $this->categoryPayload([
            'parent_id' => $rootId,
            'slug' => 'advanced',
            'name_i18n' => ['en' => 'Advanced guides'],
        ]))->assertCreated()->assertJsonPath('data.parent_id', $rootId)->json('data.id');

        $this->getJson(route('documentation.category.list', ['type' => 'development', 'q' => 'guides']))
            ->assertOk()->assertJsonCount(2, 'data');
        $this->getJson(route('documentation.category.options', ['type' => 'usage']))->assertOk()->assertJsonCount(0, 'data');
        $this->postJson(route('documentation.category.update', $rootId), $this->categoryPayload(['parent_id' => $childId]))
            ->assertUnprocessable()->assertJsonValidationErrors(['parent_id']);
        $this->postJson(route('documentation.category.delete', $rootId))->assertUnprocessable()->assertJsonValidationErrors(['id']);
        $this->postJson(route('documentation.category.status', $childId), ['active' => false])->assertOk()->assertJsonPath('data.active', false);

        $this->postJson(route('documentation.category.delete', $childId))->assertOk();
        $this->postJson(route('documentation.category.delete', $rootId))->assertOk();
        $this->assertDatabaseMissing('content_document_categories', ['id' => $rootId]);
    }

    public function test_document_crud_filters_and_validation(): void
    {
        $this->backend();
        $category = ContentDocumentCategory::factory()->create(['slug' => 'guide', 'name_i18n' => ['en' => 'Guide']]);
        $response = $this->postJson(route('documentation.document.create'), $this->documentPayload($category->id))
            ->assertCreated()->assertJsonPath('data.title', 'Installation')->assertJsonPath('data.category.name', 'Guide');
        $id = $response->json('data.id');

        $this->getJson(route('documentation.document.list', ['q' => 'Install', 'type' => 'development', 'category_id' => $category->id, 'active' => 1]))
            ->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.id', $id);
        $this->getJson(route('documentation.document.detail', $id))->assertOk()->assertJsonPath('data.content_i18n.en', '<p>Install the framework.</p>');
        $this->postJson(route('documentation.document.create'), $this->documentPayload($category->id))
            ->assertUnprocessable()->assertJsonValidationErrors(['slug']);
        $this->postJson(route('documentation.document.create'), $this->documentPayload($category->id, ['type' => 'usage', 'slug' => 'usage-guide']))
            ->assertUnprocessable()->assertJsonValidationErrors(['category_id']);
        $this->postJson(route('documentation.document.create'), $this->documentPayload($category->id, [
            'slug' => 'missing-content',
            'title_i18n' => ['en' => ''],
            'content_i18n' => ['en' => ''],
        ]))->assertUnprocessable()->assertJsonValidationErrors(['title_i18n.en', 'content_i18n.en']);

        $this->postJson(route('documentation.document.update', $id), $this->documentPayload($category->id, [
            'title_i18n' => ['en' => 'Updated installation'],
            'active' => false,
        ]))->assertOk()->assertJsonPath('data.title', 'Updated installation')->assertJsonPath('data.active', false);
        $this->postJson(route('documentation.document.status', $id), ['active' => true])->assertOk()->assertJsonPath('data.active', true);
        $this->postJson(route('documentation.category.delete', $category->id))->assertUnprocessable()->assertJsonValidationErrors(['id']);
        $this->postJson(route('documentation.document.delete', $id))->assertOk();
        $this->assertDatabaseMissing('content_documents', ['id' => $id]);
    }

    public function test_document_menus_are_discovered_and_routes_require_permission(): void
    {
        AdminDepartment::firstOrCreate(['code' => 'board'], ['name_i18n' => ['en' => 'Board'], 'active' => true]);
        $this->seed(AdminMenuSeeder::class);
        $this->assertDatabaseHas('admin_menus', ['permission' => 'documentation', 'type' => 'directory', 'parent_id' => null, 'sort' => 1001]);
        $this->assertDatabaseHas('admin_menus', ['permission' => 'documentation.document', 'type' => 'page']);
        $this->assertDatabaseHas('admin_menus', ['permission' => 'documentation.document.create', 'type' => 'button']);
        $this->assertDatabaseHas('admin_menus', ['permission' => 'documentation.category.update', 'type' => 'button']);

        $department = AdminDepartment::firstOrCreate(['code' => 'documentation-staff'], ['name_i18n' => ['en' => 'Documentation staff'], 'active' => true]);
        $actor = AdminUser::factory()->create(['department_id' => $department->id]);
        $this->actingAs($actor, 'backend')->withSession(['backend.auth_version' => $actor->auth_version]);
        $this->getJson(route('documentation.document.list'))->assertForbidden();
        $this->getJson(route('documentation.category.list'))->assertForbidden();
    }

    private function backend(): void
    {
        AdminDepartment::firstOrCreate(['code' => 'board'], ['name_i18n' => ['en' => 'Board'], 'active' => true]);
        $this->seed(AdminMenuSeeder::class);
        $actor = AdminUser::factory()->super()->create();
        $this->actingAs($actor, 'backend')->withSession(['backend.auth_version' => $actor->auth_version]);
    }

    private function categoryPayload(array $overrides = []): array
    {
        return array_replace([
            'type' => 'development',
            'parent_id' => null,
            'slug' => 'guides',
            'name_i18n' => ['en' => 'Guides'],
            'description_i18n' => ['en' => 'Framework guides'],
            'sort' => 10,
            'active' => true,
        ], $overrides);
    }

    private function documentPayload(int $categoryId, array $overrides = []): array
    {
        return array_replace([
            'category_id' => $categoryId,
            'type' => 'development',
            'slug' => 'installation',
            'title_i18n' => ['en' => 'Installation'],
            'summary_i18n' => ['en' => 'Install and configure the framework.'],
            'content_i18n' => ['en' => '<p>Install the framework.</p>'],
            'published_at' => '2026-11-01 12:00:00',
            'sort' => 10,
            'active' => true,
        ], $overrides);
    }
}
