<?php

namespace Tests\Feature;

use App\Models\Content\ContentDocument;
use App\Models\Content\ContentDocumentCategory;
use Database\Seeders\backend\content\ContentDocumentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentDocumentSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_document_seeding_is_idempotent_and_preserves_edits(): void
    {
        $this->seed(ContentDocumentSeeder::class);
        $categoryCount = ContentDocumentCategory::count();
        $documentCount = ContentDocument::count();
        $this->assertSame(10, $categoryCount);
        $this->assertGreaterThanOrEqual(18, $documentCount);
        $this->assertDatabaseHas('content_documents', ['type' => 'development', 'slug' => 'quick-start', 'active' => true]);
        $this->assertDatabaseHas('content_documents', ['type' => 'usage', 'slug' => 'documentation-management', 'active' => true]);

        $document = ContentDocument::where('slug', 'quick-start')->firstOrFail();
        $document->update(['title_i18n' => ['en' => 'Edited title']]);
        $this->seed(ContentDocumentSeeder::class);

        $this->assertSame($categoryCount, ContentDocumentCategory::count());
        $this->assertSame($documentCount, ContentDocument::count());
        $this->assertSame('Edited title', $document->fresh()->title);
    }
}
