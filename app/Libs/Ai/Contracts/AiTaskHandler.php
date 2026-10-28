<?php

namespace App\Libs\Ai\Contracts;

interface AiTaskHandler
{
    /** @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function handle(array $payload): array;
}
