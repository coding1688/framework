<?php

namespace App\Http\Controllers\Backend\File;

use App\Attributes\MenuAction;
use App\Attributes\MenuPage;
use App\Attributes\RequireSecurityPassword;
use App\Http\Controllers\Backend\BaseController;
use App\Http\Requests\Backend\File\FileStorageAccountRequest;
use App\Http\Resources\Backend\File\FileStorageAccountResource;
use App\Services\Backend\File\FileStorageAccountService;
use Illuminate\Http\JsonResponse;

#[MenuPage('file.storage', ['en' => 'Storage configuration', 'zh_CN' => '存储配置', 'zh_TW' => '儲存設定', 'th' => 'การตั้งค่าที่จัดเก็บ', 'ja' => 'ストレージ設定', 'ko' => '스토리지 설정'], '/admin/file/storage', 'file/storage/List', parent: 'development', icon: 'hard-drive-2-line', sort: 1)]
class FileStorageAccountController extends BaseController
{
    public function __construct(private readonly FileStorageAccountService $service) {}

    #[MenuAction(['en' => 'List', 'zh_CN' => '列表', 'zh_TW' => '列表', 'th' => 'รายการ', 'ja' => '一覧', 'ko' => '목록'])]
    public function list(): JsonResponse
    {
        return apiSuccess(FileStorageAccountResource::collection($this->service->list()), extra: [
            'provider_options' => $this->service->providerOptions(),
            'local_provider_options' => $this->service->localProviderOptions(),
        ]);
    }

    #[MenuAction(['en' => 'Upload settings', 'zh_CN' => '上传规则', 'zh_TW' => '上傳規則', 'th' => 'กฎการอัปโหลด', 'ja' => 'アップロードルール', 'ko' => '업로드 규칙'])]
    public function settings(): JsonResponse
    {
        return apiSuccess($this->service->settings());
    }

    #[MenuAction(['en' => 'Save upload settings', 'zh_CN' => '保存上传规则', 'zh_TW' => '儲存上傳規則', 'th' => 'บันทึกกฎการอัปโหลด', 'ja' => 'アップロードルールを保存', 'ko' => '업로드 규칙 저장'])]
    #[RequireSecurityPassword(mode: 'dialog')]
    public function saveSettings(FileStorageAccountRequest $request): JsonResponse
    {
        return apiSuccess($this->service->saveSettings($request->validated()));
    }

    #[MenuAction(['en' => 'Create', 'zh_CN' => '新增', 'zh_TW' => '新增', 'th' => 'สร้าง', 'ja' => '作成', 'ko' => '생성'])]
    #[RequireSecurityPassword(mode: 'dialog')]
    public function create(FileStorageAccountRequest $request): JsonResponse
    {
        return apiSuccess(new FileStorageAccountResource($this->service->create($request->validated())), status: 201);
    }

    #[MenuAction(['en' => 'Edit', 'zh_CN' => '编辑', 'zh_TW' => '編輯', 'th' => 'แก้ไข', 'ja' => '編集', 'ko' => '편집'])]
    #[RequireSecurityPassword(mode: 'dialog')]
    public function update(int $id, FileStorageAccountRequest $request): JsonResponse
    {
        return apiSuccess(new FileStorageAccountResource($this->service->update($id, $request->validated())));
    }

    #[MenuAction(['en' => 'Delete', 'zh_CN' => '删除', 'zh_TW' => '刪除', 'th' => 'ลบ', 'ja' => '削除', 'ko' => '삭제'])]
    #[RequireSecurityPassword(mode: 'dialog')]
    public function delete(int $id): JsonResponse
    {
        $this->service->delete($id);

        return apiSuccess();
    }

    #[MenuAction(['en' => 'Sync history', 'zh_CN' => '同步记录', 'zh_TW' => '同步紀錄', 'th' => 'ประวัติการซิงโครไนซ์', 'ja' => '同期履歴', 'ko' => '동기화 기록'])]
    public function tasks(): JsonResponse
    {
        return apiSuccess($this->service->tasks());
    }

    #[MenuAction(['en' => 'Synchronize', 'zh_CN' => '同步', 'zh_TW' => '同步', 'th' => 'ซิงโครไนซ์', 'ja' => '同期', 'ko' => '동기화'])]
    public function sync(FileStorageAccountRequest $request): JsonResponse
    {
        return apiSuccess($this->service->sync($request->validated()), status: 202);
    }

    #[MenuAction(['en' => 'Retry sync', 'zh_CN' => '重试同步', 'zh_TW' => '重試同步', 'th' => 'ลองซิงโครไนซ์อีกครั้ง', 'ja' => '同期を再試行', 'ko' => '동기화 다시 시도'])]
    public function retry(int $id): JsonResponse
    {
        return apiSuccess($this->service->retry($id), status: 202);
    }
}
