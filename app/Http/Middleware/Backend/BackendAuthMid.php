<?php

namespace App\Http\Middleware\Backend;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class BackendAuthMid
{
    public function handle(Request $request, Closure $next): Response
    {
        $admin = Auth::guard('backend')->user();
        if (! $admin || ! $admin->active || $request->session()->get('backend.auth_version') !== $admin->auth_version) {
            Auth::guard('backend')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return $request->expectsJson()
                ? apiResponse(['message' => __('backend/auth.unauthenticated'), 'csrf_token' => $request->session()->token()], 401)
                : redirect()->route('backend.login');
        }

        return $next($request);
    }
}
