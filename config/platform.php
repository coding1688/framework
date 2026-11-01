<?php

return [
    'backend_domain' => env('BACKEND_DOMAIN', 'a.base.local'),
    'partner_domain' => env('PARTNER_DOMAIN', 'p.base.local'),
    'frontend_domain' => env('FRONTEND_DOMAIN', 'www.base.local'),
    'backend_ui' => [
        'locales' => ['zh_CN' => '简体中文', 'zh_TW' => '繁體中文', 'en' => 'English', 'th' => 'ไทย', 'ja' => '日本語', 'ko' => '한국어'],
        'brand' => 'Framework',
        'source_locale' => 'en',
        'notice_duration' => 3500,
        'request_timeout' => 30000,
        'dialog_width' => '76vw',
        'theme' => [
            'defaults' => ['mode' => 'system', 'preset' => 'emerald', 'density' => 'comfortable', 'radius' => 'medium', 'button_size' => 'default'],
            'options' => [
                'mode' => ['light', 'dark', 'system'],
                'preset' => ['emerald', 'azure', 'slate'],
                'density' => ['comfortable', 'compact'],
                'radius' => ['small', 'medium', 'large'],
                'button_size' => ['small', 'default', 'large'],
            ],
            'presets' => [
                'emerald' => ['color' => '#228568'],
                'azure' => ['color' => '#3867d6'],
                'slate' => ['color' => '#64748b'],
            ],
        ],
        'permission_prefixes' => ['queues' => 'system.queue', 'schedules' => 'system.schedule', 'notificationTemplates' => 'system.notificationTemplate', 'frontendUsers' => 'frontend.user', 'vipLevels' => 'member.vipLevel', 'memberWallet' => 'member.wallet', 'administrators' => 'admin.user', 'departments' => 'admin.department', 'menus' => 'admin.menu', 'logs' => 'admin.log', 'announcements' => 'content.announcement', 'carousels' => 'content.carousel', 'files' => 'file.item', 'fileStorages' => 'file.storage', 'settings' => 'system.setting', 'settingDefinitions' => 'development.setting', 'settingGroups' => 'development.settingGroup'],
    ],
    'locales' => [
        'zh_CN' => '简体中文', 'zh_TW' => '繁體中文', 'en' => 'English',
        'th' => 'ไทย', 'ja' => '日本語', 'ko' => '한국어',
    ],
];
