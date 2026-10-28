<?php

namespace App\Libs\Settings;

use App\Libs\Settings\Contracts\SettingResolver;

class SettingResolverRegistry
{
    /** @var list<SettingResolver> */
    private array $resolvers = [];

    public function register(SettingResolver $resolver): void
    {
        $this->resolvers[] = $resolver;
    }

    /** @return array<string, array<string, mixed>> */
    public function values(): array
    {
        $values = [];
        foreach ($this->resolvers as $resolver) {
            $values = array_replace($values, $resolver->values());
        }

        return $values;
    }

    public function hasValue(string $key): ?bool
    {
        foreach ($this->resolvers as $resolver) {
            $hasValue = $resolver->hasValue($key);
            if ($hasValue !== null) {
                return $hasValue;
            }
        }

        return null;
    }
}
