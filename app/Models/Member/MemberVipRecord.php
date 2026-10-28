<?php

namespace App\Models\Member;

use App\Models\Admin\AdminUser;
use Database\Factories\Member\MemberVipRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberVipRecord extends Model
{
    /** @use HasFactory<MemberVipRecordFactory> */
    use HasFactory;

    protected $fillable = ['user_id', 'from_vip_level_id', 'to_vip_level_id', 'from_expires_at', 'to_expires_at', 'started_at', 'administrator_id', 'reason'];

    protected function casts(): array
    {
        return ['from_expires_at' => 'datetime', 'to_expires_at' => 'datetime', 'started_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fromLevel(): BelongsTo
    {
        return $this->belongsTo(MemberVipLevel::class, 'from_vip_level_id');
    }

    public function toLevel(): BelongsTo
    {
        return $this->belongsTo(MemberVipLevel::class, 'to_vip_level_id');
    }

    public function administrator(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'administrator_id');
    }
}
