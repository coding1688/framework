<?php

namespace App\Models\System;

use App\Models\Concerns\HasLocalizedAttributes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class SystemNotificationTemplate extends Model
{
    use HasLocalizedAttributes;

    protected $fillable = ['code', 'name_i18n', 'subject_i18n', 'content_i18n', 'channels', 'active'];

    protected $appends = ['name'];

    protected function casts(): array
    {
        return ['name_i18n' => 'array', 'subject_i18n' => 'array', 'content_i18n' => 'array', 'channels' => 'array', 'active' => 'boolean'];
    }

    protected function name(): Attribute
    {
        return Attribute::get(fn (): string => $this->localizedValue('name'));
    }
}
