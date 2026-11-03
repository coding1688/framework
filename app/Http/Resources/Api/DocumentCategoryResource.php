<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'parent_id' => $this->parent_id,
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,
            'sort' => $this->sort,
            'documents' => DocumentResource::collection($this->whenLoaded('documents')),
            'children' => self::collection($this->whenLoaded('children')),
        ];
    }
}
