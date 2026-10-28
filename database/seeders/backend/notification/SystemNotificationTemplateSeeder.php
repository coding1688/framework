<?php

namespace Database\Seeders\backend\notification;

use App\Models\System\SystemNotificationTemplate;
use Illuminate\Database\Seeder;

class SystemNotificationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            'security.password_reset' => [
                ['en' => 'Password reset', 'zh_CN' => '密码已重置', 'zh_TW' => '密碼已重設', 'th' => 'รีเซ็ตรหัสผ่านแล้ว', 'ja' => 'パスワード再設定', 'ko' => '비밀번호 재설정'],
                ['en' => 'Your password was reset. If this was not you, contact an administrator immediately.', 'zh_CN' => '你的账户密码已重置。如非本人操作，请立即联系管理员。', 'zh_TW' => '你的帳戶密碼已重設。如非本人操作，請立即聯絡管理員。', 'th' => 'รหัสผ่านของคุณถูกรีเซ็ตแล้ว หากไม่ใช่คุณ โปรดติดต่อผู้ดูแลทันที', 'ja' => 'パスワードが再設定されました。心当たりがない場合は管理者へ連絡してください。', 'ko' => '비밀번호가 재설정되었습니다. 본인이 아니면 즉시 관리자에게 문의하세요.'],
            ],
            'security.password_changed' => [
                ['en' => 'Password changed', 'zh_CN' => '密码已修改', 'zh_TW' => '密碼已修改', 'th' => 'เปลี่ยนรหัสผ่านแล้ว', 'ja' => 'パスワード変更', 'ko' => '비밀번호 변경'],
                ['en' => 'Your account password was changed successfully.', 'zh_CN' => '你的账户密码已成功修改。', 'zh_TW' => '你的帳戶密碼已成功修改。', 'th' => 'เปลี่ยนรหัสผ่านบัญชีของคุณสำเร็จแล้ว', 'ja' => 'アカウントのパスワードが変更されました。', 'ko' => '계정 비밀번호가 변경되었습니다.'],
            ],
            'security.email_changed' => [
                ['en' => 'Email changed', 'zh_CN' => '邮箱已修改', 'zh_TW' => '電子郵件已修改', 'th' => 'เปลี่ยนอีเมลแล้ว', 'ja' => 'メールアドレス変更', 'ko' => '이메일 변경'],
                ['en' => 'Your account email was changed to {{ email }}. Please verify the new address.', 'zh_CN' => '你的账户邮箱已修改为 {{ email }}，请验证新邮箱。', 'zh_TW' => '你的帳戶電子郵件已修改為 {{ email }}，請驗證新地址。', 'th' => 'อีเมลบัญชีของคุณเปลี่ยนเป็น {{ email }} โปรดยืนยันอีเมลใหม่', 'ja' => 'メールアドレスが {{ email }} に変更されました。新しいアドレスを確認してください。', 'ko' => '계정 이메일이 {{ email }}(으)로 변경되었습니다. 새 주소를 인증하세요.'],
            ],
            'security.account_status' => [
                ['en' => 'Account status changed', 'zh_CN' => '账户状态已变更', 'zh_TW' => '帳戶狀態已變更', 'th' => 'สถานะบัญชีเปลี่ยนแล้ว', 'ja' => 'アカウント状態変更', 'ko' => '계정 상태 변경'],
                ['en' => 'Your account status was changed to {{ status }}.', 'zh_CN' => '你的账户状态已变更为 {{ status }}。', 'zh_TW' => '你的帳戶狀態已變更為 {{ status }}。', 'th' => 'สถานะบัญชีของคุณเปลี่ยนเป็น {{ status }}', 'ja' => 'アカウントの状態が {{ status }} に変更されました。', 'ko' => '계정 상태가 {{ status }}(으)로 변경되었습니다.'],
            ],
        ];
        foreach ($templates as $code => [$name, $content]) {
            SystemNotificationTemplate::firstOrCreate(['code' => $code], [
                'name_i18n' => $name, 'subject_i18n' => $name, 'content_i18n' => $content,
                'channels' => ['database', 'mail'], 'active' => true,
            ]);
        }
    }
}
