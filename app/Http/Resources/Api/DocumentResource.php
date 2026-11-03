<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $navigation = $request->routeIs('documents.navigation');

        return [
            'id' => $this->id,
            'type' => $this->type,
            'slug' => $this->slug,
            'title' => $this->title,
            'summary' => $this->summary,
            'sort' => $this->sort,
            ...($navigation ? [] : [
                'content' => $this->content,
                'category' => new DocumentCategoryResource($this->whenLoaded('category')),
                'published_at' => $this->published_at?->toIso8601String(),
                'updated_at' => $this->updated_at?->toIso8601String(),
            ]),
        ];
    }
}
