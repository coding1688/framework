<?php

namespace App\Http\Requests\Backend\System;

use App\Http\Requests\Backend\BaseRequest;
use Illuminate\Validation\Rule;

class SystemSettingGroupRequest extends BaseRequest
{
    public function rules(): array
    {
        if (in_array($this->route()->getActionMethod(), ['list', 'options'], true)) {
            return [];
        }

        return [
            'parent_id' => ['nullable', 'integer', 'exists:system_setting_groups,id'],
            'code' => ['required', 'string', 'max:64', 'regex:/^[a-z][a-zA-Z0-9_]*$/', Rule::unique('system_setting_groups', 'code')->ignore($this->route('id'))],
            ...$this->localizedRules('name_i18n', 128),
            ...$this->localizedRules('description_i18n', 512, false),
            'active' => ['required', 'boolean'], 'sort' => ['required', 'integer', 'min:0', 'max:99999'],
        ];
    }
}
