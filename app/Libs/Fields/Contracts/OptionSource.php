<?php

namespace App\Libs\Fields\Contracts;

use App\Libs\Fields\Options\OptionContext;

interface OptionSource
{
    /** @return array<string, string> */
    public function options(OptionContext $context): array;
}
