<?php

namespace App\Http\Resources\Backend\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class AdminDepartmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            ...array_replace(Arr::only($this->resource->attributesToArray(), ['id', 'parent_id', 'name_i18n', 'code', 'sort', 'active', 'created_at', 'updated_at', 'name', 'can_manage', 'effective_menu_ids']), ['name_i18n' => supportedTranslations($this->name_i18n)]),
            'menus' => $this->whenLoaded('menus'),
            'leaders' => $this->whenLoaded('leaders'),
        ];
    }
}
