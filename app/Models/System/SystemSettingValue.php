<?php

namespace App\Models\System;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemSettingValue extends Model
{
    use HasFactory;

    protected $fillable = ['setting_definition_id', 'payload'];

    protected $hidden = ['payload'];

    protected function casts(): array
    {
        return ['payload' => 'encrypted:array'];
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(SystemSettingDefinition::class, 'setting_definition_id');
    }
}
