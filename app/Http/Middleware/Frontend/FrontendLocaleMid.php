<?php

namespace App\Http\Middleware\Frontend;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FrontendLocaleMid
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->header('X-Lang') ?? $request->query('lang', config('app.locale'));
        if (! is_string($locale) || ! array_key_exists($locale, config('platform.locales'))) {
            $locale = config('app.fallback_locale');
        }
        app()->setLocale($locale);

        return $next($request);
    }
}
