<?php

namespace App\Http\Resources\Backend\Member;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberWalletTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'user_id' => $this->user_id, 'wallet_id' => $this->wallet_id,
            'direction' => $this->direction, 'currency' => $this->currency, 'type' => $this->type,
            'amount' => $this->amount, 'balance_before' => $this->balance_before, 'balance_after' => $this->balance_after,
            'reference' => $this->reference, 'remark' => $this->remark,
            'actor_type' => $this->actor_type, 'actor' => $this->actor, 'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
