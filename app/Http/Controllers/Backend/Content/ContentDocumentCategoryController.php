<?php

namespace App\Http\Controllers\Backend\Content;

use App\Attributes\MenuAction;
use App\Attributes\MenuPage;
use App\Http\Controllers\Backend\BaseController;
use App\Http\Requests\Backend\Content\ContentDocumentCategoryRequest;
use App\Http\Resources\Backend\Content\ContentDocumentCategoryResource;
use App\Services\Backend\Content\ContentDocumentCategoryService;
use Illuminate\Http\JsonResponse;

#[MenuPage('documentation.category', ['en' => 'Document Categories', 'zh_CN' => '文档分类', 'zh_TW' => '文件分類', 'th' => 'หมวดหมู่เอกสาร', 'ja' => 'ドキュメント分類', 'ko' => '문서 분류'], '/admin/documentation/category', 'documentation/category/List', parent: 'documentation', icon: 'folder-open-line', sort: 20)]
class ContentDocumentCategoryController extends BaseController
{
    public function __construct(private readonly ContentDocumentCategoryService $service) {}

    #[MenuAction(['en' => 'List', 'zh_CN' => '列表', 'zh_TW' => '列表', 'th' => 'รายการ', 'ja' => '一覧', 'ko' => '목록'])]
    public function list(ContentDocumentCategoryRequest $request): JsonResponse
    {
        return apiSuccess(ContentDocumentCategoryResource::collection($this->service->list($request->validated())));
    }

    #[MenuAction(['en' => 'Category options', 'zh_CN' => '分类选项', 'zh_TW' => '分類選項', 'th' => 'ตัวเลือกหมวดหมู่', 'ja' => '分類オプション', 'ko' => '분류 옵션'], sort: 1)]
    public function options(ContentDocumentCategoryRequest $request): JsonResponse
    {
        return apiSuccess(ContentDocumentCategoryResource::collection($this->service->options($request->validated())));
    }

    #[MenuAction(['en' => 'Details', 'zh_CN' => '详情', 'zh_TW' => '詳情', 'th' => 'รายละเอียด', 'ja' => '詳細', 'ko' => '세부 정보'], sort: 2)]
    public function detail(int $id): JsonResponse
    {
        return apiSuccess(new ContentDocumentCategoryResource($this->service->detail($id)));
    }

    #[MenuAction(['en' => 'Create', 'zh_CN' => '新增', 'zh_TW' => '新增', 'th' => 'สร้าง', 'ja' => '作成', 'ko' => '생성'], sort: 3)]
    public function create(ContentDocumentCategoryRequest $request): JsonResponse
    {
        return apiSuccess(new ContentDocumentCategoryResource($this->service->create($request->validated())), status: 201);
    }

    #[MenuAction(['en' => 'Edit', 'zh_CN' => '编辑', 'zh_TW' => '編輯', 'th' => 'แก้ไข', 'ja' => '編集', 'ko' => '편집'], sort: 4)]
    public function update(int $id, ContentDocumentCategoryRequest $request): JsonResponse
    {
        return apiSuccess(new ContentDocumentCategoryResource($this->service->update($id, $request->validated())));
    }

    #[MenuAction(['en' => 'Change status', 'zh_CN' => '状态切换', 'zh_TW' => '狀態切換', 'th' => 'เปลี่ยนสถานะ', 'ja' => 'ステータス変更', 'ko' => '상태 변경'], sort: 5)]
    public function status(int $id, ContentDocumentCategoryRequest $request): JsonResponse
    {
        return apiSuccess(new ContentDocumentCategoryResource($this->service->status($id, $request->validated())));
    }

    #[MenuAction(['en' => 'Delete', 'zh_CN' => '删除', 'zh_TW' => '刪除', 'th' => 'ลบ', 'ja' => '削除', 'ko' => '삭제'], sort: 6)]
    public function delete(int $id): JsonResponse
    {
        $this->service->delete($id);

        return apiSuccess();
    }
}
