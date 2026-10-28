<?php

namespace App\Models\Member;

use App\Notifications\ResetUserPasswordNotification;
use Database\Factories\Member\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['username', 'name', 'email', 'password', 'avatar_url'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $appends = ['avatar'];

    protected $attributes = ['active' => true, 'auth_version' => 1];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime', 'password' => 'hashed', 'vip_level' => 'integer',
            'active' => 'boolean', 'last_login_at' => 'datetime', 'password_changed_at' => 'datetime',
            'notification_preferences' => 'array', 'vip_started_at' => 'datetime', 'vip_expires_at' => 'datetime',
        ];
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetUserPasswordNotification($token));
    }

    public function vipLevel(): BelongsTo
    {
        return $this->belongsTo(MemberVipLevel::class, 'vip_level_id');
    }

    public function vipRecords(): HasMany
    {
        return $this->hasMany(MemberVipRecord::class);
    }

    protected function effectiveVipLevel(): Attribute
    {
        return Attribute::get(fn (): int => $this->vip_status === 'expired' || $this->vip_status === 'none' ? 0 : (int) $this->vip_level);
    }

    protected function vipStatus(): Attribute
    {
        return Attribute::get(function (): string {
            if ($this->vip_level_id === null || ! $this->vip_level) {
                return 'none';
            }
            if ($this->vip_expires_at?->isPast()) {
                return 'expired';
            }

            return $this->vip_expires_at === null ? 'permanent' : 'active';
        });
    }

    protected function avatar(): Attribute
    {
        return Attribute::get(fn (): string => $this->avatar_url ?: asset('images/default-avatar.svg'));
    }

    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }
}
