<?php

namespace App\Services\Backend\Common;

use App\Libs\Fields\FieldRegistry;
use App\Libs\Fields\Options\OptionContext;
use App\Libs\Settings\MailConfiguration;
use App\Models\System\SystemSettingDefinition;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class SettingFields
{
    public function __construct(private readonly FieldRegistry $fields) {}

    public function prepare(array $validated): array
    {
        $field = $this->fields->get($validated['input_type']);
        $validated['multiple'] = $field->supportsMultiple() ? (bool) $validated['multiple'] : $field->defaultMultiple();
        try {
            $validated['validation'] = $this->fields->normalizeValidation($field, $validated['multiple'], $validated['validation'] ?? []);
        } catch (InvalidArgumentException) {
            throw ValidationException::withMessages(['validation' => __('backend/common.invalid')]);
        }
        if (in_array($validated['key'], ['ai.openai.api_key', 'mail.smtp.password'], true) && ! $validated['secret']) {
            throw ValidationException::withMessages(['secret' => __('backend/system/settings.secret_rule')]);
        }
        if ($validated['secret'] && ($validated['input_type'] !== 'text' || $validated['multiple'] || ! empty($validated['default_value']))) {
            throw ValidationException::withMessages(['secret' => __('backend/system/settings.secret_rule')]);
        }
        $validated['option_source_type'] ??= 'manual';
        $validated['option_source_key'] ??= null;
        if ($field->usesOptions($validated['multiple'])) {
            if ($validated['option_source_type'] === 'manual') {
                $validated['option_source_key'] = null;
                if (empty($validated['options_i18n']['en'])) {
                    throw ValidationException::withMessages(['options_i18n.en' => __('validation.required', ['attribute' => 'English options'])]);
                }
            } else {
                $validated['options_i18n'] = null;
                if (! is_string($validated['option_source_key']) || ! $this->fields->optionSources()->has($validated['option_source_type'], $validated['option_source_key'])) {
                    throw ValidationException::withMessages(['option_source_key' => __('backend/common.invalid')]);
                }
            }
            $this->options(new SystemSettingDefinition($validated), 'options_i18n');
        } else {
            $validated['option_source_type'] = 'manual';
            $validated['option_source_key'] = null;
            $validated['options_i18n'] = null;
        }
        if (empty($validated['default_value'])) {
            $validated['default_value'] = null;
        } else {
            $definition = new SystemSettingDefinition($validated);
            $validated['default_value'] = $this->filter($definition, $validated['default_value'], 'default_value');
        }

        return $validated;
    }

    public function filter(SystemSettingDefinition $definition, array $value, string $field): array
    {
        if (str_starts_with($definition->key, 'mail.') && ! MailConfiguration::validValue($definition->key, $value['value_string'] ?? $value['value_number'] ?? $value['value_boolean'] ?? null)) {
            throw ValidationException::withMessages([$field => __('backend/common.invalid')]);
        }
        if ($definition->input_type === 'text' && ! $definition->secret && ($definition->validation['min_length'] ?? 0) === 0
            && array_key_exists('value_string', $value) && trim((string) $value['value_string']) === '') {
            return ['value_string' => ''];
        }
        $validator = $this->fields->get($definition->input_type)->validator($definition->multiple);
        $options = $this->options($definition, $field);
        if (! $validator->validate($value, $options, $definition->validation ?? [])) {
            throw ValidationException::withMessages([$field => __('backend/common.invalid')]);
        }

        return $validator->filter($value, $options, $definition->validation ?? []);
    }

    /** @return array<string, string> */
    private function options(SystemSettingDefinition $definition, string $field): array
    {
        if (! $this->fields->get($definition->input_type)->usesOptions($definition->multiple)) {
            return [];
        }
        try {
            return $this->fields->optionSources()->resolve(
                $definition->option_source_type ?? 'manual', $definition->option_source_key,
                $definition->options_i18n ?? [], new OptionContext('en', 'backend', Auth::guard('backend')->id() === null ? null : (int) Auth::guard('backend')->id()),
            );
        } catch (InvalidArgumentException) {
            throw ValidationException::withMessages([$field => __('backend/common.invalid')]);
        }
    }
}
