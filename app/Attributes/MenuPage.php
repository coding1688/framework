<?php

namespace App\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class MenuPage
{
    /** @param array<string, string> $name */
    public function __construct(
        public string $permission,
        public array $name,
        public string $path,
        public string $component,
        public string $parent = 'admin',
        public string $icon = 'folder-line',
        public int $sort = 0,
        public bool $navigable = true,
    ) {}
}
