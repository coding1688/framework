<?php

namespace App\Http\Middleware\Backend;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BackendLocaleMid
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->header('X-Lang') ?? $request->query('lang', $request->session()->get('backend.locale', config('app.locale')));
        if (! is_string($locale) || ! array_key_exists($locale, config('platform.locales'))) {
            $locale = config('app.locale');
        }
        app()->setLocale($locale);
        $request->session()->put('backend.locale', $locale);

        return $next($request);
    }
}
