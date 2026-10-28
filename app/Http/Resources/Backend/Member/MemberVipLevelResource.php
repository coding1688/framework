<?php

namespace App\Http\Resources\Backend\Member;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberVipLevelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'level' => $this->level, 'name' => $this->name, 'name_i18n' => $this->name_i18n,
            'description' => $this->description, 'description_i18n' => $this->description_i18n,
            'benefits' => $this->benefits, 'benefits_i18n' => $this->benefits_i18n,
            'duration_days' => $this->duration_days, 'active' => $this->active, 'sort' => $this->sort,
            'users_count' => $this->whenCounted('users'), 'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
