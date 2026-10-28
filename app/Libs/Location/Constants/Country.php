<?php

namespace App\Libs\Location\Constants;

final class Country
{
    public const China = 'CN';

    public const TaiwanChina = 'TW';

    public const Japan = 'JP';

    public const SouthKorea = 'KR';

    public const UnitedStates = 'US';

    public const Canada = 'CA';

    public const Germany = 'DE';

    public const France = 'FR';

    public const Australia = 'AU';

    public const UnitedKingdom = 'GB';

    public const Netherlands = 'NL';

    public const India = 'IN';

    public const Philippines = 'PH';

    public const Singapore = 'SG';

    public const Malaysia = 'MY';

    public const Thailand = 'TH';

    public const Cambodia = 'KH';

    public const Laos = 'LA';

    public const Vietnam = 'VN';

    public const UnitedArabEmirates = 'AE';

    /** @return list<string> */
    public static function values(): array
    {
        return [
            self::China,
            self::TaiwanChina,
            self::Japan,
            self::SouthKorea,
            self::UnitedStates,
            self::Canada,
            self::Germany,
            self::France,
            self::Australia,
            self::UnitedKingdom,
            self::Netherlands,
            self::India,
            self::Philippines,
            self::Singapore,
            self::Malaysia,
            self::Thailand,
            self::Cambodia,
            self::Laos,
            self::Vietnam,
            self::UnitedArabEmirates,
        ];
    }

    public static function label(string $code): string
    {
        return __('common/location/const.country.'.$code);
    }

    /** @return list<array{code: string, label: string}> */
    public static function options(): array
    {
        return array_map(fn (string $code): array => ['code' => $code, 'label' => self::label($code)], self::values());
    }
}
