<?php

namespace App\Http\Controllers\Backend\Content;

use App\Attributes\MenuAction;
use App\Attributes\MenuPage;
use App\Http\Controllers\Backend\BaseController;
use App\Http\Requests\Backend\Content\ContentCarouselRequest;
use App\Http\Resources\Backend\Content\ContentCarouselResource;
use App\Http\Resources\Backend\File\FileResource;
use App\Services\Backend\Content\ContentCarouselService;
use Illuminate\Http\JsonResponse;

#[MenuPage('content.carousel', ['en' => 'Carousel', 'zh_CN' => '轮播图管理', 'zh_TW' => '輪播圖管理', 'th' => 'ภาพสไลด์', 'ja' => 'カルーセル', 'ko' => '캐러셀'], '/admin/content/carousel', 'content/carousel/List', parent: 'content', icon: 'slideshow-3-line', sort: 20)]
class ContentCarouselController extends BaseController
{
    public function __construct(private readonly ContentCarouselService $service) {}

    #[MenuAction(['en' => 'List', 'zh_CN' => '列表', 'zh_TW' => '列表', 'th' => 'รายการ', 'ja' => '一覧', 'ko' => '목록'])]
    public function list(ContentCarouselRequest $request): JsonResponse
    {
        $page = $this->service->list($request->validated());

        return apiPaginate($page, ContentCarouselResource::collection($page));
    }

    #[MenuAction(['en' => 'Details', 'zh_CN' => '详情', 'zh_TW' => '詳情', 'th' => 'รายละเอียด', 'ja' => '詳細', 'ko' => '세부 정보'], sort: 1)]
    public function detail(int $id): JsonResponse
    {
        return apiSuccess(new ContentCarouselResource($this->service->detail($id)));
    }

    #[MenuAction(['en' => 'Create', 'zh_CN' => '新增', 'zh_TW' => '新增', 'th' => 'สร้าง', 'ja' => '作成', 'ko' => '생성'], sort: 2)]
    public function create(ContentCarouselRequest $request): JsonResponse
    {
        return apiSuccess(new ContentCarouselResource($this->service->create($request->validated())), status: 201);
    }

    #[MenuAction(['en' => 'Edit', 'zh_CN' => '编辑', 'zh_TW' => '編輯', 'th' => 'แก้ไข', 'ja' => '編集', 'ko' => '편집'], sort: 3)]
    public function update(int $id, ContentCarouselRequest $request): JsonResponse
    {
        return apiSuccess(new ContentCarouselResource($this->service->update($id, $request->validated())));
    }

    #[MenuAction(['en' => 'Change status', 'zh_CN' => '状态切换', 'zh_TW' => '狀態切換', 'th' => 'เปลี่ยนสถานะ', 'ja' => 'ステータス変更', 'ko' => '상태 변경'], sort: 4)]
    public function status(int $id, ContentCarouselRequest $request): JsonResponse
    {
        return apiSuccess(new ContentCarouselResource($this->service->status($id, $request->validated())));
    }

    #[MenuAction(['en' => 'Delete', 'zh_CN' => '删除', 'zh_TW' => '刪除', 'th' => 'ลบ', 'ja' => '削除', 'ko' => '삭제'], sort: 5)]
    public function delete(int $id): JsonResponse
    {
        $this->service->delete($id);

        return apiSuccess();
    }

    #[MenuAction(['en' => 'Upload options', 'zh_CN' => '上传选项', 'zh_TW' => '上傳選項', 'th' => 'ตัวเลือกอัปโหลด', 'ja' => 'アップロード設定', 'ko' => '업로드 옵션'], sort: 6)]
    public function options(): JsonResponse
    {
        return apiSuccess($this->service->options());
    }

    #[MenuAction(['en' => 'Upload image', 'zh_CN' => '上传图片', 'zh_TW' => '上傳圖片', 'th' => 'อัปโหลดรูปภาพ', 'ja' => '画像アップロード', 'ko' => '이미지 업로드'], sort: 7)]
    public function upload(ContentCarouselRequest $request): JsonResponse
    {
        return apiSuccess(new FileResource($this->service->upload($request->validated())), status: 201);
    }
}
