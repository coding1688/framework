<?php

namespace App\Models\Member;

use App\Services\Member\MemberWalletAmount;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberWalletTransaction extends Model
{
    protected $fillable = ['user_id', 'direction', 'currency', 'type', 'amount_minor', 'balance_before_minor', 'balance_after_minor', 'reference', 'remark', 'actor_type', 'actor', 'created_at'];

    public $timestamps = false;

    protected $appends = ['amount', 'balance_before', 'balance_after'];

    protected function casts(): array
    {
        return ['amount_minor' => 'integer', 'balance_before_minor' => 'integer', 'balance_after_minor' => 'integer', 'created_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(MemberWallet::class, 'wallet_id');
    }

    protected function amount(): Attribute
    {
        return Attribute::get(fn (): string => MemberWalletAmount::toDecimal($this->amount_minor, $this->currency));
    }

    protected function balanceBefore(): Attribute
    {
        return Attribute::get(fn (): string => MemberWalletAmount::toDecimal($this->balance_before_minor, $this->currency));
    }

    protected function balanceAfter(): Attribute
    {
        return Attribute::get(fn (): string => MemberWalletAmount::toDecimal($this->balance_after_minor, $this->currency));
    }
}
