<?php

namespace App\Http\Requests\Backend\System;

use App\Http\Requests\Backend\BaseRequest;

class SystemSettingValueRequest extends BaseRequest
{
    public function rules(): array
    {
        if ($this->route()->getActionMethod() === 'status') {
            return ['active' => ['required', 'boolean']];
        }
        $rules = ['group_id' => ['required', 'integer', 'exists:system_setting_groups,id']];
        if ($this->route()->getActionMethod() !== 'update') {
            return $rules;
        }
        $rules += [
            'values' => ['required', 'array', 'min:1', 'max:256'],
            'values.*.definition_id' => ['required', 'integer', 'distinct', 'exists:system_setting_definitions,id'],
            'values.*.clear' => ['sometimes', 'boolean'],
            'values.*.value' => ['nullable', 'array:value_string,value_number,value_boolean,value_json,value_i18n'],
            'values.*.value.value_string' => ['nullable', 'string', 'max:512'],
            'values.*.value.value_number' => ['nullable', 'numeric'],
            'values.*.value.value_boolean' => ['nullable', 'boolean'],
            'values.*.value.value_json' => ['nullable', 'array', 'max:64'],
            'values.*.value.value_json.*' => ['string', 'max:128', 'distinct'],
            'values.*.value.value_i18n' => ['nullable', 'array:'.implode(',', $this->localeKeys())],
        ];
        foreach ($this->localeKeys() as $locale) {
            $rules['values.*.value.value_i18n.'.$locale] = ['nullable', 'string', 'max:512'];
        }

        return $rules;
    }
}
