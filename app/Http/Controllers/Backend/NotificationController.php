<?php

namespace App\Http\Controllers\Backend;

use App\Http\Requests\Backend\NotificationRequest;
use App\Http\Resources\Api\NotificationResource;
use App\Services\Backend\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class NotificationController extends BaseController
{
    public function __construct(private readonly NotificationService $service) {}

    public function list(NotificationRequest $request): JsonResponse
    {
        $result = $this->service->list(Auth::guard('backend')->user(), $request->validated());

        return apiPaginate($result['page'], NotificationResource::collection($result['page']), ['unread' => $result['unread'], 'preferences' => $result['preferences']]);
    }

    public function read(string $id): JsonResponse
    {
        $this->service->read(Auth::guard('backend')->user(), $id);

        return apiSuccess();
    }

    public function readAll(): JsonResponse
    {
        $this->service->readAll(Auth::guard('backend')->user());

        return apiSuccess();
    }

    public function preferences(NotificationRequest $request): JsonResponse
    {
        return apiSuccess($this->service->preferences(Auth::guard('backend')->user(), $request->validated()));
    }
}
