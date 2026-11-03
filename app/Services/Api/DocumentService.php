<?php

namespace App\Services\Api;

use App\Models\Content\ContentDocument;
use App\Models\Content\ContentDocumentCategory;
use Illuminate\Database\Eloquent\Collection;

class DocumentService extends BaseService
{
    public function navigation(): Collection
    {
        $categories = $this->visibleCategories();
        $documents = ContentDocument::query()
            ->whereIn('category_id', $categories->modelKeys())
            ->where('active', true)
            ->where(fn ($query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->orderBy('sort')->orderBy('id')->get()->groupBy('category_id');
        $categories->each(fn (ContentDocumentCategory $category) => $category->setRelation('documents', $documents->get($category->id, collect())->values()));
        $byParent = $categories->groupBy('parent_id');
        $tree = function (?int $parentId) use (&$tree, $byParent): Collection {
            return $byParent->get($parentId, new Collection)->map(function (ContentDocumentCategory $category) use (&$tree): ContentDocumentCategory {
                $category->setRelation('children', $tree($category->id));

                return $category;
            })->values();
        };

        return $tree(null);
    }

    public function show(string $type, string $slug): ContentDocument
    {
        return ContentDocument::with('category')
            ->whereIn('category_id', $this->visibleCategories()->modelKeys())
            ->where('type', $type)->where('slug', $slug)
            ->where('active', true)
            ->where(fn ($query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->firstOrFail();
    }

    private function visibleCategories(): Collection
    {
        return ContentDocumentCategory::query()->where('active', true)->orderBy('sort')->orderBy('id')->get();
    }
}
