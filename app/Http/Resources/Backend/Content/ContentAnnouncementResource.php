<?php

namespace App\Http\Resources\Backend\Content;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContentAnnouncementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'title_i18n' => supportedTranslations($this->title_i18n),
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
