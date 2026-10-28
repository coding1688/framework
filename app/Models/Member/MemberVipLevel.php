<?php

namespace App\Models\Member;

use App\Models\Concerns\HasLocalizedAttributes;
use Database\Factories\Member\MemberVipLevelFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MemberVipLevel extends Model
{
    /** @use HasFactory<MemberVipLevelFactory> */
    use HasFactory, HasLocalizedAttributes;

    protected $fillable = ['level', 'name_i18n', 'description_i18n', 'benefits_i18n', 'duration_days', 'active', 'sort'];

    protected $appends = ['name', 'description', 'benefits'];

    protected function casts(): array
    {
        return ['name_i18n' => 'array', 'description_i18n' => 'array', 'benefits_i18n' => 'array', 'active' => 'boolean'];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'vip_level_id');
    }

    protected function name(): Attribute
    {
        return Attribute::get(fn (): string => $this->localizedValue('name'));
    }

    protected function description(): Attribute
    {
        return Attribute::get(fn (): string => $this->localizedValue('description'));
    }

    protected function benefits(): Attribute
    {
        return Attribute::get(fn (): string => $this->localizedValue('benefits'));
    }
}
