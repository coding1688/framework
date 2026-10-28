<?php

namespace App\Libs\Ai;

use App\Libs\Ai\Contracts\AiProvider;
use App\Libs\Ai\Contracts\ExtractionProvider;
use App\Libs\Ai\Contracts\ImageAnalysisProvider;
use App\Libs\Ai\Contracts\ImageGenerationProvider;
use App\Libs\Ai\Contracts\ModerationProvider;
use App\Libs\Ai\Exceptions\AiException;
use Closure;
use InvalidArgumentException;

final class AiManager implements ExtractionProvider, ImageAnalysisProvider, ImageGenerationProvider, ModerationProvider
{
    /** @var array<string, AiProvider|Closure> */
    private array $providers = [];

    /** @param array<string, mixed> $configuration */
    public function __construct(private readonly array $configuration) {}

    public function register(string $name, AiProvider $provider): self
    {
        $this->validateProviderName($name);
        $this->providers[$name] = $provider;

        return $this;
    }

    /** @param Closure(): AiProvider $factory */
    public function extend(string $name, Closure $factory): self
    {
        $this->validateProviderName($name);
        $this->providers[$name] = $factory;

        return $this;
    }

    public function provider(?string $name = null): AiProvider
    {
        $name ??= (string) ($this->configuration['default'] ?? 'openai');
        $provider = $this->providers[$name] ?? throw new AiException('unsupported');

        if ($provider instanceof Closure) {
            $provider = $provider();
            if (! $provider instanceof AiProvider) {
                throw new InvalidArgumentException('An AI provider must implement AiProvider.');
            }
            $this->providers[$name] = $provider;
        }

        return $provider;
    }

    /** @return array{provider: string, enabled: bool, configured: bool, extraction: bool, image_analysis: bool, moderation: bool, image_generation: bool} */
    public function capabilities(): array
    {
        $providerName = (string) ($this->configuration['default'] ?? 'openai');
        $enabled = $this->enabled();
        $provider = isset($this->providers[$providerName]) ? $this->provider($providerName) : null;
        $configured = $provider?->configured() ?? false;
        $available = $enabled && $configured;

        return [
            'provider' => $providerName,
            'enabled' => $enabled,
            'configured' => $configured,
            'extraction' => $available && $provider instanceof ExtractionProvider,
            'image_analysis' => $available && $provider instanceof ImageAnalysisProvider,
            'moderation' => $available && $provider instanceof ModerationProvider,
            'image_generation' => $available && $provider instanceof ImageGenerationProvider,
        ];
    }

    public function extract(string $instructions, array $input, array $schema, string $schemaName = 'extraction'): array
    {
        /** @var ExtractionProvider $provider */
        $provider = $this->capability(ExtractionProvider::class);

        return $provider->extract($instructions, $input, $schema, $schemaName);
    }

    public function analyzeImages(string $instructions, array $images, array $schema, string $schemaName = 'images'): array
    {
        /** @var ImageAnalysisProvider $provider */
        $provider = $this->capability(ImageAnalysisProvider::class);

        return $provider->analyzeImages($instructions, $images, $schema, $schemaName);
    }

    public function moderate(string $text, array $images = []): array
    {
        /** @var ModerationProvider $provider */
        $provider = $this->capability(ModerationProvider::class);

        return $provider->moderate($text, $images);
    }

    public function generateImage(string $prompt, array $options = []): array
    {
        /** @var ImageGenerationProvider $provider */
        $provider = $this->capability(ImageGenerationProvider::class);

        return $provider->generateImage($prompt, $options);
    }

    private function enabled(): bool
    {
        return filter_var($this->configuration['enabled'] ?? false, FILTER_VALIDATE_BOOL);
    }

    /** @param class-string $contract */
    private function capability(string $contract): AiProvider
    {
        if (! $this->enabled()) {
            throw new AiException('not_configured');
        }
        $provider = $this->provider();
        if (! $provider->configured()) {
            throw new AiException('not_configured');
        }
        if (! $provider instanceof $contract) {
            throw new AiException('unsupported');
        }

        return $provider;
    }

    private function validateProviderName(string $name): void
    {
        if (! preg_match('/^[a-z][a-z0-9_-]{0,63}$/D', $name)) {
            throw new InvalidArgumentException('Invalid AI provider name.');
        }
    }
}
