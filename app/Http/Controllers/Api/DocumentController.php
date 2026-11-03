<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\Api\DocumentCategoryResource;
use App\Http\Resources\Api\DocumentResource;
use App\Services\Api\DocumentService;
use Illuminate\Http\JsonResponse;

class DocumentController extends BaseController
{
    public function __construct(private readonly DocumentService $service) {}

    public function navigation(): JsonResponse
    {
        return apiSuccess(DocumentCategoryResource::collection($this->service->navigation()));
    }

    public function show(string $type, string $slug): JsonResponse
    {
        return apiSuccess(new DocumentResource($this->service->show($type, $slug)));
    }
}
