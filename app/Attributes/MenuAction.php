<?php

namespace App\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
final class MenuAction
{
    /** @param array<string, string> $name */
    public function __construct(public array $name, public int $sort = 0) {}
}
