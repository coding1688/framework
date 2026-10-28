<?php

namespace App\Http\Resources\Backend\Member;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberVipRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'user_id' => $this->user_id,
            'from_level' => $this->fromLevel ? ['id' => $this->fromLevel->id, 'level' => $this->fromLevel->level, 'name' => $this->fromLevel->name] : null,
            'to_level' => $this->toLevel ? ['id' => $this->toLevel->id, 'level' => $this->toLevel->level, 'name' => $this->toLevel->name] : null,
            'from_expires_at' => $this->from_expires_at?->toIso8601String(), 'to_expires_at' => $this->to_expires_at?->toIso8601String(),
            'started_at' => $this->started_at?->toIso8601String(), 'reason' => $this->reason,
            'administrator' => $this->administrator ? ['id' => $this->administrator->id, 'name' => $this->administrator->name] : null,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
