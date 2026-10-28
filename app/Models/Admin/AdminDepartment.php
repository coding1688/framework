<?php

namespace App\Models\Admin;

use App\Models\Concerns\HasLocalizedAttributes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdminDepartment extends Model
{
    use HasLocalizedAttributes;

    protected $table = 'admin_departments';

    protected $fillable = ['name_i18n', 'parent_id', 'sort', 'active'];

    protected $appends = ['name'];

    protected function casts(): array
    {
        return ['name_i18n' => 'array', 'active' => 'boolean'];
    }

    protected function name(): Attribute
    {
        return Attribute::make(get: fn (): string => $this->localizedValue('name'));
    }

    public function menus(): BelongsToMany
    {
        return $this->belongsToMany(AdminMenu::class, 'admin_department_menu', 'department_id', 'menu_id');
    }

    public function leaders(): HasMany
    {
        return $this->hasMany(AdminUser::class, 'department_id')->where('is_leader', true);
    }

    public function effectiveMenuIds(): array
    {
        $allowed = AdminMenu::where('active', true)->whereNull('navigation_target_id')->pluck('id')->all();
        $current = $this;
        $seen = [];
        while ($current) {
            if (! $current->active || in_array($current->id, $seen, true)) {
                return [];
            }
            $seen[] = $current->id;
            if ($current->code === 'board') {
                return array_values($allowed);
            }
            $allowed = array_intersect($allowed, $current->menus()->pluck('admin_menus.id')->all());
            $current = $current->parent_id ? self::find($current->parent_id) : null;
        }

        return [];
    }
}
