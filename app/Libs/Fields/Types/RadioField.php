<?php

namespace App\Libs\Fields\Types;

use App\Libs\Fields\Contracts\FieldType;
use App\Libs\Fields\Contracts\FieldValueValidator;
use App\Libs\Fields\Validators\OptionValidator;

final class RadioField implements FieldType
{
    public function key(): string
    {
        return 'radio';
    }

    public function component(): string
    {
        return 'radio';
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
        return true;
    }

    public function dataType(bool $multiple): string
    {
        return 'select';
    }

    public function validationSchema(bool $multiple): array
    {
        return [];
    }

    public function validator(bool $multiple): FieldValueValidator
    {
        return new OptionValidator(false);
    }
}
