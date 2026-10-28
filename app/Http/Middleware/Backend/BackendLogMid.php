<?php

namespace App\Http\Middleware\Backend;

use App\Attributes\IgnoreLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class BackendLogMid
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldIgnore($request)) {
            return $next($request);
        }

        $request->attributes->set('backendLogStarted', hrtime(true));
        $request->attributes->set('backendLogActor', Auth::guard('backend')->id());
        try {
            return $next($request);
        } finally {
            $request->attributes->set('backendLogDuration', round((hrtime(true) - $request->attributes->get('backendLogStarted')) / 1_000_000, 2));
            $request->attributes->set('backendLogActor', Auth::guard('backend')->id() ?? $request->attributes->get('backendLogActor'));
        }
    }

    public function terminate(Request $request, Response $response): void
    {
        if (! $request->attributes->has('backendLogStarted')) {
            return;
        }
        try {
            Log::channel('backend_requests')->info('backend.request', [
                'route' => $request->route()?->getName(),
                'method' => $request->method(),
                'status' => $response->getStatusCode(),
                'duration_ms' => $request->attributes->get('backendLogDuration'),
                'administrator_id' => $request->attributes->get('backendLogActor'),
                'ip' => $request->ip(),
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function shouldIgnore(Request $request): bool
    {
        $route = $request->route();
        $controller = $route?->getControllerClass();
        $method = $route?->getActionMethod();
        if (! $controller || ! $method || ! method_exists($controller, $method)) {
            return false;
        }

        return (new ReflectionMethod($controller, $method))->getAttributes(IgnoreLog::class) !== [];
    }
}
