<?php

namespace App\Http\Resources\Backend\Content;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContentDocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'category' => new ContentDocumentCategoryResource($this->whenLoaded('category')),
            'type' => $this->type,
            'slug' => $this->slug,
            'title' => $this->title,
            'title_i18n' => supportedTranslations($this->title_i18n),
            'summary' => $this->summary,
            'summary_i18n' => supportedTranslations($this->summary_i18n),
            'content' => $this->content,
            'content_i18n' => supportedTranslations($this->content_i18n),
            'published_at' => $this->published_at?->toIso8601String(),
            'sort' => $this->sort,
            'active' => $this->active,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
