<?php

namespace App\Models\Member;

use App\Services\Member\MemberWalletAmount;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MemberWallet extends Model
{
    protected $fillable = ['user_id', 'currency', 'balance_minor'];

    protected $appends = ['balance'];

    protected function casts(): array
    {
        return ['balance_minor' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(MemberWalletTransaction::class, 'wallet_id');
    }

    protected function balance(): Attribute
    {
        return Attribute::get(fn (): string => MemberWalletAmount::toDecimal($this->balance_minor, $this->currency));
    }
}
