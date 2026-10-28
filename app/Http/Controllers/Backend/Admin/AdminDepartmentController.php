<?php

namespace App\Http\Controllers\Backend\Admin;

use App\Attributes\MenuAction;
use App\Attributes\MenuPage;
use App\Attributes\RequireSecurityPassword;
use App\Http\Controllers\Backend\BaseController;
use App\Http\Requests\Backend\Admin\AdminDepartmentRequest;
use App\Http\Resources\Backend\Admin\AdminDepartmentResource;
use App\Http\Resources\Backend\Admin\AdminUserResource;
use App\Services\Backend\Admin\AdminDepartmentService;
use Illuminate\Http\JsonResponse;

#[MenuPage('admin.department', ['en' => 'Departments', 'zh_CN' => '部门管理', 'zh_TW' => '部門管理', 'th' => 'แผนก', 'ja' => '部門', 'ko' => '부서'], '/admin/department', 'admin/department/List', parent: 'admin', icon: 'organization-chart')]
class AdminDepartmentController extends BaseController
{
    public function __construct(private AdminDepartmentService $service) {}

    #[MenuAction(['en' => 'List', 'zh_CN' => '列表', 'zh_TW' => '列表', 'th' => 'รายการ', 'ja' => '一覧', 'ko' => '목록'], sort: 0)]
    public function list(): JsonResponse
    {
        return apiSuccess(AdminDepartmentResource::collection($this->service->list()));
    }

    #[MenuAction(['en' => 'Load options', 'zh_CN' => '加载选项', 'zh_TW' => '載入選項', 'th' => 'โหลดตัวเลือก', 'ja' => 'オプションを読み込む', 'ko' => '옵션 불러오기'], sort: 6)]
    public function options(): JsonResponse
    {
        return apiSuccess(extra: $this->service->options());
    }

    #[MenuAction(['en' => 'Employee management', 'zh_CN' => '员工管理', 'zh_TW' => '員工管理', 'th' => 'การจัดการพนักงาน', 'ja' => '従業員管理', 'ko' => '직원 관리'], sort: 4)]
    public function employees(int $id): JsonResponse
    {
        $result = $this->service->employees($id);

        return apiSuccess(AdminUserResource::collection($result['employees']), extra: ['can_set_leaders' => $result['can_set_leaders']]);
    }

    #[MenuAction(['en' => 'Set leaders', 'zh_CN' => '设置 Leader', 'zh_TW' => '設定 Leader', 'th' => 'ตั้งหัวหน้า', 'ja' => 'リーダーを設定', 'ko' => '리더 설정'], sort: 7)]
    public function leaders(AdminDepartmentRequest $request, int $id): JsonResponse
    {
        $this->service->leaders($request->validated(), $id);

        return apiSuccess(message: __('backend/common.saved'));
    }

    #[MenuAction(['en' => 'Assign permissions', 'zh_CN' => '分配权限', 'zh_TW' => '分配權限', 'th' => 'กำหนดสิทธิ์', 'ja' => '権限を割り当てる', 'ko' => '권한 할당'], sort: 5)]
    public function permissions(AdminDepartmentRequest $request, int $id): JsonResponse
    {
        $this->service->permissions($request->validated(), $id);

        return apiSuccess(message: __('backend/common.saved'));
    }

    #[MenuAction(['en' => 'Create', 'zh_CN' => '新增', 'zh_TW' => '新增', 'th' => 'สร้าง', 'ja' => '作成', 'ko' => '생성'], sort: 1)]
    public function create(AdminDepartmentRequest $request): JsonResponse
    {
        return apiSuccess(new AdminDepartmentResource($this->service->create($request->validated())), status: 201);
    }

    #[MenuAction(['en' => 'Edit', 'zh_CN' => '编辑', 'zh_TW' => '編輯', 'th' => 'แก้ไข', 'ja' => '編集', 'ko' => '편집'], sort: 2)]
    public function update(AdminDepartmentRequest $request, int $id): JsonResponse
    {
        $this->service->update($request->validated(), $id);

        return apiSuccess(message: __('backend/common.saved'));
    }

    #[MenuAction(['en' => 'Change status', 'zh_CN' => '启用/停用', 'zh_TW' => '啟用/停用', 'th' => 'เปลี่ยนสถานะ', 'ja' => 'ステータス変更', 'ko' => '상태 변경'], sort: 3)]
    #[RequireSecurityPassword(mode: 'form')]
    public function status(AdminDepartmentRequest $request, int $id): JsonResponse
    {
        $this->service->status($request->validated(), $id);

        return apiSuccess(message: __('backend/common.saved'));
    }

    #[MenuAction(['en' => 'Delete', 'zh_CN' => '删除', 'zh_TW' => '刪除', 'th' => 'ลบ', 'ja' => '削除', 'ko' => '삭제'], sort: 4)]
    public function delete(int $id): JsonResponse
    {
        $this->service->delete($id);

        return apiSuccess(message: __('backend/common.saved'));
    }
}
