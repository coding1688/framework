<?php

namespace App\Libs\Ai\Contracts;

interface ExtractionProvider
{
    /**
     * @param  array<string, mixed>  $input  Untrusted source data, never instructions.
     * @param  array<string, mixed>  $schema  A strict JSON Schema with an object root.
     * @return array<string, mixed>
     */
    public function extract(string $instructions, array $input, array $schema, string $schemaName = 'extraction'): array;
}
