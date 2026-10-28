<?php

namespace App\Http\Requests\Backend;

use Illuminate\Foundation\Http\FormRequest;

abstract class BaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return list<string> */
    protected function localeKeys(): array
    {
        return array_keys(config('platform.locales'));
    }

    /** @return array<string, array<int, string>> */
    protected function localizedRules(string $field, int $maxLength, bool $required = true): array
    {
        $rules = [$field => [$required ? 'required' : 'nullable', 'array:'.implode(',', $this->localeKeys())]];
        foreach ($this->localeKeys() as $locale) {
            $rules[$field.'.'.$locale] = [$required && $locale === 'en' ? 'required' : 'nullable', 'string', 'max:'.$maxLength];
        }

        return $rules;
    }

    /** @param array<string, string> $fields @return array<string, string> */
    protected function localizedAttributes(array $fields): array
    {
        $attributes = [];
        foreach ($fields as $field => $label) {
            $attributes[$field] = __($label);
            foreach (config('platform.locales') as $locale => $language) {
                $attributes[$field.'.'.$locale] = __($label).' ('.$language.')';
            }
        }

        return $attributes;
    }
}
