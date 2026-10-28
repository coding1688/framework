<?php

namespace App\Libs\Fields\Types;

use App\Libs\Fields\Contracts\FieldType;
use App\Libs\Fields\Contracts\FieldValueValidator;
use App\Libs\Fields\Validators\LocalizedTextValidator;

final class LocalizedTextField implements FieldType
{
    /** @param list<string> $locales */
    public function __construct(private readonly array $locales = ['en']) {}

    public function key(): string
    {
        return 'localized_text';
    }

    public function component(): string
    {
        return 'localized-input';
    }

    public function supportsMultiple(): bool
    {
        return false;
    }

    public function defaultMultiple(): bool
    {
        return false;
    }

    public function usesOptions(bool $multiple): bool
    {
        return false;
    }

    public function dataType(bool $multiple): string
    {
        return 'localized_text';
    }

    public function validationSchema(bool $multiple): array
    {
        return [
            ['key' => 'min_length', 'type' => 'integer', 'default' => 0, 'min' => 0, 'max' => 512],
            ['key' => 'max_length', 'type' => 'integer', 'default' => 512, 'min' => 1, 'max' => 512],
            ['key' => 'supported_locales', 'type' => 'locales', 'default' => $this->locales, 'options' => $this->locales],
            ['key' => 'required_locales', 'type' => 'locales', 'default' => ['en'], 'options' => $this->locales],
        ];
    }

    public function validator(bool $multiple): FieldValueValidator
    {
        return new LocalizedTextValidator;
    }
}
