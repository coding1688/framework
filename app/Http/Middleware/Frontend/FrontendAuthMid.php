<?php

namespace App\Http\Middleware\Frontend;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class FrontendAuthMid
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('web')->user();
        if ($user && $user->active && ! $request->session()->has('web.auth_version')) {
            $request->session()->put('web.auth_version', $user->auth_version);
        }
        if (! $user || ! $user->active || $request->session()->get('web.auth_version') !== $user->auth_version) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return $request->expectsJson()
                ? apiResponse(['message' => __('auth.unauthenticated'), 'csrf_token' => $request->session()->token()], 401)
                : redirect()->route('frontend.home');
        }

        return $next($request);
    }
}
