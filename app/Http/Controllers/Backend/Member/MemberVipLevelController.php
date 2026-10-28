<?php

namespace App\Http\Controllers\Backend\Member;

use App\Attributes\MenuAction;
use App\Attributes\MenuPage;
use App\Http\Controllers\Backend\BaseController;
use App\Http\Requests\Backend\Member\MemberVipLevelRequest;
use App\Http\Resources\Backend\Member\MemberVipLevelResource;
use App\Services\Backend\Member\MemberVipLevelService;
use Illuminate\Http\JsonResponse;

#[MenuPage('member.vipLevel', ['en' => 'VIP Levels', 'zh_CN' => 'VIP 等级', 'zh_TW' => 'VIP 等級', 'th' => 'ระดับ VIP', 'ja' => 'VIPレベル', 'ko' => 'VIP 등급'], '/admin/member/vip-level', 'member/vipLevel/List', parent: 'member', icon: 'vip-crown-line', sort: 10)]
class MemberVipLevelController extends BaseController
{
    public function __construct(private readonly MemberVipLevelService $service) {}

    #[MenuAction(['en' => 'List', 'zh_CN' => '列表', 'zh_TW' => '列表', 'th' => 'รายการ', 'ja' => '一覧', 'ko' => '목록'])]
    public function list(MemberVipLevelRequest $request): JsonResponse
    {
        $page = $this->service->list($request->validated());

        return apiPaginate($page, MemberVipLevelResource::collection($page));
    }

    #[MenuAction(['en' => 'Options', 'zh_CN' => '加载选项', 'zh_TW' => '載入選項', 'th' => 'ตัวเลือก', 'ja' => 'オプション', 'ko' => '옵션'], sort: 1)]
    public function options(): JsonResponse
    {
        return apiSuccess($this->service->options());
    }

    #[MenuAction(['en' => 'Details', 'zh_CN' => '详情', 'zh_TW' => '詳情', 'th' => 'รายละเอียด', 'ja' => '詳細', 'ko' => '세부 정보'], sort: 2)]
    public function detail(int $id): JsonResponse
    {
        return apiSuccess(new MemberVipLevelResource($this->service->detail($id)));
    }

    #[MenuAction(['en' => 'Create', 'zh_CN' => '新增', 'zh_TW' => '新增', 'th' => 'สร้าง', 'ja' => '作成', 'ko' => '생성'], sort: 3)]
    public function create(MemberVipLevelRequest $request): JsonResponse
    {
        return apiSuccess(new MemberVipLevelResource($this->service->create($request->validated())), status: 201);
    }

    #[MenuAction(['en' => 'Edit', 'zh_CN' => '编辑', 'zh_TW' => '編輯', 'th' => 'แก้ไข', 'ja' => '編集', 'ko' => '편집'], sort: 4)]
    public function update(MemberVipLevelRequest $request, int $id): JsonResponse
    {
        return apiSuccess(new MemberVipLevelResource($this->service->update($request->validated(), $id)));
    }

    #[MenuAction(['en' => 'Change status', 'zh_CN' => '状态切换', 'zh_TW' => '狀態切換', 'th' => 'เปลี่ยนสถานะ', 'ja' => 'ステータス変更', 'ko' => '상태 변경'], sort: 5)]
    public function status(MemberVipLevelRequest $request, int $id): JsonResponse
    {
        return apiSuccess(new MemberVipLevelResource($this->service->status($request->validated(), $id)));
    }

    #[MenuAction(['en' => 'Delete', 'zh_CN' => '删除', 'zh_TW' => '刪除', 'th' => 'ลบ', 'ja' => '削除', 'ko' => '삭제'], sort: 6)]
    public function delete(int $id): JsonResponse
    {
        $this->service->delete($id);

        return apiSuccess();
    }
}
