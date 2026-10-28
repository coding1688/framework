<?php

namespace App\Models\Concerns;

trait HasLocalizedAttributes
{
    protected function localizedValue(string $field, ?array $translations = null): string
    {
        $translations ??= $this->getAttribute($field.'_i18n') ?? [];
        $locales = array_unique([app()->getLocale(), 'en', config('app.fallback_locale'), 'zh_CN', ...array_keys($translations)]);
        foreach ($locales as $locale) {
            $value = $translations[$locale] ?? null;
            if (is_string($value) && trim($value) !== '') {
                return $value;
            }
        }

        return '';
    }
}
