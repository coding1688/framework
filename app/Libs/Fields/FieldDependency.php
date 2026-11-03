<?php

namespace App\Libs\Fields;

final class FieldDependency
{
    public static function satisfied(mixed $value, ?string $expectedValue = null): bool
    {
        if ($value === null || $value === '' || $value === []) {
            return false;
        }
        if (is_array($value) && ($expectedValue === null || $expectedValue === '')) {
            foreach ($value as $item) {
                if ($item !== null && $item !== '') {
                    return true;
                }
            }

            return false;
        }
        if ($expectedValue === null || $expectedValue === '') {
            return true;
        }

        $expected = self::comparable($expectedValue);
        if (is_array($value)) {
            foreach ($value as $item) {
                if (self::comparable($item) === $expected) {
                    return true;
                }
            }

            return false;
        }

        return self::comparable($value) === $expected;
    }

    private static function comparable(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return is_scalar($value) ? (string) $value : '';
    }
}
