<?php

use App\Http\Middleware\Backend\BackendAclMid;
use App\Http\Middleware\Backend\BackendAuthMid;
use App\Http\Middleware\Backend\BackendLocaleMid;
use App\Http\Middleware\Backend\BackendLogMid;
use App\Http\Middleware\Backend\BackendSecurityPasswordMid;
use App\Http\Middleware\Frontend\FrontendAuthMid;
use App\Http\Middleware\Frontend\FrontendLocaleMid;
use App\Libs\Ai\Exceptions\AiException;
use App\Libs\Api\ApiExceptionRenderer;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trimStrings(except: ['security_password', 'security_password_confirmation', 'current_security_password', 'account.password', 'account.security_password', 'access_key', 'secret_key']);
        $middleware->prependToPriorityList(
            SubstituteBindings::class,
            BackendAclMid::class,
        );
        $middleware->prependToPriorityList(
            BackendAclMid::class,
            BackendAuthMid::class,
        );
        $middleware->prependToPriorityList(BackendAuthMid::class, BackendLogMid::class);
        $middleware->prependToPriorityList(BackendLogMid::class, BackendLocaleMid::class);
        $middleware->appendToPriorityList(BackendAclMid::class, BackendSecurityPasswordMid::class);
        $middleware->alias([
            'backend.auth' => BackendAuthMid::class,
            'backend.permission' => BackendAclMid::class,
            'backend.locale' => BackendLocaleMid::class,
            'backend.log' => BackendLogMid::class,
            'backend.securityPassword' => BackendSecurityPasswordMid::class,
            'frontend.auth' => FrontendAuthMid::class,
            'frontend.locale' => FrontendLocaleMid::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash(['security_password', 'security_password_confirmation', 'current_security_password', 'account.password', 'account.security_password', 'access_key', 'secret_key']);
        $exceptions->render(function (AiException $exception, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }
            $status = match ($exception->errorCode) {
                'task_not_found' => 404,
                'task_busy', 'task_consumed' => 409,
                'not_configured' => 503,
                default => 422,
            };

            return apiError($exception->getMessage(), $status, errorCode: $exception->errorCode);
        });
        $exceptions->render(fn (Throwable $exception, Request $request) => app(ApiExceptionRenderer::class)->render($exception, $request));
        $exceptions->respond(function (Response $response) {
            if ($response->getStatusCode() !== 403 || request()->getHost() !== config('platform.backend_domain')) {
                return $response;
            }

            if (! request()->expectsJson()) {
                $response = response()->view('backend.errors.forbidden', [], 403);
            }
            $response->headers->set('X-Backend-Redirect', 'home');
            $response->headers->set('Cache-Control', 'no-store');

            return $response;
        });
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->expectsJson() || ($request->getHost() !== config('platform.backend_domain') && $request->is('api/*')),
        );
    })->create();
