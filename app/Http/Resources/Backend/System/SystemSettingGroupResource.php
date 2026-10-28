<?php

namespace App\Http\Resources\Backend\System;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SystemSettingGroupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'parent_id' => $this->parent_id, 'code' => $this->code,
            'name' => $this->name, 'name_i18n' => supportedTranslations($this->name_i18n), 'description' => $this->description, 'description_i18n' => supportedTranslations($this->description_i18n),
            'active' => $this->active, 'sort' => $this->sort,
            'ancestor_ids' => $this->ancestor_ids ?? [], 'depth' => $this->depth ?? 0, 'path' => $this->path ?? [],
            'effective_active' => $this->effective_active ?? $this->active, 'disabled' => $this->disabled ?? ! $this->active];
    }
}
