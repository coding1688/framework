<?php

namespace App\Libs\Api;

use App\Libs\File\UploadConfiguration;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

final class ApiExceptionRenderer
{
    public function __construct(private readonly UploadConfiguration $uploads) {}

    public function render(Throwable $exception, Request $request): ?JsonResponse
    {
        if (! $request->expectsJson()) {
            return null;
        }
        if ($exception instanceof PostTooLargeException) {
            $size = (int) ceil($this->uploads->maxSizeKb() / 1024);
            $message = __('api.payload_too_large', ['size' => $size]);

            return apiError($message, 413, ['files' => [$message]], 'payload_too_large');
        }
        if ($exception instanceof ValidationException) {
            return apiError($exception->getMessage(), 422, $exception->errors(), 'validation_failed');
        }
        if ($exception instanceof AuthenticationException) {
            return apiError(__('api.unauthenticated'), 401, errorCode: 'unauthenticated');
        }
        if ($exception instanceof AuthorizationException) {
            return apiError(__('api.forbidden'), 403, errorCode: 'forbidden');
        }
        if ($exception instanceof TokenMismatchException) {
            return apiError(__('api.session_expired'), 419, errorCode: 'session_expired');
        }
        if ($exception instanceof HttpExceptionInterface) {
            return $this->httpException($exception);
        }

        return apiError(__('api.server_error'), 500, errorCode: 'server_error');
    }

    private function httpException(HttpExceptionInterface $exception): JsonResponse
    {
        $status = $exception->getStatusCode();
        $message = match ($status) {
            401 => __('api.unauthenticated'),
            403 => __('api.forbidden'),
            404 => __('api.not_found'),
            405 => __('api.method_not_allowed'),
            413 => __('api.payload_too_large', ['size' => (int) ceil($this->uploads->maxSizeKb() / 1024)]),
            419 => __('api.session_expired'),
            429 => $exception->getMessage() ?: __('api.too_many_requests'),
            default => $status >= 500 ? __('api.server_error') : ($exception->getMessage() ?: __('api.error')),
        };
        $code = match ($status) {
            401 => 'unauthenticated', 403 => 'forbidden', 404 => 'not_found', 405 => 'method_not_allowed',
            413 => 'payload_too_large', 419 => 'session_expired', 429 => 'too_many_requests',
            default => $status >= 500 ? 'server_error' : 'http_error',
        };
        $errors = $status === 413 ? ['files' => [$message]] : [];

        return apiError($message, $status, $errors, $code)->withHeaders($exception->getHeaders());
    }
}
