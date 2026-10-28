<?php

namespace App\Libs\Notifications;

use App\Models\System\SystemNotificationTemplate;
use App\Notifications\TemplatedMailNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;

class NotificationManager
{
    /** @param array<string, scalar> $variables */
    public function send(Model $notifiable, string $code, array $variables = [], ?string $actionUrl = null): void
    {
        $template = SystemNotificationTemplate::where('code', $code)->where('active', true)->first();
        if (! $template) {
            return;
        }
        $preferences = $notifiable->notification_preferences ?? [];
        $disabled = $preferences[$code] ?? [];
        $subject = $this->renderDictionary($template->subject_i18n, $variables);
        $content = $this->renderDictionary($template->content_i18n, $variables);
        $channels = array_intersect($template->channels, ['database', 'mail']);
        if (in_array('database', $channels, true) && ! in_array('database', $disabled, true)) {
            DatabaseNotification::create([
                'id' => (string) Str::uuid(), 'type' => TemplatedMailNotification::class,
                'notifiable_type' => $notifiable->getMorphClass(), 'notifiable_id' => $notifiable->getKey(),
                'data' => ['code' => $code, 'subject_i18n' => $subject, 'content_i18n' => $content, 'action_url' => $actionUrl],
            ]);
        }
        if (in_array('mail', $channels, true) && ! in_array('mail', $disabled, true)) {
            $locale = $notifiable->locale ?? app()->getLocale();
            $notifiable->notify(new TemplatedMailNotification(
                $this->localized($subject, $locale), $this->localized($content, $locale), $actionUrl,
            ));
        }
    }

    /** @param array<string, ?string> $translations @param array<string, scalar> $variables @return array<string, string> */
    private function renderDictionary(array $translations, array $variables): array
    {
        return array_map(fn (?string $value): string => $this->render($value ?? '', $variables), $translations);
    }

    /** @param array<string, scalar> $variables */
    private function render(string $template, array $variables): string
    {
        return preg_replace_callback('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', function (array $match) use ($variables): string {
            return array_key_exists($match[1], $variables)
                ? htmlspecialchars((string) $variables[$match[1]], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                : $match[0];
        }, $template);
    }

    /** @param array<string, string> $translations */
    private function localized(array $translations, string $locale): string
    {
        foreach (array_unique([$locale, 'en', config('app.fallback_locale'), 'zh_CN', ...array_keys($translations)]) as $key) {
            if (trim($translations[$key] ?? '') !== '') {
                return $translations[$key];
            }
        }

        return '';
    }
}
