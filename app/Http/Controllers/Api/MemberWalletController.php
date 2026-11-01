<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\MemberWalletRequest;
use App\Http\Resources\Api\MemberWalletDailyStatResource;
use App\Http\Resources\Api\MemberWalletResource;
use App\Http\Resources\Api\MemberWalletTransactionResource;
use App\Services\Api\MemberWalletService;
use Illuminate\Http\JsonResponse;

class MemberWalletController extends BaseController
{
    public function __construct(private readonly MemberWalletService $service) {}

    public function list(MemberWalletRequest $request): JsonResponse
    {
        $result = $this->service->list($request->user(), $request->validated());

        return apiPaginate($result['page'], MemberWalletResource::collection($result['page']), ['main_currency' => $result['main_currency']]);
    }

    public function history(MemberWalletRequest $request): JsonResponse
    {
        $page = $this->service->history($request->user(), $request->validated());

        return apiPaginate($page, MemberWalletTransactionResource::collection($page));
    }

    public function statistics(MemberWalletRequest $request): JsonResponse
    {
        $page = $this->service->statistics($request->user(), $request->validated());

        return apiPaginate($page, MemberWalletDailyStatResource::collection($page));
    }
}
