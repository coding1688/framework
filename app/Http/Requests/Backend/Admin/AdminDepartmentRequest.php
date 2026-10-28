<?php

namespace App\Http\Requests\Backend\Admin;

use App\Http\Requests\Backend\BaseRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AdminDepartmentRequest extends BaseRequest
{
    protected function prepareForValidation(): void
    {
        if (is_array($this->input('leader'))) {
            $leader = $this->input('leader');
            foreach (['email', 'username'] as $field) {
                if (isset($leader[$field]) && is_string($leader[$field])) {
                    $leader[$field] = strtolower(trim($leader[$field]));
                }
            }
            $this->merge(['leader' => $leader]);
        }
    }

    public function rules(): array
    {
        if ($this->route()->getActionMethod() === 'status') {
            return ['active' => ['required', 'boolean']];
        }
        if ($this->route()->getActionMethod() === 'leaders') {
            return ['leader_ids' => ['required', 'array', 'min:1', 'max:2'], 'leader_ids.*' => ['required', 'integer', 'distinct', 'exists:admin_users,id']];
        }
        if ($this->route()->getActionMethod() === 'permissions') {
            return [
                'menu_ids' => ['present', 'array', 'max:500'],
                'menu_ids.*' => ['integer', 'distinct', 'exists:admin_menus,id'],
            ];
        }

        $locales = $this->localeKeys();
        $rules = [
            'active' => ['required', 'boolean'],
            'name' => ['prohibited'],
            'description' => ['prohibited'],
            ...$this->localizedRules('name_i18n', 128),
            'parent_id' => ['nullable', 'integer', 'exists:admin_departments,id'], 'sort' => ['required', 'integer', 'min:0', 'max:99999'],
        ];

        $withoutLeader = ($this->route()->getActionMethod() === 'create') && $this->has('create_leader') && ! $this->boolean('create_leader');

        return $rules + [
            'create_leader' => [($this->route()->getActionMethod() === 'create') ? 'sometimes' : 'prohibited', 'boolean'],
            'code' => ['prohibited'],
            'menu_ids' => ['present', 'array', 'max:500'],
            'menu_ids.*' => ['integer', 'distinct', 'exists:admin_menus,id'],
            'leader_ids' => [$withoutLeader ? 'prohibited' : 'required_without:leader', 'array', $withoutLeader ? 'min:0' : 'min:1', $this->filled('leader') ? 'max:1' : 'max:2'],
            'leader_ids.*' => ['integer', 'distinct', 'exists:admin_users,id'],
            'leader' => [($this->route()->getActionMethod() === 'create') && ! $withoutLeader ? 'required_without:leader_ids' : 'prohibited', 'array:name,username,email,password,locale'],
            'leader.name' => ['required_with:leader', 'string', 'max:128'],
            'leader.username' => ['nullable', 'string', 'min:3', 'max:64', 'regex:/^[a-z0-9_.-]+$/', 'unique:admin_users,username'],
            'leader.email' => ['required_with:leader', 'email', 'max:256', 'unique:admin_users,email'],
            'leader.password' => ['required_with:leader', Password::min(12)->mixedCase()->numbers(), 'max:128'],
            'leader.locale' => ['required_with:leader', Rule::in($locales)],
        ];
    }

    public function attributes(): array
    {
        $attributes = $this->localizedAttributes(['name_i18n' => 'backend/common.name', 'description_i18n' => 'backend/common.description'])
            + ['leader_ids' => __('backend/department.leaders'), 'leader' => __('backend/department.leaders')];
        foreach (['name', 'username', 'email', 'password', 'locale'] as $field) {
            $attributes['leader.'.$field] = __('backend.'.$field);
        }

        return $attributes;
    }

    public function messages(): array
    {
        return [
            'leader_ids.required_without' => __('backend/department.leader_count'),
            'leader_ids.min' => __('backend/department.leader_count'),
            'leader_ids.max' => __('backend/department.leader_count'),
            'leader.required_without' => __('backend/department.leader_count'),
            'leader.*.required_with' => __('validation.required'),
        ];
    }
}
