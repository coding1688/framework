<?php

namespace App\Providers;

use App\Libs\Common\CacheGroupRegistry;
use App\Libs\Fields\FieldRegistry;
use App\Libs\Fields\Options\OptionContext;
use App\Libs\Fields\Options\OptionSourceRegistry;
use App\Libs\Location\Constants\Country;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
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
            $sources = new OptionSourceRegistry(
                array_keys(config('platform.locales')),
                cache: Cache::store(config('fields.cache_store') ?: config('cache.default')),
                cacheTtl: (int) config('fields.cache_ttl', 300),
            );
            foreach (['common', 'location'] as $category) {
                $sources->registerCategory($category, 'common/fields/const.category.'.$category);
            }
            $sources->registerConstant('common.country', 'location', 'common/fields/const.source.common_country',
                fn (OptionContext $context): array => collect(Country::options())->mapWithKeys(fn (array $country): array => [$country['code'] => $country['label']])->all());
            $sources->registerConstant('common.language', 'location', 'common/fields/const.source.frontend_language',
                fn (OptionContext $context): array => config('platform.locales'));

            return $sources;
        });
        $this->app->singleton(FieldRegistry::class, fn (): FieldRegistry => new FieldRegistry(
            array_keys(config('platform.locales')),
            $this->app->make(OptionSourceRegistry::class),
        ));
        $caches = $this->app->make(CacheGroupRegistry::class);
        $caches->registerGroup('field', ['en' => 'Field', 'zh_CN' => '字段模块', 'zh_TW' => '欄位模組', 'th' => 'โมดูลฟิลด์', 'ja' => 'フィールドモジュール', 'ko' => '필드 모듈']);
        $caches->register('field.options', 'field',
            ['en' => 'Option sources', 'zh_CN' => '字段选项源', 'zh_TW' => '欄位選項來源', 'th' => 'แหล่งตัวเลือกฟิลด์', 'ja' => 'フィールドオプションソース', 'ko' => '필드 옵션 소스'],
            ['en' => 'Registered constant and database option results.', 'zh_CN' => '已注册常量和数据库选项结果。', 'zh_TW' => '已註冊常數和資料庫選項結果。', 'th' => 'ผลตัวเลือกค่าคงที่และฐานข้อมูลที่ลงทะเบียน', 'ja' => '登録済みの定数およびデータベースオプション結果。', 'ko' => '등록된 상수 및 데이터베이스 옵션 결과입니다.'],
            (string) (config('fields.cache_store') ?: config('cache.default')), (int) config('fields.cache_ttl', 300),
            fn (?string $key = null) => $this->app->make(OptionSourceRegistry::class)->forget($key),
            fn (?string $search = null) => $this->app->make(OptionSourceRegistry::class)->entries($search),
            fn (string $key) => $this->app->make(OptionSourceRegistry::class)->rebuild($key),
        );
    }
}
