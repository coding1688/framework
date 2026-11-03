<?php

namespace App\Http\Requests\Backend\System;

use App\Http\Requests\Backend\BaseRequest;
use App\Libs\Fields\FieldRegistry;
use Illuminate\Validation\Rule;

class SystemSettingDefinitionRequest extends BaseRequest
{
    public function rules(): array
    {
        if ($this->route()->getActionMethod() === 'list') {
            return ['q' => ['nullable', 'string', 'max:128'], 'group_id' => ['nullable', 'integer', 'exists:system_setting_groups,id'], 'active' => ['nullable', 'boolean'], 'page' => ['nullable', 'integer', 'min:1']];
        }
        if ($this->route()->getActionMethod() === 'optionValues') {
            return ['option_source_type' => ['required', Rule::in(['constant', 'database'])], 'option_source_key' => ['required', 'string', 'max:64']];
        }
        $locales = implode(',', $this->localeKeys());
        $rules = [
            'group_id' => ['required', 'integer', 'exists:system_setting_groups,id'],
            'key' => ['required', 'string', 'max:128', 'regex:/^[a-z][a-zA-Z0-9]*(?:\.[a-z][a-zA-Z0-9_]*)*$/', Rule::unique('system_setting_definitions', 'key')->ignore($this->route('id'))],
            'input_type' => ['required', Rule::in(app(FieldRegistry::class)->keys())],
            'multiple' => ['required', 'boolean'],
            'option_source_type' => ['sometimes', 'required', Rule::in(['manual', 'constant', 'database'])],
            'option_source_key' => ['nullable', 'string', 'max:64'],
            'depends_on' => ['nullable', 'string', 'max:128', 'regex:/^[a-z][a-zA-Z0-9]*(?:\.[a-z][a-zA-Z0-9_]*)*$/', Rule::exists('system_setting_definitions', 'key')],
            'depends_value' => ['nullable', 'string', 'max:128'],
            'validation' => ['nullable', 'array', 'max:16'],
            ...$this->localizedRules('name_i18n', 128),
            ...$this->localizedRules('description_i18n', 512, false),
            'options_i18n' => ['nullable', 'array:'.$locales],
            'default_value' => ['nullable', 'array:value_string,value_number,value_boolean,value_json,value_i18n'],
            'default_value.value_string' => ['nullable', 'string', 'max:512'],
            'default_value.value_number' => ['nullable', 'numeric'],
            'default_value.value_boolean' => ['nullable', 'boolean'],
            'default_value.value_json' => ['nullable', 'array', 'max:64'],
            'default_value.value_json.*' => ['string', 'max:128', 'distinct'],
            'default_value.value_i18n' => ['nullable', 'array:'.$locales],
            'secret' => ['required', 'boolean'], 'active' => ['required', 'boolean'], 'sort' => ['required', 'integer', 'min:0', 'max:99999'],
        ];
        foreach ($this->localeKeys() as $locale) {
            $rules['options_i18n.'.$locale] = ['nullable', 'array', 'max:64'];
            $rules['options_i18n.'.$locale.'.*'] = ['nullable', 'string', 'max:128'];
            $rules['default_value.value_i18n.'.$locale] = ['nullable', 'string', 'max:512'];
        }

        return $rules;
    }
}
