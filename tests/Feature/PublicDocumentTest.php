<?php

namespace Tests\Feature;

use App\Models\Content\ContentDocument;
use App\Models\Content\ContentDocumentCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_navigation_only_contains_visible_published_documents_in_tree_order(): void
    {
        $root = ContentDocumentCategory::factory()->create(['slug' => 'development', 'sort' => 1]);
        $category = ContentDocumentCategory::factory()->create(['parent_id' => $root->id, 'slug' => 'guide', 'sort' => 2]);
        ContentDocument::factory()->create(['category_id' => $category->id, 'slug' => 'visible', 'title_i18n' => ['en' => 'Visible'], 'sort' => 10]);
        ContentDocument::factory()->create(['category_id' => $category->id, 'slug' => 'draft', 'active' => false]);
        ContentDocument::factory()->create(['category_id' => $category->id, 'slug' => 'future', 'published_at' => now()->addDay()]);
        $disabled = ContentDocumentCategory::factory()->create(['slug' => 'disabled', 'active' => false]);
        ContentDocument::factory()->create(['category_id' => $disabled->id, 'slug' => 'hidden']);

        $this->getJson(route('documents.navigation'))->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $root->id)
            ->assertJsonPath('data.0.children.0.id', $category->id)
            ->assertJsonCount(1, 'data.0.children.0.documents')
            ->assertJsonPath('data.0.children.0.documents.0.slug', 'visible')
            ->assertJsonMissingPath('data.0.children.0.documents.0.content');
    }

    public function test_detail_returns_localized_content_and_hides_unavailable_documents(): void
    {
        $category = ContentDocumentCategory::factory()->create(['name_i18n' => ['en' => 'Guide', 'zh_CN' => '指南']]);
        $document = ContentDocument::factory()->create([
            'category_id' => $category->id,
            'slug' => 'installation',
            'title_i18n' => ['en' => 'Installation', 'zh_CN' => '安装'],
            'content_i18n' => ['en' => '<p>Install</p>', 'zh_CN' => '<p>安装说明</p>'],
        ]);

        $this->withHeader('X-Lang', 'zh_CN')->getJson(route('documents.show', ['type' => 'development', 'slug' => 'installation']))
            ->assertOk()->assertJsonPath('data.title', '安装')->assertJsonPath('data.content', '<p>安装说明</p>')->assertJsonPath('data.category.name', '指南');
        $this->withHeader('X-Lang', 'invalid')->getJson(route('documents.show', ['type' => 'development', 'slug' => 'installation']))
            ->assertOk()->assertJsonPath('data.title', 'Installation');

        $document->update(['active' => false]);
        $this->getJson(route('documents.show', ['type' => 'development', 'slug' => 'installation']))->assertNotFound();
        $document->update(['active' => true, 'published_at' => now()->addDay()]);
        $this->getJson(route('documents.show', ['type' => 'development', 'slug' => 'installation']))->assertNotFound();
        $this->getJson(route('documents.show', ['type' => 'usage', 'slug' => 'installation']))->assertNotFound();
    }
}
