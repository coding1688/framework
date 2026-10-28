<?php

namespace App\Libs\Ai;

use stdClass;

final class StructuredOutputValidator
{
    /** @param array<string, mixed> $schema */
    public function validate(mixed $value, array $schema): bool
    {
        return $this->matches($value, $schema, $schema, 0);
    }

    /**
     * @param  array<string, mixed>  $schema
     * @param  array<string, mixed>  $root
     */
    private function matches(mixed $value, array $schema, array $root, int $depth): bool
    {
        if ($depth > 64) {
            return false;
        }
        if (isset($schema['$ref'])) {
            $reference = $this->reference((string) $schema['$ref'], $root);

            return $reference !== null && $this->matches($value, $reference, $root, $depth + 1);
        }
        if (isset($schema['anyOf'])) {
            $matched = false;
            foreach ($schema['anyOf'] as $alternative) {
                if (is_array($alternative) && $this->matches($value, $alternative, $root, $depth + 1)) {
                    $matched = true;
                    break;
                }
            }
            if (! $matched) {
                return false;
            }
        }
        if (array_key_exists('enum', $schema) && ! in_array($value, $schema['enum'], true)) {
            return false;
        }
        if (isset($schema['type'])) {
            $types = is_array($schema['type']) ? $schema['type'] : [$schema['type']];
            if (! array_any($types, fn (mixed $type): bool => is_string($type) && $this->matchesType($value, $type))) {
                return false;
            }
        }
        if ($value instanceof stdClass) {
            $properties = get_object_vars($value);
            foreach ($schema['required'] ?? [] as $key) {
                if (! array_key_exists($key, $properties)) {
                    return false;
                }
            }
            foreach ($properties as $key => $property) {
                $definition = $schema['properties'][$key] ?? null;
                if ($definition === null) {
                    if (($schema['additionalProperties'] ?? true) === false) {
                        return false;
                    }
                    $definition = is_array($schema['additionalProperties'] ?? null) ? $schema['additionalProperties'] : null;
                }
                if (is_array($definition) && ! $this->matches($property, $definition, $root, $depth + 1)) {
                    return false;
                }
            }
        } elseif (is_array($value)) {
            if (isset($schema['minItems']) && count($value) < $schema['minItems'] || isset($schema['maxItems']) && count($value) > $schema['maxItems']) {
                return false;
            }
            if (($schema['uniqueItems'] ?? false) && count(array_unique(array_map(fn (mixed $item): string => serialize($item), $value))) !== count($value)) {
                return false;
            }
            if (isset($schema['items']) && is_array($schema['items'])) {
                foreach ($value as $item) {
                    if (! $this->matches($item, $schema['items'], $root, $depth + 1)) {
                        return false;
                    }
                }
            }
        } elseif (is_string($value)) {
            $length = mb_strlen($value);
            if (isset($schema['minLength']) && $length < $schema['minLength'] || isset($schema['maxLength']) && $length > $schema['maxLength']) {
                return false;
            }
            if (isset($schema['pattern']) && preg_match('~'.str_replace('~', '\\~', $schema['pattern']).'~u', $value) !== 1) {
                return false;
            }
        } elseif (is_int($value) || is_float($value)) {
            if (isset($schema['minimum']) && $value < $schema['minimum'] || isset($schema['maximum']) && $value > $schema['maximum']) {
                return false;
            }
            if (isset($schema['exclusiveMinimum']) && $value <= $schema['exclusiveMinimum'] || isset($schema['exclusiveMaximum']) && $value >= $schema['exclusiveMaximum']) {
                return false;
            }
            if (isset($schema['multipleOf'])) {
                $divisor = (float) $schema['multipleOf'];
                if ($divisor <= 0 || abs($value / $divisor - round($value / $divisor)) > 0.00000001) {
                    return false;
                }
            }
        }

        return true;
    }

    private function matchesType(mixed $value, string $type): bool
    {
        return match ($type) {
            'object' => $value instanceof stdClass,
            'array' => is_array($value) && array_is_list($value),
            'string' => is_string($value),
            'number' => (is_int($value) || is_float($value)) && is_finite((float) $value),
            'integer' => is_int($value) || is_float($value) && is_finite($value) && floor($value) === $value,
            'boolean' => is_bool($value),
            'null' => $value === null,
            default => false,
        };
    }

    /**
     * @param  array<string, mixed>  $root
     * @return array<string, mixed>|null
     */
    private function reference(string $pointer, array $root): ?array
    {
        if ($pointer === '#') {
            return $root;
        }
        if (! str_starts_with($pointer, '#/')) {
            return null;
        }
        $definition = $root;
        foreach (explode('/', substr($pointer, 2)) as $segment) {
            $key = str_replace(['~1', '~0'], ['/', '~'], $segment);
            if (! is_array($definition) || ! array_key_exists($key, $definition)) {
                return null;
            }
            $definition = $definition[$key];
        }

        return is_array($definition) ? $definition : null;
    }
}
