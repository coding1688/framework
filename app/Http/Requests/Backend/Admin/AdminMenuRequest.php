<?php

namespace App\Http\Requests\Backend\Admin;

use App\Http\Requests\Backend\BaseRequest;
use Illuminate\Validation\Rule;

class AdminMenuRequest extends BaseRequest
{
    public function rules(): array
    {
        if ($this->route()->getActionMethod() === 'sync') {
            return ['permission' => ['nullable', 'string', 'max:128', Rule::exists('admin_menus', 'permission')->where('type', 'page')]];
        }

        $id = $this->route('id');
        $rules = [
            'active' => ['required', 'boolean'],
            'name' => ['prohibited'],
            'description' => ['prohibited'],
            ...$this->localizedRules('name_i18n', 128),
            'parent_id' => ['nullable', 'integer', 'exists:admin_menus,id'], 'sort' => ['required', 'integer', 'min:0', 'max:99999'],
        ];

        return $rules + [
            'type' => ['required', Rule::in(['directory', 'page', 'button'])],
            'permission' => ['required', 'string', 'max:128', 'regex:/^[a-z][a-zA-Z0-9_.-]+$/', Rule::unique('admin_menus')->ignore($id)],
            'path' => [Rule::requiredIf($this->input('type') === 'page'), Rule::prohibitedIf($this->input('type') === 'directory'), 'nullable', 'string', 'max:256', 'regex:#^/(?!/)[a-zA-Z0-9/_-]*$#'],
            'icon' => ['nullable', 'string', 'max:64', 'regex:/^(?:[A-Z][A-Za-z0-9]*|(?:ri-)?[a-z0-9]+(?:-[a-z0-9]+)+)$/'],
            'component' => ['nullable', 'string', 'max:128', 'regex:#^[a-zA-Z0-9]+(?:/[a-zA-Z0-9]+)*$#'],
            'is_system' => ['prohibited'],
            'navigation_target_id' => ['prohibited'],
            'context' => ['prohibited'],
        ];
    }

    public function attributes(): array
    {
        return $this->localizedAttributes(['name_i18n' => 'backend/common.name', 'description_i18n' => 'backend/common.description']);
    }
}
