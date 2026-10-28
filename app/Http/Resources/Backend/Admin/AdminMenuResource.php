<?php

namespace App\Http\Resources\Backend\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class AdminMenuResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return array_replace(Arr::only($this->resource->attributesToArray(), ['id', 'parent_id', 'name_i18n', 'type', 'permission', 'path', 'icon', 'component', 'sort', 'active', 'navigable', 'is_system', 'navigation_target_id', 'context', 'created_at', 'updated_at', 'name']), ['name_i18n' => supportedTranslations($this->name_i18n), 'route_name' => $this->permission, 'title' => $this->resource->getAttribute('navigation_title') ?? $this->name, 'canonical_permission' => $this->navigationTarget?->permission ?? $this->permission]);
    }
}
