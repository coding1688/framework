<?php

namespace App\Http\Resources\Backend\Member;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'action' => $this->action,
            'actor_type' => $this->actor_type,
            'actor' => $this->actor,
            'ip' => $this->ip,
            'user_agent' => $this->user_agent,
            'device_type' => $this->device_type,
            'context' => $this->context,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
