<?php

namespace App\Http\Resources\Backend\Content;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContentDocumentCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'parent_id' => $this->parent_id,
            'slug' => $this->slug,
            'name' => $this->name,
            'name_i18n' => supportedTranslations($this->name_i18n),
            'description' => $this->description,
            'description_i18n' => supportedTranslations($this->description_i18n),
            'sort' => $this->sort,
            'active' => $this->active,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
