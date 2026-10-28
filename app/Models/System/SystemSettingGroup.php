<?php

namespace App\Models\System;

use App\Models\Concerns\HasLocalizedAttributes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SystemSettingGroup extends Model
{
    use HasFactory, HasLocalizedAttributes;

    protected $fillable = ['parent_id', 'code', 'name_i18n', 'description_i18n', 'active', 'sort'];

    protected $appends = ['name', 'description'];

    protected function casts(): array
    {
        return ['name_i18n' => 'array', 'description_i18n' => 'array', 'active' => 'boolean'];
    }

    protected function name(): Attribute
    {
        return Attribute::make(get: fn (): string => $this->localizedValue('name'));
    }

    protected function description(): Attribute
    {
        return Attribute::make(get: fn (): string => $this->localizedValue('description'));
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function definitions(): HasMany
    {
        return $this->hasMany(SystemSettingDefinition::class, 'group_id');
    }
}
