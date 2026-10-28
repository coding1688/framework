<?php

namespace App\Http\Resources\Backend\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'name' => $this->name, 'username' => $this->username, 'email' => $this->email,
            'security_password_set' => $this->hasSecurityPassword(),
            'active' => $this->active, 'is_super' => $this->is_super, 'locale' => $this->locale, 'preferences' => $this->themePreferences(),
            'is_leader' => $this->is_leader, 'is_board' => $this->isBoard(),
            'department_id' => $this->department_id, 'department' => $this->whenLoaded('department'),
            'last_login_at' => $this->last_login_at?->toIso8601String(),
            $this->mergeWhen($request->route()?->getActionMethod() === 'detail', [
                'created_ip' => $this->created_ip,
                'created_by_name' => $this->created_by_name,
                'last_login_ip' => $this->last_login_ip,
                'last_login_location' => $this->last_login_location,
                'last_login_device' => $this->last_login_device,
                'password_changed_at' => $this->password_changed_at?->toIso8601String(),
                'password_changed_ip' => $this->password_changed_ip,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
