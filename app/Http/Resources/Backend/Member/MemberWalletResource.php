<?php

namespace App\Http\Resources\Backend\Member;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberWalletResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'user_id' => $this->user_id, 'currency' => $this->currency,
            'balance' => $this->balance, 'created_at' => $this->created_at?->toIso8601String(), 'updated_at' => $this->updated_at?->toIso8601String(),
            'user' => $this->user ? ['id' => $this->user->id, 'username' => $this->user->username, 'name' => $this->user->name, 'email' => $this->user->email, 'is_test' => $this->user->is_test] : null,
        ];
    }
}
