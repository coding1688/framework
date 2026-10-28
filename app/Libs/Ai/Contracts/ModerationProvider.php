<?php

namespace App\Libs\Ai\Contracts;

interface ModerationProvider
{
    /**
     * @param  list<string|array{url: string, detail?: string}>  $images
     * @return array{flagged: bool, categories: array<string, bool>, scores: array<string, float>, provider: string, model: string}
     */
    public function moderate(string $text, array $images = []): array;
}
