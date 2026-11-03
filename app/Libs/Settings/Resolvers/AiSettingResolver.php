<?php

namespace App\Libs\Settings\Resolvers;

use App\Libs\Ai\AiConfiguration;
use App\Libs\Settings\Contracts\SettingResolver;

class AiSettingResolver implements SettingResolver
{
    public function __construct(private readonly AiConfiguration $configuration) {}

    public function values(): array
    {
        $configuration = $this->configuration->resolve((array) config('ai'));
        $defaultProvider = $configuration['providers'][$configuration['default']] ?? [];
        $values = [
            'ai.enabled' => ['value_boolean' => $configuration['enabled']],
            'ai.provider' => ['value_string' => $configuration['default']],
            'ai.http_timeout' => ['value_number' => $defaultProvider['timeout'] ?? 90],
            'ai.image_timeout' => ['value_number' => $defaultProvider['image_timeout'] ?? 180],
            'ai.max_output_tokens' => ['value_number' => $defaultProvider['max_output_tokens'] ?? 16384],
        ];
        foreach ($configuration['providers'] ?? [] as $name => $provider) {
            foreach (['text_model', 'image_model', 'moderation_model'] as $setting) {
                if (isset($provider[$setting]) && is_string($provider[$setting])) {
                    $values['ai.'.$name.'.'.$setting] = ['value_string' => $provider[$setting]];
                }
            }
        }

        return $values;
    }

    public function hasValue(string $key): ?bool
    {
        if (! preg_match('/^ai\.([a-z][a-z0-9_-]{0,63})\.api_key$/D', $key, $matches)) {
            return null;
        }
        $configuration = $this->configuration->resolve((array) config('ai'));
        if (! array_key_exists($matches[1], $configuration['providers'] ?? [])) {
            return null;
        }

        return trim((string) ($configuration['providers'][$matches[1]]['api_key'] ?? '')) !== '';
    }
}
