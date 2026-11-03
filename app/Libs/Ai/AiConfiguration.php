<?php

namespace App\Libs\Ai;

use App\Libs\Settings\SettingStore;

final class AiConfiguration
{
    public function __construct(private readonly SettingStore $settings) {}

    /**
     * @param  array<string, mixed>  $fallback
     * @return array<string, mixed>
     */
    public function resolve(array $fallback): array
    {
        $resolved = $fallback;
        $enabled = $this->settings->get('ai.enabled', $fallback['enabled'] ?? false);
        $resolved['enabled'] = is_bool($enabled) ? $enabled : filter_var($fallback['enabled'] ?? false, FILTER_VALIDATE_BOOL);
        $allowedProviders = array_keys($fallback['providers'] ?? []);
        $default = (string) ($fallback['default'] ?? 'openai');
        $provider = $this->settings->get('ai.provider', $default);
        $resolved['default'] = is_string($provider) && in_array($provider, $allowedProviders, true)
            ? $provider : (in_array($default, $allowedProviders, true) ? $default : ($allowedProviders[0] ?? 'openai'));
        foreach ($resolved['providers'] ?? [] as $name => $providerConfiguration) {
            if (! is_array($providerConfiguration)) {
                continue;
            }
            $key = $this->settings->get('ai.'.$name.'.api_key', $providerConfiguration['api_key'] ?? '');
            if (is_string($key) && strlen(trim($key)) <= 1024 && ! preg_match('/\s/', trim($key))) {
                $providerConfiguration['api_key'] = trim($key);
            }
            foreach (['text_model', 'image_model', 'moderation_model'] as $setting) {
                if (! array_key_exists($setting, $providerConfiguration)) {
                    continue;
                }
                $fallbackModel = $this->model($providerConfiguration[$setting]);
                if ($fallbackModel === null) {
                    continue;
                }
                $providerConfiguration[$setting] = $this->model($this->settings->get('ai.'.$name.'.'.$setting, $fallbackModel)) ?? $fallbackModel;
            }
            foreach ([
                'timeout' => ['ai.http_timeout', 90, 1, 180],
                'image_timeout' => ['ai.image_timeout', 180, 1, 300],
                'max_output_tokens' => ['ai.max_output_tokens', 16384, 256, 32768],
            ] as $setting => [$key, $defaultValue, $minimum, $maximum]) {
                $current = $this->integer($providerConfiguration[$setting] ?? $defaultValue, $minimum, $maximum) ?? $defaultValue;
                $providerConfiguration[$setting] = $this->integer($this->settings->get($key, $current), $minimum, $maximum) ?? $current;
            }
            $resolved['providers'][$name] = $providerConfiguration;
        }

        return $resolved;
    }

    private function model(mixed $value): ?string
    {
        return is_string($value) && preg_match('/^[a-zA-Z0-9][a-zA-Z0-9._:\/-]{0,127}$/D', trim($value)) ? trim($value) : null;
    }

    private function integer(mixed $value, int $minimum, int $maximum): ?int
    {
        if (! is_numeric($value) || ! is_finite((float) $value) || floor((float) $value) !== (float) $value || $value < $minimum || $value > $maximum) {
            return null;
        }

        return (int) $value;
    }
}
