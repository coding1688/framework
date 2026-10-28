<?php

namespace App\Models\Admin;

use App\Models\Concerns\HasLocalizedAttributes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminMenu extends Model
{
    use HasLocalizedAttributes;

    protected $table = 'admin_menus';

    protected $fillable = ['name_i18n', 'parent_id', 'type', 'permission', 'path', 'icon', 'component', 'sort', 'active', 'navigable'];

    protected $appends = ['name'];

    protected function casts(): array
    {
        return ['name_i18n' => 'array', 'route_names' => 'array', 'context' => 'array', 'navigation_target_id' => 'integer', 'active' => 'boolean', 'navigable' => 'boolean', 'is_system' => 'boolean'];
    }

    public function navigationTarget(): BelongsTo
    {
        return $this->belongsTo(self::class, 'navigation_target_id');
    }

    protected function name(): Attribute
    {
        return Attribute::make(get: fn (): string => $this->localizedValue('name'));
    }
}
