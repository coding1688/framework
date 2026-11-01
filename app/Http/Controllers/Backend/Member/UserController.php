<?php

namespace App\Http\Controllers\Backend\Member;

use App\Attributes\MenuAction;
use App\Attributes\MenuPage;
use App\Attributes\RequireSecurityPassword;
use App\Http\Controllers\Backend\BaseController;
use App\Http\Requests\Backend\Member\UserRequest;
use App\Http\Resources\Backend\Member\MemberVipRecordResource;
use App\Http\Resources\Backend\Member\UserLogResource;
use App\Http\Resources\Backend\Member\UserResource;
use App\Services\Backend\Member\UserService;
use Illuminate\Http\JsonResponse;

#[MenuPage('frontend.user', ['en' => 'User Management', 'zh_CN' => '用户管理', 'zh_TW' => '使用者管理', 'th' => 'การจัดการผู้ใช้', 'ja' => 'ユーザー管理', 'ko' => '사용자 관리'], '/admin/frontend/user', 'member/user/List', parent: 'member', icon: 'user-3-line', sort: 20)]
class UserController extends BaseController
{
    public function __construct(private readonly UserService $service) {}

    #[MenuAction(['en' => 'List', 'zh_CN' => '列表', 'zh_TW' => '列表', 'th' => 'รายการ', 'ja' => '一覧', 'ko' => '목록'])]
    public function list(UserRequest $request): JsonResponse
    {
        $page = $this->service->list($request->validated());

        return apiPaginate($page, UserResource::collection($page));
    }

    #[MenuAction(['en' => 'Details', 'zh_CN' => '详情', 'zh_TW' => '詳情', 'th' => 'รายละเอียด', 'ja' => '詳細', 'ko' => '세부 정보'], sort: 1)]
    public function detail(int $id): JsonResponse
    {
        return apiSuccess(new UserResource($this->service->detail($id)));
    }

    #[MenuAction(['en' => 'Edit', 'zh_CN' => '编辑', 'zh_TW' => '編輯', 'th' => 'แก้ไข', 'ja' => '編集', 'ko' => '편집'], sort: 2)]
    public function update(int $id, UserRequest $request): JsonResponse
    {
        return apiSuccess(new UserResource($this->service->update($id, $request->validated())));
    }

    #[MenuAction(['en' => 'Freeze or unfreeze', 'zh_CN' => '冻结或解冻', 'zh_TW' => '凍結或解凍', 'th' => 'ระงับหรือปลดระงับ', 'ja' => '凍結または解除', 'ko' => '동결 또는 해제'], sort: 3)]
    public function status(int $id, UserRequest $request): JsonResponse
    {
        return apiSuccess(new UserResource($this->service->status($id, $request->validated())));
    }

    #[MenuAction(['en' => 'Batch restrictions', 'zh_CN' => '批量限制', 'zh_TW' => '批次限制', 'th' => 'จำกัดเป็นชุด', 'ja' => '一括制限', 'ko' => '일괄 제한'], sort: 11)]
    public function restrictions(UserRequest $request): JsonResponse
    {
        return apiSuccess($this->service->restrictions($request->validated()));
    }

    #[MenuAction(['en' => 'Restriction types', 'zh_CN' => '限制类型', 'zh_TW' => '限制類型', 'th' => 'ประเภทข้อจำกัด', 'ja' => '制限タイプ', 'ko' => '제한 유형'], sort: 12)]
    public function restrictionTypes(): JsonResponse
    {
        return apiSuccess($this->service->restrictionTypes());
    }

    #[MenuAction(['en' => 'Check restriction', 'zh_CN' => '查询限制', 'zh_TW' => '查詢限制', 'th' => 'ตรวจสอบข้อจำกัด', 'ja' => '制限確認', 'ko' => '제한 확인'], sort: 13)]
    public function restriction(int $id, string $type): JsonResponse
    {
        return apiSuccess($this->service->restriction($id, $type));
    }

    #[MenuAction(['en' => 'Reset password', 'zh_CN' => '重置密码', 'zh_TW' => '重設密碼', 'th' => 'รีเซ็ตรหัสผ่าน', 'ja' => 'パスワード再設定', 'ko' => '비밀번호 재설정'], sort: 4)]
    #[RequireSecurityPassword(mode: 'dialog')]
    public function password(int $id, UserRequest $request): JsonResponse
    {
        $this->service->password($id, $request->validated());

        return apiSuccess();
    }

    #[MenuAction(['en' => 'Resend verification', 'zh_CN' => '重发验证邮件', 'zh_TW' => '重發驗證郵件', 'th' => 'ส่งอีเมลยืนยันอีกครั้ง', 'ja' => '確認メール再送', 'ko' => '인증 메일 재전송'], sort: 5)]
    public function verification(int $id): JsonResponse
    {
        $this->service->verification($id);

        return apiSuccess(message: __('auth.verification_sent'));
    }

    #[MenuAction(['en' => 'Manage VIP', 'zh_CN' => '会员设置', 'zh_TW' => '會員設定', 'th' => 'ตั้งค่าสมาชิก VIP', 'ja' => 'VIP設定', 'ko' => 'VIP 설정'], sort: 6)]
    public function vip(int $id, UserRequest $request): JsonResponse
    {
        return apiSuccess(new UserResource($this->service->vip($id, $request->validated())));
    }

    #[MenuAction(['en' => 'VIP options', 'zh_CN' => '会员选项', 'zh_TW' => '會員選項', 'th' => 'ตัวเลือกสมาชิก VIP', 'ja' => 'VIPオプション', 'ko' => 'VIP 옵션'], sort: 7)]
    public function vipOptions(): JsonResponse
    {
        return apiSuccess($this->service->vipOptions());
    }

    #[MenuAction(['en' => 'VIP history', 'zh_CN' => '会员记录', 'zh_TW' => '會員記錄', 'th' => 'ประวัติสมาชิก VIP', 'ja' => 'VIP履歴', 'ko' => 'VIP 기록'], sort: 8)]
    public function vipHistory(int $id, UserRequest $request): JsonResponse
    {
        $page = $this->service->vipHistory($id, $request->validated());

        return apiPaginate($page, MemberVipRecordResource::collection($page));
    }

    #[MenuAction(['en' => 'Logs', 'zh_CN' => '日志', 'zh_TW' => '日誌', 'th' => 'บันทึก', 'ja' => 'ログ', 'ko' => '로그'], sort: 9)]
    public function logs(int $id, UserRequest $request): JsonResponse
    {
        $page = $this->service->logs($id, $request->validated());

        return apiPaginate($page, UserLogResource::collection($page));
    }
}
