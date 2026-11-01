<?php

namespace App\Models\Member;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserRestriction extends Model
{
    public const TYPE_FREEZE = 'login';

    protected $fillable = ['type', 'active_key', 'reason', 'created_by', 'created_at', 'lifted_reason', 'lifted_by', 'lifted_at'];

    public $timestamps = false;

    protected function casts(): array
    {
        return ['created_at' => 'datetime', 'lifted_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
