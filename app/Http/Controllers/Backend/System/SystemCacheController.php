<?php

namespace App\Http\Controllers\Backend\System;

use App\Attributes\MenuAction;
use App\Attributes\MenuPage;
use App\Attributes\RequireSecurityPassword;
use App\Http\Controllers\Backend\BaseController;
use App\Http\Requests\Backend\System\SystemCacheRequest;
use App\Services\Backend\System\SystemCacheService;
use Illuminate\Http\JsonResponse;

#[MenuPage('system.cache', ['en' => 'Cache management', 'zh_CN' => '缓存管理', 'zh_TW' => '快取管理', 'th' => 'การจัดการแคช', 'ja' => 'キャッシュ管理', 'ko' => '캐시 관리'], '/admin/system/cache', 'system/cache/List', parent: 'development', icon: 'database-2-line', sort: 40)]
class SystemCacheController extends BaseController
{
    public function __construct(private readonly SystemCacheService $service) {}

    #[MenuAction(['en' => 'List', 'zh_CN' => '列表', 'zh_TW' => '列表', 'th' => 'รายการ', 'ja' => '一覧', 'ko' => '목록'])]
    public function list(SystemCacheRequest $request): JsonResponse
    {
        return apiSuccess($this->service->list($request->validated()));
    }

    #[MenuAction(['en' => 'Cache keys', 'zh_CN' => '缓存 Key', 'zh_TW' => '快取 Key', 'th' => 'คีย์แคช', 'ja' => 'キャッシュキー', 'ko' => '캐시 키'], sort: 1)]
    public function keys(SystemCacheRequest $request): JsonResponse
    {
        return apiPaginate($this->service->keys($request->validated()));
    }

    #[MenuAction(['en' => 'Invalidate cache', 'zh_CN' => '失效缓存', 'zh_TW' => '失效快取', 'th' => 'ทำให้แคชไม่ใช้งาน', 'ja' => 'キャッシュを無効化', 'ko' => '캐시 무효화'], sort: 2)]
    #[RequireSecurityPassword(mode: 'dialog')]
    public function clear(SystemCacheRequest $request): JsonResponse
    {
        $this->service->clear($request->validated());

        return apiSuccess();
    }

    #[MenuAction(['en' => 'Refresh key', 'zh_CN' => '刷新 Key', 'zh_TW' => '重新整理 Key', 'th' => 'รีเฟรชคีย์', 'ja' => 'キーを更新', 'ko' => '키 새로 고침'], sort: 3)]
    #[RequireSecurityPassword(mode: 'dialog')]
    public function refresh(SystemCacheRequest $request): JsonResponse
    {
        $this->service->refresh($request->validated());

        return apiSuccess();
    }

    #[MenuAction(['en' => 'Rebuild key', 'zh_CN' => '重新生成 Key', 'zh_TW' => '重新產生 Key', 'th' => 'สร้างคีย์ใหม่', 'ja' => 'キーを再生成', 'ko' => '키 재생성'], sort: 4)]
    #[RequireSecurityPassword(mode: 'dialog')]
    public function rebuild(SystemCacheRequest $request): JsonResponse
    {
        $this->service->rebuild($request->validated());

        return apiSuccess();
    }
}
