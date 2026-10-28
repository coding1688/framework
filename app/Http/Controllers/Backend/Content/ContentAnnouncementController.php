<?php

namespace App\Http\Controllers\Backend\Content;

use App\Attributes\MenuAction;
use App\Attributes\MenuPage;
use App\Http\Controllers\Backend\BaseController;
use App\Http\Requests\Backend\Content\ContentAnnouncementRequest;
use App\Http\Resources\Backend\Content\ContentAnnouncementResource;
use App\Services\Backend\Content\ContentAnnouncementService;
use Illuminate\Http\JsonResponse;

#[MenuPage('content.announcement', ['en' => 'Announcements', 'zh_CN' => '公告管理', 'zh_TW' => '公告管理', 'th' => 'ประกาศ', 'ja' => 'お知らせ', 'ko' => '공지사항'], '/admin/content/announcement', 'content/announcement/List', parent: 'content', icon: 'notification-3-line', sort: 10)]
class ContentAnnouncementController extends BaseController
{
    public function __construct(private readonly ContentAnnouncementService $service) {}

    #[MenuAction(['en' => 'List', 'zh_CN' => '列表', 'zh_TW' => '列表', 'th' => 'รายการ', 'ja' => '一覧', 'ko' => '목록'])]
    public function list(ContentAnnouncementRequest $request): JsonResponse
    {
        $page = $this->service->list($request->validated());

        return apiPaginate($page, ContentAnnouncementResource::collection($page));
    }

    #[MenuAction(['en' => 'Details', 'zh_CN' => '详情', 'zh_TW' => '詳情', 'th' => 'รายละเอียด', 'ja' => '詳細', 'ko' => '세부 정보'], sort: 1)]
    public function detail(int $id): JsonResponse
    {
        return apiSuccess(new ContentAnnouncementResource($this->service->detail($id)));
    }

    #[MenuAction(['en' => 'Create', 'zh_CN' => '新增', 'zh_TW' => '新增', 'th' => 'สร้าง', 'ja' => '作成', 'ko' => '생성'], sort: 2)]
    public function create(ContentAnnouncementRequest $request): JsonResponse
    {
        return apiSuccess(new ContentAnnouncementResource($this->service->create($request->validated())), status: 201);
    }

    #[MenuAction(['en' => 'Edit', 'zh_CN' => '编辑', 'zh_TW' => '編輯', 'th' => 'แก้ไข', 'ja' => '編集', 'ko' => '편집'], sort: 3)]
    public function update(int $id, ContentAnnouncementRequest $request): JsonResponse
    {
        return apiSuccess(new ContentAnnouncementResource($this->service->update($id, $request->validated())));
    }

    #[MenuAction(['en' => 'Change status', 'zh_CN' => '状态切换', 'zh_TW' => '狀態切換', 'th' => 'เปลี่ยนสถานะ', 'ja' => 'ステータス変更', 'ko' => '상태 변경'], sort: 4)]
    public function status(int $id, ContentAnnouncementRequest $request): JsonResponse
    {
        return apiSuccess(new ContentAnnouncementResource($this->service->status($id, $request->validated())));
    }

    #[MenuAction(['en' => 'Delete', 'zh_CN' => '删除', 'zh_TW' => '刪除', 'th' => 'ลบ', 'ja' => '削除', 'ko' => '삭제'], sort: 5)]
    public function delete(int $id): JsonResponse
    {
        $this->service->delete($id);

        return apiSuccess();
    }
}
