<?php

namespace App\Http\Requests\Backend\Member;

use App\Http\Requests\Backend\BaseRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserRequest extends BaseRequest
{
    public function rules(): array
    {
        return match ($this->route()->getActionMethod()) {
            'list' => [
                'q' => ['nullable', 'string', 'max:128'], 'active' => ['nullable', 'boolean'],
                'is_test' => ['nullable', 'boolean'], 'verified' => ['nullable', 'boolean'], 'date_from' => ['nullable', 'date'],
                'vip_level_id' => ['nullable', 'integer', 'exists:member_vip_levels,id'],
                'date_to' => ['nullable', 'date', 'after_or_equal:date_from'], 'page' => ['nullable', 'integer', 'min:1'],
            ],
            'update' => [
                'username' => ['required', 'string', 'min:3', 'max:32', 'regex:/^[A-Za-z0-9_.-]+$/', Rule::unique('users', 'username')->ignore($this->route('id'))],
                'name' => ['required', 'string', 'max:128'],
                'email' => ['required', 'email', 'max:256', Rule::unique('users', 'email')->ignore($this->route('id'))],
                'is_test' => ['sometimes', 'boolean'],
                'remark' => ['sometimes', 'nullable', 'string', 'max:512'],
                'locale' => ['sometimes', 'required', Rule::in(array_keys(config('platform.locales')))],
                'default_currency' => ['sometimes', 'required', Rule::in(array_keys(config('member.wallet.currencies')))],
            ],
            'vip' => [
                'vip_level_id' => ['nullable', 'integer', 'exists:member_vip_levels,id'],
                'started_at' => ['nullable', 'date'], 'expires_at' => ['nullable', 'date', 'after_or_equal:started_at'],
                'reason' => ['nullable', 'string', 'max:512'],
            ],
            'vipHistory' => ['page' => ['nullable', 'integer', 'min:1']],
            'logs' => [
                'action' => ['nullable', 'string', 'max:128'], 'date_from' => ['nullable', 'date'],
                'date_to' => ['nullable', 'date', 'after_or_equal:date_from'], 'page' => ['nullable', 'integer', 'min:1'],
            ],
            'status' => [
                'active' => ['required', 'boolean'],
                'reason' => [Rule::requiredIf(fn (): bool => ! $this->boolean('active')), 'nullable', 'string', 'max:512'],
            ],
            'restrictions' => [
                'user_ids' => ['required', 'array', 'min:1', 'max:100'],
                'user_ids.*' => ['required', 'integer', 'distinct', 'exists:users,id'],
                'type' => ['required', 'string', Rule::in(array_keys(config('member.restrictions')))],
                'restricted' => ['required', 'boolean'],
                'reason' => [Rule::requiredIf(fn (): bool => $this->boolean('restricted')), 'nullable', 'string', 'max:512'],
            ],
            'password' => ['password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers(), 'max:128']],
            default => [],
        };
    }
}
