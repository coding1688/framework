<?php

namespace App\Models\Member;

use App\Services\Member\MemberWalletAmount;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberWalletDailyStat extends Model
{
    protected $fillable = ['user_id', 'stat_date', 'currency', 'type', 'credit_amount_minor', 'debit_amount_minor', 'credit_count', 'debit_count'];

    protected $appends = ['credit_amount', 'debit_amount', 'transaction_count'];

    protected function casts(): array
    {
        return [
            'stat_date' => 'date', 'credit_amount_minor' => 'integer', 'debit_amount_minor' => 'integer',
            'credit_count' => 'integer', 'debit_count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function creditAmount(): Attribute
    {
        return Attribute::get(fn (): string => MemberWalletAmount::toDecimal($this->credit_amount_minor, $this->currency));
    }

    protected function debitAmount(): Attribute
    {
        return Attribute::get(fn (): string => MemberWalletAmount::toDecimal($this->debit_amount_minor, $this->currency));
    }

    protected function transactionCount(): Attribute
    {
        return Attribute::get(fn (): int => $this->credit_count + $this->debit_count);
    }
}
