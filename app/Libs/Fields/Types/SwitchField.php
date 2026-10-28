<?php

namespace App\Libs\Fields\Types;

use App\Libs\Fields\Contracts\FieldType;
use App\Libs\Fields\Contracts\FieldValueValidator;
use App\Libs\Fields\Validators\BooleanValidator;

final class SwitchField implements FieldType
{
    public function key(): string
    {
        return 'switch';
    }

    public function component(): string
    {
        return 'switch';
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
        return 'boolean';
    }

    public function validationSchema(bool $multiple): array
    {
        return [];
    }

    public function validator(bool $multiple): FieldValueValidator
    {
        return new BooleanValidator;
    }
}
