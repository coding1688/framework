<?php

namespace App\Providers;

use App\Libs\Common\CacheGroupRegistry;
use App\Libs\File\FileReferenceOwnerRegistry;
use App\Libs\Settings\SettingResolverRegistry;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CacheGroupRegistry::class);
        $this->app->singleton(FileReferenceOwnerRegistry::class);
        $this->app->singleton(SettingResolverRegistry::class);
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
