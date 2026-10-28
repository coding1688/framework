<?php

namespace App\Models\System;

use App\Models\Concerns\HasLocalizedAttributes;
use Database\Factories\System\SystemScheduleTaskFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SystemScheduleTask extends Model
{
    /** @use HasFactory<SystemScheduleTaskFactory> */
    use HasFactory, HasLocalizedAttributes, SoftDeletes;

    protected $fillable = ['key', 'name_i18n', 'description_i18n', 'command', 'cron', 'timezone', 'active', 'without_overlapping', 'sort'];

    protected $appends = ['name', 'description'];

    protected function casts(): array
    {
        return ['name_i18n' => 'array', 'description_i18n' => 'array', 'active' => 'boolean', 'without_overlapping' => 'boolean'];
    }

    public function runs(): HasMany
    {
        return $this->hasMany(SystemScheduleRun::class, 'system_schedule_task_id');
    }

    protected function name(): Attribute
    {
        return Attribute::get(fn (): string => $this->localizedValue('name'));
    }

    protected function description(): Attribute
    {
        return Attribute::get(fn (): string => $this->localizedValue('description'));
    }
}
