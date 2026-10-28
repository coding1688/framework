<?php

namespace App\Libs\Ai\Contracts;

interface ImageAnalysisProvider
{
    /**
     * @param  list<string|array{url: string, detail?: string}>  $images  Public image URLs or base64 data URLs.
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>
     */
    public function analyzeImages(string $instructions, array $images, array $schema, string $schemaName = 'images'): array;
}
