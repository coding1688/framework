<?php

namespace App\Http\Controllers\Backend\Admin;

use App\Attributes\MenuAction;
use App\Attributes\MenuPage;
use App\Http\Controllers\Backend\BaseController;
use App\Http\Requests\Backend\Admin\AdminUserRequest;
use App\Http\Resources\Backend\Admin\AdminUserResource;
use App\Services\Backend\Admin\AdminUserService;
use Illuminate\Http\JsonResponse;

#[MenuPage('admin.user', ['en' => 'Administrators', 'zh_CN' => '管理员', 'zh_TW' => '管理員', 'th' => 'ผู้ดูแล', 'ja' => '管理者', 'ko' => '관리자'], '/admin/user', 'admin/user/List', parent: 'admin', icon: 'group-line')]
class AdminUserController extends BaseController
{
    public function __construct(private AdminUserService $service) {}

    #[MenuAction(['en' => 'List', 'zh_CN' => '列表', 'zh_TW' => '列表', 'th' => 'รายการ', 'ja' => '一覧', 'ko' => '목록'], sort: 0)]
    public function list(AdminUserRequest $request): JsonResponse
    {
        $page = $this->service->list($request->validated());

        return apiPaginate($page, AdminUserResource::collection($page));
    }

    #[MenuAction(['en' => 'Details', 'zh_CN' => '详情', 'zh_TW' => '詳情', 'th' => 'รายละเอียด', 'ja' => '詳細', 'ko' => '세부 정보'], sort: 6)]
    public function detail(int $id): JsonResponse
    {
        return apiSuccess(new AdminUserResource($this->service->detail($id)));
    }

    #[MenuAction(['en' => 'Load options', 'zh_CN' => '加载选项', 'zh_TW' => '載入選項', 'th' => 'โหลดตัวเลือก', 'ja' => 'オプションを読み込む', 'ko' => '옵션 불러오기'], sort: 7)]
    public function options(): JsonResponse
    {
        return apiSuccess(extra: $this->service->options());
    }

    #[MenuAction(['en' => 'Create', 'zh_CN' => '新增', 'zh_TW' => '新增', 'th' => 'สร้าง', 'ja' => '作成', 'ko' => '생성'], sort: 1)]
    public function create(AdminUserRequest $request): JsonResponse
    {
        return apiSuccess(new AdminUserResource($this->service->create($request->validated(), $request->ip())), status: 201);
    }

    #[MenuAction(['en' => 'Edit', 'zh_CN' => '编辑', 'zh_TW' => '編輯', 'th' => 'แก้ไข', 'ja' => '編集', 'ko' => '편집'], sort: 2)]
    public function update(AdminUserRequest $request, int $id): JsonResponse
    {
        $this->service->update($request->validated(), $id);

        return apiSuccess(message: __('backend/common.saved'));
    }

    #[MenuAction(['en' => 'Change status', 'zh_CN' => '修改状态', 'zh_TW' => '修改狀態', 'th' => 'เปลี่ยนสถานะ', 'ja' => 'ステータスを変更', 'ko' => '상태 변경'], sort: 8)]
    public function status(AdminUserRequest $request, int $id): JsonResponse
    {
        $this->service->status($request->validated(), $id);

        return apiSuccess(message: __('backend/common.saved'));
    }

    #[MenuAction(['en' => 'Delete', 'zh_CN' => '删除', 'zh_TW' => '刪除', 'th' => 'ลบ', 'ja' => '削除', 'ko' => '삭제'], sort: 3)]
    public function delete(int $id): JsonResponse
    {
        $this->service->delete($id);

        return apiSuccess(message: __('backend/common.saved'));
    }

    #[MenuAction(['en' => 'Reset password', 'zh_CN' => '重置密码', 'zh_TW' => '重設密碼', 'th' => 'รีเซ็ตรหัสผ่าน', 'ja' => 'パスワードをリセット', 'ko' => '비밀번호 재설정'], sort: 4)]
    public function password(AdminUserRequest $request, int $id): JsonResponse
    {
        $this->service->password($request->validated(), $id, $request->ip());

        return apiSuccess(message: __('backend/common.saved'));
    }
}
