<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\AuthRequest;
use App\Http\Resources\Api\UserResource;
use App\Services\Api\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends BaseController
{
    public function __construct(private readonly AuthService $service) {}

    public function register(AuthRequest $request): JsonResponse
    {
        return apiSuccess(new UserResource($this->service->register($request->validated())), status: 201, extra: ['csrf_token' => $request->session()->token()]);
    }

    public function login(AuthRequest $request): JsonResponse
    {
        return apiSuccess(new UserResource($this->service->login($request->validated(), $request->ip(), $request->userAgent())), extra: ['csrf_token' => $request->session()->token()]);
    }

    public function forgotPassword(AuthRequest $request): JsonResponse
    {
        $this->service->forgotPassword($request->validated());

        return apiSuccess(message: __('auth.password_reset_sent'));
    }

    public function resetPassword(AuthRequest $request): JsonResponse
    {
        return apiSuccess(new UserResource($this->service->resetPassword($request->validated())), message: __('auth.password_reset'));
    }

    public function profile(AuthRequest $request): JsonResponse
    {
        return apiSuccess(new UserResource($this->service->profile($request->user(), $request->validated())), message: __('auth.saved'));
    }

    public function password(AuthRequest $request): JsonResponse
    {
        $token = $this->service->password($request->user(), $request->validated());

        return apiSuccess(message: __('auth.saved'), extra: ['csrf_token' => $token]);
    }

    public function email(AuthRequest $request): JsonResponse
    {
        return apiSuccess(new UserResource($this->service->email($request->user(), $request->validated())), message: __('auth.verification_sent'));
    }

    public function avatar(AuthRequest $request): JsonResponse
    {
        return apiSuccess(new UserResource($this->service->avatar($request->user(), $request->file('avatar'))), message: __('auth.saved'));
    }

    public function logout(): JsonResponse
    {
        $this->service->logout();

        return apiSuccess();
    }

    public function user(Request $request): JsonResponse
    {
        return apiSuccess(new UserResource($request->user()));
    }
}
