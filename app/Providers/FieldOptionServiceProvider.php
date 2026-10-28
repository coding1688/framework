<?php

namespace App\Providers;

use App\Libs\Fields\FieldRegistry;
use App\Libs\Fields\Options\OptionContext;
use App\Libs\Fields\Options\OptionSourceRegistry;
use App\Libs\Location\Constants\Country;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class FieldOptionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(OptionContext::class, function (): OptionContext {
            $request = $this->app->make('request');
            $domain = $request->route()?->getDomain();
            $scope = match ($domain) {
                config('platform.backend_domain') => 'backend',
                default => 'runtime',
            };
            $guard = match ($scope) {
                'backend' => 'backend',
                'public' => 'web',
                default => null,
            };
            $actorId = $guard !== null && config('auth.guards.'.$guard) !== null ? Auth::guard($guard)->id() : null;

            return new OptionContext(app()->getLocale(), $scope, $actorId === null ? null : (int) $actorId);
        });
        $this->app->singleton(OptionSourceRegistry::class, function (): OptionSourceRegistry {
            $sources = new OptionSourceRegistry(array_keys(config('platform.locales')));
            foreach (['common', 'location'] as $category) {
                $sources->registerCategory($category, 'common/fields/const.category.'.$category);
            }
            $sources->registerConstant('common.country', 'location', 'common/fields/const.source.common_country',
                fn (OptionContext $context): array => collect(Country::options())->mapWithKeys(fn (array $country): array => [$country['code'] => $country['label']])->all());
            $sources->registerConstant('common.enabledCountry', 'location', 'common/fields/const.source.common_country',
                fn (OptionContext $context): array => collect(Country::values())
                    ->filter(fn (mixed $country): bool => is_string($country) && in_array($country, Country::values(), true))
                    ->unique()
                    ->mapWithKeys(fn (string $country): array => [$country => Country::label($country)])
                    ->all());
            $sources->registerConstant('common.language', 'location', 'common/fields/const.source.frontend_language',
                fn (OptionContext $context): array => config('platform.locales'));

            return $sources;
        });
        $this->app->singleton(FieldRegistry::class, fn (): FieldRegistry => new FieldRegistry(
            array_keys(config('platform.locales')),
            $this->app->make(OptionSourceRegistry::class),
        ));
    }
}
