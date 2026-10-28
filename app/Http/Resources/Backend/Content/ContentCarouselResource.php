<?php

namespace App\Http\Resources\Backend\Content;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContentCarouselResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'title_i18n' => supportedTranslations($this->title_i18n),
            'image_id' => $this->image_id,
            'image_url' => $this->image?->publicUrl(),
            'link_url' => $this->link_url,
            'sort' => $this->sort,
            'active' => $this->active,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
