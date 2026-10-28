<?php

namespace App\Http\Resources\Backend\Member;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'username' => $this->username, 'name' => $this->name,
            'email' => $this->email, 'email_verified' => $this->hasVerifiedEmail(),
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'vip_level' => $this->effective_vip_level, 'vip_level_id' => $this->vip_level_id,
            'vip_name' => $this->vipLevel?->name, 'vip_status' => $this->vip_status,
            'vip_started_at' => $this->vip_started_at?->toIso8601String(), 'vip_expires_at' => $this->vip_expires_at?->toIso8601String(),
            'avatar' => $this->avatar, 'active' => $this->active,
            'providers' => $this->whenLoaded('socialAccounts', fn () => $this->socialAccounts->pluck('provider')->values()),
            'last_login_at' => $this->last_login_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            $this->mergeWhen($request->route()?->getActionMethod() === 'detail', [
                'last_login_ip' => $this->last_login_ip,
                'last_login_device' => $this->last_login_device,
                'password_changed_at' => $this->password_changed_at?->toIso8601String(),
            ]),
        ];
    }
}
