<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberWalletTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'wallet_id' => $this->wallet_id, 'direction' => $this->direction,
            'currency' => $this->currency, 'type' => $this->type, 'amount' => $this->amount,
            'balance_before' => $this->balance_before, 'balance_after' => $this->balance_after,
            'reference' => $this->reference, 'remark' => $this->remark, 'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
