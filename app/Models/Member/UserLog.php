<?php

namespace App\Models\Member;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserLog extends Model
{
    public const DEVICE_TYPE_WEB = 'WEB';

    public const DEVICE_TYPE_H5 = 'H5';

    public const DEVICE_TYPE_ANDROID = 'ANDROID';

    public const DEVICE_TYPE_IOS = 'IOS';

    public const DEVICE_TYPE_HARMONY = 'HARMONY';

    protected $fillable = ['action', 'actor_type', 'actor', 'ip', 'user_agent', 'device_type', 'context', 'created_at'];

    public $timestamps = false;

    protected function casts(): array
    {
        return ['context' => 'array', 'created_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
