<?php

namespace App\Libs\Ai\Contracts;

interface ImageGenerationProvider
{
    /**
     * @param  array{size?: string, quality?: string, background?: string, output_format?: string, output_compression?: int, n?: int}  $options
     * @return array{images: list<array{base64: string, mime_type: string, revised_prompt: ?string}>, provider: string, model: string}
     */
    public function generateImage(string $prompt, array $options = []): array;
}
