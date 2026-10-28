<?php

namespace App\Http\Controllers\Backend;

use App\Http\Requests\Backend\AuthRequest;
use App\Http\Resources\Backend\Admin\AdminMenuResource;
use App\Http\Resources\Backend\Admin\AdminUserResource;
use App\Services\Backend\AuthService;
use Illuminate\Http\JsonResponse;

class AuthController extends BaseController
{
    public function __construct(private AuthService $service) {}

    public function login(AuthRequest $request): JsonResponse
    {
        $user = $this->service->login($request->validated(), $request->ip(), $request->userAgent());

        return apiSuccess(new AdminUserResource($user), extra: ['redirect' => route('backend.home'), 'csrf_token' => $request->session()->token()]);
    }

    public function forgotPassword(AuthRequest $request): JsonResponse
    {
        $this->service->forgotPassword($request->validated());

        return apiSuccess(message: __('auth.password_reset_sent'));
    }

    public function resetPassword(AuthRequest $request): JsonResponse
    {
        $this->service->resetPassword($request->validated(), $request->ip());

        return apiSuccess(message: __('auth.password_reset'));
    }

    public function logout(): JsonResponse
    {
        $token = $this->service->logout();

        return apiSuccess(message: __('backend/common.saved'), extra: ['csrf_token' => $token]);
    }

    public function userinfo(): JsonResponse
    {
        $result = $this->service->userinfo();

        return apiSuccess(new AdminUserResource($result['user']), extra: ['permissions' => $result['permissions'], 'menus' => AdminMenuResource::collection($result['menus']), 'navigation' => $result['navigation']]);
    }

    public function preferences(AuthRequest $request): JsonResponse
    {
        return apiSuccess($this->service->preferences($request->validated()));
    }

    public function securityPassword(AuthRequest $request): JsonResponse
    {
        $this->service->securityPassword($request->validated(), $request->ip());

        return apiSuccess(['security_password_set' => true], message: __('common/security/main.saved'));
    }

    public function password(AuthRequest $request): JsonResponse
    {
        $token = $this->service->password($request->validated(), $request->ip());

        return apiSuccess(message: __('backend/common.saved'), extra: ['csrf_token' => $token]);
    }
}
