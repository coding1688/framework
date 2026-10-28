<?php

namespace App\Models\Common;

use App\Models\Concerns\HasLocalizedAttributes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommonRegion extends Model
{
    use HasLocalizedAttributes;

    protected $fillable = ['country_code', 'code', 'name_i18n', 'sort', 'active'];

    protected $appends = ['name'];

    protected function casts(): array
    {
        return ['name_i18n' => 'array', 'active' => 'boolean'];
    }

    protected function name(): Attribute
    {
        return Attribute::make(get: fn (): string => $this->localizedValue('name'));
    }

    public function provinces(): HasMany
    {
        return $this->hasMany(CommonProvince::class, 'region_id');
    }

    public function cities(): HasMany
    {
        return $this->hasMany(CommonCity::class, 'region_id');
    }
}
