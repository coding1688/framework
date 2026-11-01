<?php

namespace App\Services\Member;

use Illuminate\Validation\ValidationException;

class MemberWalletAmount
{
    public static function toMinor(string $amount, string $currency): int
    {
        $precision = self::precision($currency);
        $amount = trim($amount);
        if (! preg_match('/^(?:0|[1-9]\d*)(?:\.\d{1,'.$precision.'})?$/', $amount)) {
            throw self::invalidAmount();
        }
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');
        $scale = 10 ** $precision;
        $whole = ltrim($whole, '0') ?: '0';
        if (strlen($whole) > strlen((string) PHP_INT_MAX) || (int) $whole > intdiv(PHP_INT_MAX, $scale)) {
            throw self::invalidAmount();
        }
        $minor = (int) $whole * $scale + (int) str_pad($fraction, $precision, '0');
        if ($minor <= 0) {
            throw self::invalidAmount();
        }

        return $minor;
    }

    public static function toDecimal(int $minor, string $currency): string
    {
        $precision = self::precision($currency);
        $scale = 10 ** $precision;
        $whole = intdiv($minor, $scale);
        if ($precision === 0) {
            return (string) $whole;
        }

        return $whole.'.'.str_pad((string) ($minor % $scale), $precision, '0', STR_PAD_LEFT);
    }

    public static function precision(string $currency): int
    {
        $precision = config("member.wallet.currencies.$currency.precision");
        if (! is_int($precision)) {
            throw ValidationException::withMessages(['currency' => [__('validation.in', ['attribute' => 'currency'])]]);
        }

        return $precision;
    }

    private static function invalidAmount(): ValidationException
    {
        return ValidationException::withMessages(['amount' => [__('validation.regex', ['attribute' => 'amount'])]]);
    }
}
