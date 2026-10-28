<?php

namespace App\Http\Requests\Api;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AuthRequest extends BaseRequest
{
    public function rules(): array
    {
        return match ($this->route()->getActionMethod()) {
            'register' => [
                'username' => ['required', 'string', 'min:3', 'max:32', 'regex:/^[A-Za-z0-9_.-]+$/', 'unique:users,username'],
                'name' => ['nullable', 'string', 'max:128'],
                'email' => ['required', 'email', 'max:256', 'unique:users,email'],
                'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers(), 'max:128'],
            ],
            'login' => [
                'login' => ['required', 'string', 'max:256'],
                'password' => ['required', 'string', 'max:128'],
            ],
            'forgotPassword' => ['email' => ['required', 'email', 'max:256']],
            'resetPassword' => [
                'token' => ['required', 'string', 'max:256'], 'email' => ['required', 'email', 'max:256'],
                'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers(), 'max:128'],
            ],
            'profile' => [
                'username' => ['required', 'string', 'min:3', 'max:32', 'regex:/^[A-Za-z0-9_.-]+$/', Rule::unique('users', 'username')->ignore($this->user()->id)],
                'name' => ['required', 'string', 'max:128'],
            ],
            'password' => [
                'current_password' => ['nullable', 'string', 'max:128'],
                'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers(), 'max:128'],
            ],
            'email' => [
                'current_password' => ['nullable', 'string', 'max:128'],
                'email' => ['required', 'email', 'max:256', Rule::unique('users', 'email')->ignore($this->user()->id)],
            ],
            'avatar' => ['avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120']],
            default => [],
        };
    }
}
