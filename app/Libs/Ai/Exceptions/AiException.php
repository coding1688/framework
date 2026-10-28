<?php

namespace App\Libs\Ai\Exceptions;

use RuntimeException;
use Throwable;

final class AiException extends RuntimeException
{
    public function __construct(public readonly string $errorCode, ?Throwable $previous = null)
    {
        parent::__construct((string) __('common/ai/main.'.$errorCode), 0, $previous);
    }
}
