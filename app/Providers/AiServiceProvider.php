<?php

namespace App\Providers;

use App\Libs\Ai\AiConfiguration;
use App\Libs\Ai\AiManager;
use App\Libs\Ai\Contracts\ExtractionProvider;
use App\Libs\Ai\Contracts\ImageAnalysisProvider;
use App\Libs\Ai\Contracts\ImageGenerationProvider;
use App\Libs\Ai\Contracts\ModerationProvider;
use App\Libs\Ai\Providers\ClaudeProvider;
use App\Libs\Ai\Providers\KimiProvider;
use App\Libs\Ai\Providers\OpenAiProvider;
use App\Libs\Settings\Resolvers\AiSettingResolver;
use App\Libs\Settings\SettingResolverRegistry;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\ServiceProvider;

class AiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AiManager::class, function (): AiManager {
            $configuration = $this->app->make(AiConfiguration::class)->resolve((array) config('ai'));

            return (new AiManager($configuration))
                ->register('openai', new OpenAiProvider((array) ($configuration['providers']['openai'] ?? []), $this->app->make(Factory::class)))
                ->register('claude', new ClaudeProvider((array) ($configuration['providers']['claude'] ?? []), $this->app->make(Factory::class)))
                ->register('kimi', new KimiProvider((array) ($configuration['providers']['kimi'] ?? []), $this->app->make(Factory::class)));
        });
        foreach ([ExtractionProvider::class, ImageAnalysisProvider::class, ImageGenerationProvider::class, ModerationProvider::class] as $contract) {
            $this->app->alias(AiManager::class, $contract);
        }
    }

    public function boot(SettingResolverRegistry $settings, AiSettingResolver $resolver): void
    {
        $settings->register($resolver);
    }
}
