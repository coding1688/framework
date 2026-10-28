<?php

namespace App\Http\Controllers\Backend\File;

use App\Attributes\MenuAction;
use App\Attributes\MenuPage;
use App\Http\Controllers\Backend\BaseController;
use App\Http\Requests\Backend\File\FileRequest;
use App\Http\Resources\Backend\File\FileResource;
use App\Services\Backend\File\FileService;
use Illuminate\Http\JsonResponse;

#[MenuPage('file.item', ['en' => 'File list', 'zh_CN' => '文件列表', 'zh_TW' => '檔案列表', 'th' => 'รายการไฟล์', 'ja' => 'ファイル一覧', 'ko' => '파일 목록'], '/admin/file/item', 'file/item/List', parent: 'system', icon: 'folder-cloud-line')]
class FileController extends BaseController
{
    public function __construct(private FileService $service) {}

    #[MenuAction(['en' => 'List', 'zh_CN' => '列表', 'zh_TW' => '列表', 'th' => 'รายการ', 'ja' => '一覧', 'ko' => '목록'])]
    public function list(FileRequest $request): JsonResponse
    {
        $page = $this->service->list($request->validated());

        return apiPaginate($page, FileResource::collection($page));
    }

    #[MenuAction(['en' => 'Storage options', 'zh_CN' => '存储选项', 'zh_TW' => '儲存選項', 'th' => 'ตัวเลือกที่จัดเก็บ', 'ja' => 'ストレージオプション', 'ko' => '스토리지 옵션'], sort: 1)]
    public function options(): JsonResponse
    {
        return apiSuccess($this->service->options());
    }

    #[MenuAction(['en' => 'Details', 'zh_CN' => '详情', 'zh_TW' => '詳情', 'th' => 'รายละเอียด', 'ja' => '詳細', 'ko' => '세부 정보'], sort: 2)]
    public function detail(int $id): JsonResponse
    {
        return apiSuccess(new FileResource($this->service->detail($id)));
    }

    #[MenuAction(['en' => 'Upload', 'zh_CN' => '上传', 'zh_TW' => '上傳', 'th' => 'อัปโหลด', 'ja' => 'アップロード', 'ko' => '업로드'], sort: 3)]
    public function upload(FileRequest $request): JsonResponse
    {
        return apiSuccess(FileResource::collection($this->service->upload($request->validated())), status: 201);
    }

    #[MenuAction(['en' => 'Delete', 'zh_CN' => '删除', 'zh_TW' => '刪除', 'th' => 'ลบ', 'ja' => '削除', 'ko' => '삭제'], sort: 4)]
    public function delete(int $id): JsonResponse
    {
        $this->service->delete($id);

        return apiSuccess();
    }
}
