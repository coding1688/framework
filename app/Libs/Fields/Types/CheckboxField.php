<?php

namespace App\Libs\Fields\Types;

use App\Libs\Fields\Contracts\FieldType;
use App\Libs\Fields\Contracts\FieldValueValidator;
use App\Libs\Fields\Validators\OptionValidator;

final class CheckboxField implements FieldType
{
    public function key(): string
    {
        return 'checkbox';
    }

    public function component(): string
    {
        return 'checkbox';
    }

    public function supportsMultiple(): bool
    {
        return false;
    }

    public function defaultMultiple(): bool
    {
        return true;
    }

    public function required(bool $multiple, array $validation): bool
    {
        return ($validation['min_selected'] ?? 0) > 0;
    }

    public function usesOptions(bool $multiple): bool
    {
        return true;
    }

    public function dataType(bool $multiple): string
    {
        return 'multi_select';
    }

    public function validationSchema(bool $multiple): array
    {
        return [
            ['key' => 'min_selected', 'type' => 'integer', 'default' => 0, 'min' => 0, 'max' => 64],
            ['key' => 'max_selected', 'type' => 'integer', 'default' => 64, 'min' => 1, 'max' => 64],
        ];
    }

    public function validator(bool $multiple): FieldValueValidator
    {
        return new OptionValidator(true);
    }
}
