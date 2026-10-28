<?php

namespace App\Libs\Settings\Contracts;

interface SettingResolver
{
    /** @return array<string, array<string, mixed>> */
    public function values(): array;

    public function hasValue(string $key): ?bool;
}
