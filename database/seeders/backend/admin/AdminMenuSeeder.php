<?php

namespace Database\Seeders\backend\admin;

use App\Models\Admin\AdminMenu;
use App\Services\Backend\Common\MenuDiscovery;
use Illuminate\Database\Seeder;

class AdminMenuSeeder extends Seeder
{
    public function run(): void
    {
        $root = AdminMenu::firstOrCreate(['permission' => 'admin'], ['name_i18n' => ['en' => 'Administration', 'zh_CN' => '系统管理', 'zh_TW' => '系統管理', 'th' => 'การดูแลระบบ', 'ja' => '管理', 'ko' => '관리'], 'type' => 'directory', 'icon' => 'team-line', 'is_system' => true]);
        $member = AdminMenu::firstOrCreate(['permission' => 'member'], ['name_i18n' => ['en' => 'Member Management', 'zh_CN' => '会员管理', 'zh_TW' => '會員管理', 'th' => 'การจัดการสมาชิก', 'ja' => '会員管理', 'ko' => '회원 관리'], 'type' => 'directory', 'icon' => 'group-line', 'is_system' => true]);
        $content = AdminMenu::firstOrCreate(['permission' => 'content'], ['name_i18n' => ['en' => 'Content', 'zh_CN' => '内容管理', 'zh_TW' => '內容管理', 'th' => 'จัดการเนื้อหา', 'ja' => 'コンテンツ管理', 'ko' => '콘텐츠 관리'], 'type' => 'directory', 'icon' => 'article-line', 'is_system' => true]);
        $development = AdminMenu::firstOrCreate(['permission' => 'development'], ['name_i18n' => ['en' => 'Development Management', 'zh_CN' => '开发管理', 'zh_TW' => '開發管理', 'th' => 'การจัดการการพัฒนา', 'ja' => '開発管理', 'ko' => '개발 관리'], 'type' => 'directory', 'icon' => 'terminal-box-line', 'is_system' => true]);
        $system = AdminMenu::firstOrCreate(['permission' => 'system'], ['name_i18n' => ['en' => 'System', 'zh_CN' => '系统配置', 'zh_TW' => '系統設定', 'th' => 'ระบบ', 'ja' => 'システム', 'ko' => '시스템'], 'type' => 'directory', 'icon' => 'settings-2-line', 'is_system' => true]);
        $this->fillTranslations($root, ['en' => 'Administration', 'zh_CN' => '系统管理', 'zh_TW' => '系統管理', 'th' => 'การดูแลระบบ', 'ja' => '管理', 'ko' => '관리']);
        $this->fillTranslations($member, ['en' => 'Member Management', 'zh_CN' => '会员管理', 'zh_TW' => '會員管理', 'th' => 'การจัดการสมาชิก', 'ja' => '会員管理', 'ko' => '회원 관리']);
        $this->fillTranslations($content, ['en' => 'Content', 'zh_CN' => '内容管理', 'zh_TW' => '內容管理', 'th' => 'จัดการเนื้อหา', 'ja' => 'コンテンツ管理', 'ko' => '콘텐츠 관리']);
        $this->fillTranslations($development, ['en' => 'Development Management', 'zh_CN' => '开发管理', 'zh_TW' => '開發管理', 'th' => 'การจัดการการพัฒนา', 'ja' => '開発管理', 'ko' => '개발 관리']);
        $this->fillTranslations($system, ['en' => 'System', 'zh_CN' => '系统配置', 'zh_TW' => '系統設定', 'th' => 'ระบบ', 'ja' => 'システム', 'ko' => '시스템']);
        foreach ([[$root, 'team-line'], [$member, 'group-line'], [$content, 'article-line'], [$development, 'terminal-box-line'], [$system, 'settings-2-line']] as [$menu, $icon]) {
            if (in_array($menu->icon, [null, '', 'folder-line'], true)) {
                $menu->forceFill(['icon' => $icon])->save();
            }
        }
        $developmentTranslations = $development->name_i18n ?? [];
        $legacyDevelopmentNames = ['en' => 'Development', 'zh_CN' => '开发配置', 'zh_TW' => '開發設定', 'th' => 'การพัฒนา', 'ja' => '開発', 'ko' => '개발'];
        $developmentNames = ['en' => 'Development Management', 'zh_CN' => '开发管理', 'zh_TW' => '開發管理', 'th' => 'การจัดการการพัฒนา', 'ja' => '開発管理', 'ko' => '개발 관리'];
        foreach ($legacyDevelopmentNames as $locale => $legacyName) {
            if (($developmentTranslations[$locale] ?? null) === $legacyName) {
                $developmentTranslations[$locale] = $developmentNames[$locale];
            }
        }
        $development->forceFill(['name_i18n' => $developmentTranslations, 'sort' => 1000])->save();
        foreach (['system.queue', 'system.schedule'] as $permission) {
            $menu = AdminMenu::where('permission', $permission)->first();
            if ($menu && $menu->parent_id === $system->id) {
                $menu->forceFill(['parent_id' => $development->id])->save();
            }
        }
        $frontendUser = AdminMenu::where('permission', 'frontend.user')->first();
        if ($frontendUser) {
            $translations = $frontendUser->name_i18n ?? [];
            $legacyNames = ['en' => 'Frontend users', 'zh_CN' => '前台用户', 'zh_TW' => '前台使用者', 'th' => 'ผู้ใช้ส่วนหน้า', 'ja' => 'フロントエンドユーザー', 'ko' => '프런트엔드 사용자'];
            $names = ['en' => 'User Management', 'zh_CN' => '用户管理', 'zh_TW' => '使用者管理', 'th' => 'การจัดการผู้ใช้', 'ja' => 'ユーザー管理', 'ko' => '사용자 관리'];
            foreach ($legacyNames as $locale => $legacyName) {
                if (($translations[$locale] ?? null) === $legacyName) {
                    $translations[$locale] = $names[$locale];
                }
            }
            $frontendUser->forceFill(['name_i18n' => $translations]);
            if ($frontendUser->parent_id === $root->id) {
                $frontendUser->forceFill(['parent_id' => $member->id]);
            }
            if ($frontendUser->isDirty()) {
                $frontendUser->save();
            }
        }
        $sections = [
            'administrators' => ['en' => 'Administrators', 'zh_CN' => '管理员', 'zh_TW' => '管理員', 'th' => 'ผู้ดูแล', 'ja' => '管理者', 'ko' => '관리자'],
            'departments' => ['en' => 'Departments', 'zh_CN' => '部门管理', 'zh_TW' => '部門管理', 'th' => 'แผนก', 'ja' => '部門', 'ko' => '부서'],
            'menus' => ['en' => 'Backend menus', 'zh_CN' => '后台菜单', 'zh_TW' => '後台選單', 'th' => 'เมนูแผงควบคุม', 'ja' => '管理メニュー', 'ko' => '관리 메뉴'],
            'logs' => ['en' => 'Audit log', 'zh_CN' => '操作日志', 'zh_TW' => '操作日誌', 'th' => 'บันทึกการตรวจสอบ', 'ja' => '監査ログ', 'ko' => '감사 로그'],
        ];
        $pagePaths = ['administrators' => 'admin/user', 'departments' => 'admin/department', 'menus' => 'admin/menu', 'logs' => 'admin/log'];
        $actions = [
            'list' => ['en' => 'List', 'zh_CN' => '列表', 'zh_TW' => '列表', 'th' => 'รายการ', 'ja' => '一覧', 'ko' => '목록'],
            'create' => ['en' => 'Create', 'zh_CN' => '新增', 'zh_TW' => '新增', 'th' => 'สร้าง', 'ja' => '作成', 'ko' => '생성'],
            'update' => ['en' => 'Edit', 'zh_CN' => '编辑', 'zh_TW' => '編輯', 'th' => 'แก้ไข', 'ja' => '編集', 'ko' => '편집'],
            'delete' => ['en' => 'Delete', 'zh_CN' => '删除', 'zh_TW' => '刪除', 'th' => 'ลบ', 'ja' => '削除', 'ko' => '삭제'],
            'password' => ['en' => 'Reset password', 'zh_CN' => '重置密码', 'zh_TW' => '重設密碼', 'th' => 'รีเซ็ตรหัสผ่าน', 'ja' => 'パスワードをリセット', 'ko' => '비밀번호 재설정'],
        ];
        $sort = 0;
        foreach ($sections as $section => $names) {
            $prefix = config('platform.backend_ui.permission_prefixes.'.$section);
            $menu = AdminMenu::firstOrCreate(['permission' => $prefix], [
                'name_i18n' => $names, 'type' => 'page', 'path' => route('backend.menu', ['path' => $pagePaths[$section]], false), 'sort' => $sort++,
                'parent_id' => $section === 'menus' ? $development->id : $root->id,
            ]);
            $metadata = [
                'administrators' => ['group-line', 'admin/user/List', 'User', 'user-settings-line'],
                'departments' => ['organization-chart', 'admin/department/List', 'OfficeBuilding', 'organization-chart'],
                'menus' => ['node-tree', 'admin/menu/List', 'Menu', 'node-tree'],
                'logs' => ['file-history-line', 'admin/log/List', 'Document', 'file-history-line'],
            ][$section];
            $menu->forceFill(['is_system' => true, 'icon' => ! $menu->icon || in_array($menu->icon, [$metadata[2], $metadata[3]], true) ? $metadata[0] : $menu->icon, 'component' => $menu->component ?: $metadata[1]])->save();
            $this->fillTranslations($menu, $names);
            foreach ($actions as $action => $labels) {
                if ($section === 'logs' && $action !== 'list') {
                    continue;
                }
                if ($action === 'password' && $section !== 'administrators') {
                    continue;
                }
                $button = AdminMenu::firstOrCreate(['permission' => $prefix.'.'.$action], [
                    'name_i18n' => $labels, 'type' => 'button', 'parent_id' => $menu->id, 'sort' => $sort++,
                ]);
                $button->forceFill(['is_system' => true, 'route_names' => []])->save();
                $this->fillTranslations($button, $labels);
            }
        }
        app(MenuDiscovery::class)->sync();
    }

    private function fillTranslations(AdminMenu $menu, array $defaults): void
    {
        $menu->forceFill(['name_i18n' => array_replace($defaults, supportedTranslations($menu->name_i18n))])->save();
    }
}
