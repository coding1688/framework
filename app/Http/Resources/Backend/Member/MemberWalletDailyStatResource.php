<?php

namespace App\Http\Resources\Backend\Member;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberWalletDailyStatResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'user_id' => $this->user_id, 'stat_date' => $this->stat_date->toDateString(),
            'currency' => $this->currency, 'type' => $this->type,
            'credit_amount' => $this->credit_amount, 'debit_amount' => $this->debit_amount,
            'credit_count' => $this->credit_count, 'debit_count' => $this->debit_count,
            'transaction_count' => $this->transaction_count,
        ];
    }
}
