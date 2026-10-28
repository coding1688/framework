<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = $this->data;

        return [
            'id' => $this->id, 'code' => $data['code'] ?? null,
            'subject' => $this->localized($data['subject_i18n'] ?? []),
            'content' => $this->localized($data['content_i18n'] ?? []),
            'action_url' => $data['action_url'] ?? null,
            'read_at' => $this->read_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    private function localized(array $translations): string
    {
        foreach (array_unique([app()->getLocale(), 'en', config('app.fallback_locale'), 'zh_CN', ...array_keys($translations)]) as $locale) {
            if (is_string($translations[$locale] ?? null) && trim($translations[$locale]) !== '') {
                return $translations[$locale];
            }
        }

        return '';
    }
}
