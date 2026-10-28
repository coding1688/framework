<?php

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

function apiResponse(array $payload = [], int $status = 200): JsonResponse
{
    return response()->json($payload, $status);
}

function apiSuccess(mixed $data = null, ?string $message = null, int $status = 200, array $extra = []): JsonResponse
{
    $payload = [];
    if ($data !== null) {
        $payload['data'] = $data;
    }
    if ($message !== null) {
        $payload['message'] = $message;
    }

    return apiResponse($payload + $extra, $status);
}

function apiError(string $message, int $status = 400, array $errors = [], ?string $errorCode = null): JsonResponse
{
    $payload = ['message' => $message];
    if ($errorCode !== null) {
        $payload['error_code'] = $errorCode;
    }
    if ($errors !== []) {
        $payload['errors'] = $errors;
    }

    return apiResponse($payload, $status);
}

function apiPaginate(LengthAwarePaginator $page, mixed $data = null, array $extra = []): JsonResponse
{
    return apiSuccess($data ?? $page->items(), extra: [
        'total' => $page->total(),
        'last_page' => $page->lastPage(),
        ...$extra,
    ]);
}

function supportedTranslations(?array $translations): array
{
    return array_intersect_key($translations ?? [], config('platform.locales'));
}
