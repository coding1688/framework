<?php

namespace App\Providers;

use App\Libs\Ai\AiConfiguration;
use App\Libs\Ai\AiManager;
use App\Libs\Ai\Contracts\ExtractionProvider;
use App\Libs\Ai\Contracts\ImageAnalysisProvider;
use App\Libs\Ai\Contracts\ImageGenerationProvider;
use App\Libs\Ai\Contracts\ModerationProvider;
use App\Libs\Ai\Providers\OpenAiProvider;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AiManager::class, function (): AiManager {
            $configuration = $this->app->make(AiConfiguration::class)->resolve((array) config('ai'));

            return (new AiManager($configuration))->register(
                'openai',
                new OpenAiProvider((array) ($configuration['providers']['openai'] ?? []), $this->app->make(Factory::class)),
            );
        });
        foreach ([ExtractionProvider::class, ImageAnalysisProvider::class, ImageGenerationProvider::class, ModerationProvider::class] as $contract) {
            $this->app->alias(AiManager::class, $contract);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $migrationDirectories = glob(database_path('migrations/*'), GLOB_ONLYDIR);

        if ($migrationDirectories !== false) {
            $this->loadMigrationsFrom($migrationDirectories);
        }
    }
}
