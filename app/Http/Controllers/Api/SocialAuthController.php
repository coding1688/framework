<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\Api\UserResource;
use App\Services\Api\SocialAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SocialAuthController extends BaseController
{
    public function __construct(private readonly SocialAuthService $service) {}

    public function redirect(string $provider): RedirectResponse
    {
        return redirect()->away($this->service->authorizationUrl($provider));
    }

    public function callback(Request $request, string $provider): JsonResponse|RedirectResponse
    {
        $user = $this->service->login($provider, $request->query('code'), $request->query('state'));
        if (! $request->expectsJson()) {
            return redirect()->route('frontend.home');
        }

        return apiSuccess(new UserResource($user));
    }

    public function bindRedirect(string $provider): RedirectResponse
    {
        return redirect()->away($this->service->authorizationUrl($provider, true));
    }

    public function bindCallback(Request $request, string $provider): JsonResponse|RedirectResponse
    {
        $user = $this->service->bind($request->user(), $provider, $request->query('code'), $request->query('state'));
        if (! $request->expectsJson()) {
            return redirect()->route('frontend.home');
        }

        return apiSuccess(new UserResource($user));
    }

    public function unlink(Request $request, string $provider): JsonResponse
    {
        return apiSuccess(new UserResource($this->service->unlink($request->user(), $provider)));
    }
}
