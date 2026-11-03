<?php

namespace Database\Seeders\backend\setting;

use App\Libs\Fields\FieldRegistry;
use App\Libs\Settings\SettingStore;
use App\Models\System\SystemSettingDefinition;
use App\Models\System\SystemSettingGroup;
use App\Models\System\SystemSettingValue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            'website' => ['Website', '网站配置', '網站配置', 'เว็บไซต์', 'Web サイト', '웹사이트'],
            'site' => ['Site', '站点配置', '站點配置', 'ไซต์', 'サイト', '사이트'],
            'backend' => ['Administration', '后台配置', '後台配置', 'การดูแลระบบ', '管理', '관리'],
            'system' => ['System', '系统配置', '系統配置', 'ระบบ', 'システム', '시스템'],
            'schedule' => ['Scheduling', '计划任务', '排程任務', 'งานตามกำหนดเวลา', 'スケジュール', '스ケジュール'],
            'ai' => ['AI', 'AI 配置', 'AI 設定', 'การตั้งค่า AI', 'AI 設定', 'AI 설정'],
            'mail' => ['Email', '邮件配置', '郵件設定', 'อีเมล', 'メール', '이메일'],
            'member' => ['Member', '会员配置', '會員配置', 'การตั้งค่าสมาชิก', '会員設定', '회원 설정'],
        ];
        $models = [];
        foreach ($groups as $code => $labels) {
            $models[$code] = SystemSettingGroup::firstOrCreate(['code' => $code], ['parent_id' => null, 'name_i18n' => $this->translations($labels), 'active' => true, 'sort' => count($models)]);
            $this->fillTranslations($models[$code], $this->translations($labels));
        }
        $aiGroups = [
            'chatgpt' => ['ChatGPT', 'ChatGPT', 'ChatGPT', 'ChatGPT', 'ChatGPT', 'ChatGPT'],
            'claude' => ['Claude', 'Claude', 'Claude', 'Claude', 'Claude', 'Claude'],
            'kimi' => ['Kimi', 'Kimi', 'Kimi', 'Kimi', 'Kimi', 'Kimi'],
        ];
        foreach ($aiGroups as $code => $labels) {
            $models[$code] = SystemSettingGroup::firstOrCreate(['code' => $code], ['parent_id' => $models['ai']->id, 'name_i18n' => $this->translations($labels), 'active' => true, 'sort' => count($models)]);
            if ((int) $models[$code]->parent_id !== (int) $models['ai']->id) {
                $models[$code]->forceFill(['parent_id' => $models['ai']->id])->save();
            }
            $this->fillTranslations($models[$code], $this->translations($labels));
        }
        $definitions = [
            ['website', 'website.name', ['Website name', '网站名称', '網站名稱', 'ชื่อเว็บไซต์', 'Web サイト名', '웹사이트 이름'], 'localized_text', ['value_i18n' => ['en' => 'Framework', 'zh_CN' => 'Framework', 'zh_TW' => 'Framework', 'th' => 'Framework', 'ja' => 'Framework', 'ko' => 'Framework']], []],
            ['site', 'site.default_language', ['Default language', '默认语言', '預設語言', 'ภาษาเริ่มต้น', 'デフォルト言語', '기본 언어'], 'select', ['value_string' => 'en'], []],
            ['backend', 'backend.session_minutes', ['Session duration (minutes)', '会话时长（分钟）', '工作階段時長（分鐘）', 'ระยะเวลาเซสชัน (นาที)', 'セッション時間（分）', '세션 시간(분)'], 'number', ['value_number' => 120], ['precision' => 0, 'min_value' => 5, 'max_value' => 1440]],
            ['system', 'system.maintenance', ['Maintenance mode', '维护模式', '維護模式', 'โหมดบำรุงรักษา', 'メンテナンスモード', '유지 관리 모드'], 'switch', ['value_boolean' => false], []],
            ['schedule', 'schedule.enabled', ['Automatic scheduling', '自动调度', '自動排程', 'การกำหนดเวลาอัตโนมัติ', '自動スケジュール', '자동 스케줄링'], 'switch', ['value_boolean' => true], []],
            ['schedule', 'schedule.timezone', ['Default timezone', '默认时区', '預設時區', 'เขตเวลาเริ่มต้น', 'デフォルトタイムゾーン', '기본 시간대'], 'text', ['value_string' => 'Asia/Shanghai'], ['min_length' => 1, 'max_length' => 64]],
            ['schedule', 'schedule.log_retention_days', ['Log retention (days)', '日志保留天数', '日誌保留天數', 'ระยะเวลาเก็บบันทึก (วัน)', 'ログ保持期間（日）', '로그 보관 기간(일)'], 'number', ['value_number' => 90], ['precision' => 0, 'min_value' => 1, 'max_value' => 3650]],
            ['ai', 'ai.enabled', ['Enable AI', '启用 AI', '啟用 AI', 'เปิดใช้งาน AI', 'AI を有効にする', 'AI 활성화'], 'switch', ['value_boolean' => false], []],
            ['ai', 'ai.provider', ['AI provider', 'AI 服务商', 'AI 服務商', 'ผู้ให้บริการ AI', 'AI プロバイダー', 'AI 제공업체'], 'select', ['value_string' => 'openai'], [], [
                'en' => ['openai' => 'ChatGPT', 'claude' => 'Claude', 'kimi' => 'Kimi'],
                'zh_CN' => ['openai' => 'ChatGPT', 'claude' => 'Claude', 'kimi' => 'Kimi'],
                'zh_TW' => ['openai' => 'ChatGPT', 'claude' => 'Claude', 'kimi' => 'Kimi'],
                'th' => ['openai' => 'ChatGPT', 'claude' => 'Claude', 'kimi' => 'Kimi'],
                'ja' => ['openai' => 'ChatGPT', 'claude' => 'Claude', 'kimi' => 'Kimi'],
                'ko' => ['openai' => 'ChatGPT', 'claude' => 'Claude', 'kimi' => 'Kimi'],
            ]],
            ['chatgpt', 'ai.openai.api_key', ['ChatGPT API key', 'ChatGPT API 密钥', 'ChatGPT API 金鑰', 'ChatGPT API key', 'ChatGPT API キー', 'ChatGPT API 키'], 'text', null, ['min_length' => 0, 'max_length' => 512], null, true],
            ['chatgpt', 'ai.openai.text_model', ['Text model', '文本模型', '文字模型', 'โมเดลข้อความ', 'テキストモデル', '텍스트 모델'], 'text', ['value_string' => 'gpt-4.1'], ['max_length' => 128]],
            ['chatgpt', 'ai.openai.image_model', ['Image model', '图片模型', '圖片模型', 'โมเดลรูปภาพ', '画像モデル', '이미지 모델'], 'text', ['value_string' => 'gpt-image-1'], ['max_length' => 128]],
            ['chatgpt', 'ai.openai.moderation_model', ['Moderation model', '审核模型', '審核模型', 'โมเดลกลั่นกรอง', 'モデレーションモデル', '콘텐츠 검열 모델'], 'text', ['value_string' => 'omni-moderation-latest'], ['max_length' => 128]],
            ['claude', 'ai.claude.api_key', ['Claude API key', 'Claude API 密钥', 'Claude API 金鑰', 'Claude API key', 'Claude API キー', 'Claude API 키'], 'text', null, ['min_length' => 0, 'max_length' => 512], null, true],
            ['claude', 'ai.claude.text_model', ['Text model', '文本模型', '文字模型', 'โมเดลข้อความ', 'テキストモデル', '텍스트 모델'], 'text', ['value_string' => 'claude-sonnet-4-20250514'], ['max_length' => 128]],
            ['kimi', 'ai.kimi.api_key', ['Kimi API key', 'Kimi API 密钥', 'Kimi API 金鑰', 'Kimi API key', 'Kimi API キー', 'Kimi API 키'], 'text', null, ['min_length' => 0, 'max_length' => 512], null, true],
            ['kimi', 'ai.kimi.text_model', ['Text model', '文本模型', '文字模型', 'โมเดลข้อความ', 'テキストモデル', '텍스트 모델'], 'text', ['value_string' => 'kimi-k2-0905-preview'], ['max_length' => 128]],
            ['ai', 'ai.http_timeout', ['Text timeout (seconds)', '文本请求超时（秒）', '文字請求逾時（秒）', 'เวลาหมดเขตข้อความ (วินาที)', 'テキストタイムアウト（秒）', '텍스트 시간 초과(초)'], 'number', ['value_number' => 90], ['precision' => 0, 'min_value' => 1, 'max_value' => 180]],
            ['ai', 'ai.image_timeout', ['Image timeout (seconds)', '图片请求超时（秒）', '圖片請求逾時（秒）', 'เวลาหมดเขตรูปภาพ (วินาที)', '画像タイムアウト（秒）', '이미지 시간 초과(초)'], 'number', ['value_number' => 180], ['precision' => 0, 'min_value' => 1, 'max_value' => 300]],
            ['ai', 'ai.max_output_tokens', ['Maximum output tokens', '最大输出 Token', '最大輸出 Token', 'โทเคนเอาต์พุตสูงสุด', '最大出力トークン', '최대 출력 토큰'], 'number', ['value_number' => 16384], ['precision' => 0, 'min_value' => 256, 'max_value' => 32768]],
            ['mail', 'mail.smtp.enabled', ['Use SMTP settings', '启用系统 SMTP 配置', '啟用系統 SMTP 設定', 'ใช้การตั้งค่า SMTP', 'SMTP 設定を使用', 'SMTP 설정 사용'], 'switch', ['value_boolean' => false], []],
            ['mail', 'mail.smtp.host', ['SMTP host', 'SMTP 服务器', 'SMTP 伺服器', 'โฮสต์ SMTP', 'SMTP ホスト', 'SMTP 호스트'], 'text', null, ['min_length' => 1, 'max_length' => 256]],
            ['mail', 'mail.smtp.port', ['SMTP port', 'SMTP 端口', 'SMTP 連接埠', 'พอร์ต SMTP', 'SMTP ポート', 'SMTP 포트'], 'number', ['value_number' => 587], ['precision' => 0, 'min_value' => 1, 'max_value' => 65535]],
            ['mail', 'mail.smtp.encryption', ['Encryption', '加密方式', '加密方式', 'การเข้ารหัส', '暗号化', '암호화'], 'select', ['value_string' => 'tls'], [], ['en' => ['tls' => 'STARTTLS (587)', 'smtps' => 'SSL/TLS (465)', 'none' => 'None'], 'zh_CN' => ['tls' => 'STARTTLS（587）', 'smtps' => 'SSL/TLS（465）', 'none' => '不加密'], 'zh_TW' => ['tls' => 'STARTTLS（587）', 'smtps' => 'SSL/TLS（465）', 'none' => '不加密'], 'th' => ['tls' => 'STARTTLS (587)', 'smtps' => 'SSL/TLS (465)', 'none' => 'ไม่เข้ารหัส'], 'ja' => ['tls' => 'STARTTLS (587)', 'smtps' => 'SSL/TLS (465)', 'none' => '暗号化なし'], 'ko' => ['tls' => 'STARTTLS (587)', 'smtps' => 'SSL/TLS (465)', 'none' => '암호화 없음']]],
            ['mail', 'mail.smtp.username', ['SMTP username', 'SMTP 账号', 'SMTP 帳號', 'ชื่อผู้ใช้ SMTP', 'SMTP ユーザー名', 'SMTP 사용자 이름'], 'text', ['value_string' => ''], ['max_length' => 256]],
            ['mail', 'mail.smtp.password', ['SMTP password / authorization code', 'SMTP 密码 / 授权码', 'SMTP 密碼 / 授權碼', 'รหัสผ่าน / รหัสอนุญาต SMTP', 'SMTP パスワード / 認証コード', 'SMTP 비밀번호 / 인증 코드'], 'text', null, ['max_length' => 512], null, true],
            ['mail', 'mail.from.address', ['From email address', '发件人邮箱', '寄件者信箱', 'อีเมลผู้ส่ง', '送信者メールアドレス', '발신자 이메일'], 'text', null, ['min_length' => 1, 'max_length' => 256]],
            ['mail', 'mail.from.name', ['From name', '发件人名称', '寄件者名稱', 'ชื่อผู้ส่ง', '送信者名', '발신자 이름'], 'text', ['value_string' => 'Framework'], ['min_length' => 1, 'max_length' => 128]],
            ['mail', 'mail.smtp.timeout', ['SMTP timeout (seconds)', 'SMTP 超时（秒）', 'SMTP 逾時（秒）', 'เวลาหมดเขต SMTP (วินาที)', 'SMTP タイムアウト（秒）', 'SMTP 시간 초과(초)'], 'number', ['value_number' => 15], ['precision' => 0, 'min_value' => 1, 'max_value' => 120]],
            ['member', 'member.wallet.main_currency', ['Main wallet currency', '主钱包币种', '主錢包幣種', 'สกุลเงินกระเป๋าหลัก', 'メインウォレット通貨', '주 지갑 통화'], 'select', ['value_string' => 'RMB'], [], [
                'en' => ['RMB' => 'RMB', 'USDT' => 'USDT'], 'zh_CN' => ['RMB' => 'RMB', 'USDT' => 'USDT'], 'zh_TW' => ['RMB' => 'RMB', 'USDT' => 'USDT'],
                'th' => ['RMB' => 'RMB', 'USDT' => 'USDT'], 'ja' => ['RMB' => 'RMB', 'USDT' => 'USDT'], 'ko' => ['RMB' => 'RMB', 'USDT' => 'USDT'],
            ]],
        ];
        $fields = app(FieldRegistry::class);
        foreach ($definitions as $sort => $definition) {
            [$group, $key, $labels, $inputType, $default, $validation] = $definition;
            $model = SystemSettingDefinition::firstOrCreate(['key' => $key], [
                'group_id' => $models[$group]->id,
                'name_i18n' => $this->translations($labels),
                'description_i18n' => null,
                'input_type' => $inputType, 'multiple' => $definition[8] ?? false,
                'option_source_type' => in_array($key, ['site.default_country', 'site.enabled_countries', 'site.default_language'], true) ? 'constant' : 'manual',
                'option_source_key' => match ($key) {
                    'site.default_country' => 'common.country',
                    'site.enabled_countries' => 'common.country',
                    'site.default_language' => 'common.language',
                    default => null,
                },
                'validation' => $fields->normalizeValidation($inputType, $definition[8] ?? false, $validation),
                'options_i18n' => $definition[6] ?? null,
                'default_value' => $default,
                'secret' => $definition[7] ?? false, 'active' => true, 'sort' => $sort,
            ]);
            $this->fillTranslations($model, $this->translations($labels));
            if (str_starts_with($key, 'ai.') && (int) $model->group_id !== (int) $models[$group]->id) {
                $model->forceFill(['group_id' => $models[$group]->id])->save();
            }
            if ($key === 'ai.provider') {
                $options = $model->options_i18n ?? [];
                foreach ($definition[6] as $locale => $defaults) {
                    $existing = $options[$locale] ?? [];
                    if (($existing['openai'] ?? null) === 'OpenAI') {
                        $existing['openai'] = 'ChatGPT';
                    }
                    $options[$locale] = array_replace($defaults, $existing);
                }
                $model->forceFill(['options_i18n' => $options])->save();
            } elseif (($definition[6] ?? null) !== null) {
                $this->fillTranslations($model, $definition[6], 'options_i18n');
            }
            if (is_array($default) && array_key_exists('value_i18n', $default)) {
                $currentDefault = $model->default_value ?? [];
                $currentDefault['value_i18n'] = array_replace($default['value_i18n'], supportedTranslations($currentDefault['value_i18n'] ?? []));
                $model->forceFill(['default_value' => $currentDefault])->save();
            }
            if (in_array($key, ['site.default_country', 'site.enabled_countries', 'site.default_language'], true)) {
                $model->update([
                    'group_id' => $models['site']->id,
                    'name_i18n' => [...$this->translations($labels), ...($model->name_i18n ?? [])],
                    'option_source_type' => 'constant',
                    'option_source_key' => match ($key) {
                        'site.enabled_countries' => 'common.country',
                        'site.default_language' => 'common.language',
                        default => 'common.country',
                    },
                    'options_i18n' => null,
                ]);
            }
        }
        foreach ([SystemSettingDefinition::class] as $modelClass) {
            $modelClass::where('input_type', 'localized_text')->each(function (SystemSettingDefinition $definition): void {
                $validation = $definition->validation ?? [];
                $supported = $validation['supported_locales'] ?? [];
                sort($supported);
                if ($supported === ['en', 'tl', 'zh_CN', 'zh_TW']) {
                    $definition->update(['validation' => [...$validation, 'supported_locales' => array_keys(config('platform.locales'))]]);
                }
            });
        }
        SystemSettingValue::whereHas('definition', fn ($query) => $query->where('key', 'website.name'))->get()->each(function (SystemSettingValue $value): void {
            $payload = $value->payload ?? [];
            $translations = $payload['value_i18n'] ?? [];
            if ($translations !== [] && count(array_filter($translations, fn ($translation) => $translation === 'Framework')) === count($translations)) {
                $payload['value_i18n'] = array_replace(array_fill_keys(array_keys(config('platform.locales')), 'Framework'), supportedTranslations($translations));
                $value->forceFill(['payload' => $payload])->save();
            }
        });
        app(SettingStore::class)->forget();
    }

    private function translations(array $labels): array
    {
        return array_combine(['en', 'zh_CN', 'zh_TW', 'th', 'ja', 'ko'], $labels);
    }

    private function fillTranslations(Model $model, array $defaults, string $field = 'name_i18n'): void
    {
        $model->forceFill([$field => array_replace($defaults, supportedTranslations($model->{$field}))])->save();
    }
}
