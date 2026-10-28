<?php

namespace App\Models\System;

use App\Libs\Fields\FieldRegistry;
use App\Libs\Fields\Options\OptionContext;
use App\Models\Concerns\HasLocalizedAttributes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use InvalidArgumentException;

class SystemSettingDefinition extends Model
{
    use HasFactory, HasLocalizedAttributes;

    protected $fillable = ['group_id', 'key', 'name_i18n', 'description_i18n', 'input_type', 'multiple', 'option_source_type', 'option_source_key', 'validation', 'options_i18n', 'default_value', 'secret', 'active', 'sort'];

    protected $hidden = ['default_value'];

    protected $appends = ['name', 'description', 'options'];

    protected function casts(): array
    {
        return ['name_i18n' => 'array', 'description_i18n' => 'array', 'validation' => 'array', 'options_i18n' => 'array', 'default_value' => 'array', 'multiple' => 'boolean', 'secret' => 'boolean', 'active' => 'boolean'];
    }

    protected function name(): Attribute
    {
        return Attribute::make(get: fn (): string => $this->localizedValue('name'));
    }

    protected function description(): Attribute
    {
        return Attribute::make(get: fn (): string => $this->localizedValue('description'));
    }

    protected function options(): Attribute
    {
        return Attribute::make(get: function (): array {
            try {
                return app(FieldRegistry::class)->optionSources()->resolve(
                    $this->option_source_type ?? 'manual', $this->option_source_key,
                    $this->options_i18n ?? [], app(OptionContext::class),
                );
            } catch (InvalidArgumentException) {
                return [];
            }
        });
    }

    protected function displayI18n(): Attribute
    {
        return Attribute::make(get: function (): array {
            if ($this->secret || $this->input_type !== 'localized_text') {
                return [];
            }
            $payload = $this->getAttribute('resolved_value') ?? ($this->relationLoaded('value') ? $this->value?->payload : null) ?? $this->default_value;

            return $payload['value_i18n'] ?? [];
        });
    }

    protected function displayValue(): Attribute
    {
        return Attribute::make(get: fn (): ?string => $this->secret || $this->input_type !== 'localized_text' ? null : $this->localizedValue('display'));
    }

    public function toArray(): array
    {
        $attributes = parent::toArray();
        if (array_key_exists('options', $attributes)) {
            $attributes['options'] = (object) $attributes['options'];
        }

        return $attributes;
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(SystemSettingGroup::class, 'group_id');
    }

    public function value(): HasOne
    {
        return $this->hasOne(SystemSettingValue::class, 'setting_definition_id');
    }
}
