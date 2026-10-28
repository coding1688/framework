<?php

namespace App\Libs\Ai\Sources;

use App\Libs\Ai\Exceptions\AiException;
use GuzzleHttp\Psr7\StreamDecoratorTrait;
use Psr\Http\Message\StreamInterface;

class BoundedSourceStream implements StreamInterface
{
    use StreamDecoratorTrait;

    private int $written = 0;

    private bool $hasExceeded = false;

    public function __construct(StreamInterface $stream, private readonly int $maximum)
    {
        $this->stream = $stream;
    }

    public function write(string $string): int
    {
        $this->checkLength($this->written + strlen($string));
        $written = $this->stream->write($string);
        $this->written += $written;

        return $written;
    }

    public function checkLength(int $length): void
    {
        if ($length > $this->maximum) {
            $this->hasExceeded = true;
            throw new AiException('source_too_large');
        }
    }

    public function exceeded(): bool
    {
        return $this->hasExceeded;
    }
}
