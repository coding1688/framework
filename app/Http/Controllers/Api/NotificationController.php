<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\NotificationRequest;
use App\Http\Resources\Api\NotificationResource;
use App\Services\Api\NotificationService;
use Illuminate\Http\JsonResponse;

class NotificationController extends BaseController
{
    public function __construct(private readonly NotificationService $service) {}

    public function list(NotificationRequest $request): JsonResponse
    {
        $result = $this->service->list($request->user(), $request->validated());

        return apiPaginate($result['page'], NotificationResource::collection($result['page']), ['unread' => $result['unread'], 'preferences' => $result['preferences']]);
    }

    public function read(NotificationRequest $request, string $id): JsonResponse
    {
        $this->service->read($request->user(), $id);

        return apiSuccess();
    }

    public function readAll(NotificationRequest $request): JsonResponse
    {
        $this->service->readAll($request->user());

        return apiSuccess();
    }

    public function preferences(NotificationRequest $request): JsonResponse
    {
        return apiSuccess($this->service->preferences($request->user(), $request->validated()));
    }
}
