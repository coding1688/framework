<?php

namespace App\Attributes;

use Attribute;
use InvalidArgumentException;

#[Attribute(Attribute::TARGET_METHOD)]
final class RequireSecurityPassword
{
    public function __construct(public readonly string $mode = 'form')
    {
        if (! in_array($mode, ['form', 'dialog'], true)) {
            throw new InvalidArgumentException('Unsupported security password input mode.');
        }
    }

    /** @return array{required: true, mode: string} */
    public function metadata(): array
    {
        return ['required' => true, 'mode' => $this->mode];
    }
}
