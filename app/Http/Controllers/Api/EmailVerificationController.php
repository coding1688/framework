<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\Api\UserResource;
use App\Services\Api\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationController extends BaseController
{
    public function __construct(private readonly AuthService $service) {}

    public function send(Request $request): JsonResponse
    {
        $this->service->resendVerification($request->user());

        return apiSuccess(message: __('auth.verification_sent'));
    }

    public function verify(Request $request, int $id, string $hash): JsonResponse|RedirectResponse
    {
        $user = $this->service->verifyEmail($request->user(), $id, $hash);
        if (! $request->expectsJson()) {
            return redirect()->route('frontend.home');
        }

        return apiSuccess(new UserResource($user));
    }
}
