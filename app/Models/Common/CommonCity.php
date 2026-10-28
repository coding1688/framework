<?php

namespace App\Models\Common;

use App\Models\Concerns\HasLocalizedAttributes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommonCity extends Model
{
    use HasLocalizedAttributes;

    protected $fillable = ['region_id', 'province_id', 'code', 'type', 'name_i18n', 'sort', 'active'];

    protected $appends = ['name'];

    protected function casts(): array
    {
        return ['name_i18n' => 'array', 'active' => 'boolean'];
    }

    protected function name(): Attribute
    {
        return Attribute::make(get: fn (): string => $this->localizedValue('name'));
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(CommonRegion::class, 'region_id');
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(CommonProvince::class, 'province_id');
    }
}
