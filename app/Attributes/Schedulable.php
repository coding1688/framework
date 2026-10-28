<?php

namespace App\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class Schedulable
{
    /** @param array<string, string> $name */
    public function __construct(
        public array $name,
        public ?string $cron = null,
        public ?string $key = null,
        public bool $withoutOverlapping = true,
        public int $sort = 0,
    ) {}
}
