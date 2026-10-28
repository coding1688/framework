<?php

namespace App\Http\Requests\Backend\Admin;

use App\Http\Requests\Backend\BaseRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AdminUserRequest extends BaseRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->username)) {
            $this->merge(['username' => strtolower(trim($this->username)) ?: null]);
        }
        if (is_string($this->email)) {
            $this->merge(['email' => strtolower($this->email)]);
        }
    }

    public function rules(): array
    {
        $action = $this->route()->getActionMethod();
        if ($action === 'list') {
            return ['q' => ['nullable', 'string', 'max:128'], 'active' => ['nullable', 'boolean'], 'department_id' => ['nullable', 'integer'], 'page' => ['nullable', 'integer', 'min:1']];
        }
        if ($action === 'status') {
            return ['active' => ['required', 'boolean']];
        }
        if ($action === 'password') {
            return ['password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers(), 'max:128']];
        }

        return [
            'name' => ['required', 'string', 'max:128'],
            'username' => ['nullable', 'string', 'min:3', 'max:64', 'regex:/^[a-z0-9_.-]+$/', Rule::unique('admin_users')->ignore($this->route('id'))],
            'email' => ['required', 'email', 'max:256', Rule::unique('admin_users')->ignore($this->route('id'))],
            'password' => [($this->route()->getActionMethod() === 'create') ? 'required' : 'prohibited', Password::min(12)->mixedCase()->numbers(), 'max:128'],
            'department_id' => ['nullable', 'integer', 'exists:admin_departments,id'],
            'locale' => ['sometimes', Rule::in($this->localeKeys())],
            'active' => ['sometimes', 'boolean'],
            'is_leader' => ['prohibited'],
            'role_ids' => ['prohibited'],
            'is_super' => ['prohibited'], 'auth_version' => ['prohibited'],
        ];
    }

    public function attributes(): array
    {
        return ['is_leader' => __('backend/department.is_leader'), 'username' => __('backend/admin.username')];
    }
}
