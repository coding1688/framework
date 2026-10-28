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
        $openai = $configuration['providers']['openai'] ?? [];

        return [
            'ai.enabled' => ['value_boolean' => $configuration['enabled']],
            'ai.provider' => ['value_string' => $configuration['default']],
            'ai.openai.text_model' => ['value_string' => $openai['text_model'] ?? 'gpt-4.1'],
            'ai.openai.image_model' => ['value_string' => $openai['image_model'] ?? 'gpt-image-1'],
            'ai.openai.moderation_model' => ['value_string' => $openai['moderation_model'] ?? 'omni-moderation-latest'],
            'ai.http_timeout' => ['value_number' => $openai['timeout'] ?? 90],
            'ai.image_timeout' => ['value_number' => $openai['image_timeout'] ?? 180],
            'ai.max_output_tokens' => ['value_number' => $openai['max_output_tokens'] ?? 16384],
        ];
    }

    public function hasValue(string $key): ?bool
    {
        if ($key !== 'ai.openai.api_key') {
            return null;
        }
        $configuration = $this->configuration->resolve((array) config('ai'));

        return trim((string) ($configuration['providers']['openai']['api_key'] ?? '')) !== '';
    }
}
