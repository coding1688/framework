<?php

namespace App\Http\Requests\Backend;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AuthRequest extends BaseRequest
{
    public function rules(): array
    {
        return match ($this->route()->getActionMethod()) {
            'login' => ['email' => ['required', 'email', 'max:256'], 'password' => ['required', 'string', 'max:128']],
            'forgotPassword' => ['email' => ['required', 'email', 'max:256']],
            'resetPassword' => [
                'token' => ['required', 'string', 'max:256'], 'email' => ['required', 'email', 'max:256'],
                'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers(), 'max:128'],
            ],
            'securityPassword' => [
                'current_password' => ['nullable', 'string', 'max:128'],
                'current_security_password' => ['nullable', 'string', 'max:128'],
                'security_password' => ['required', 'confirmed', Password::min(8)->letters()->numbers(), 'max:128'],
                'security_password_confirmation' => ['required', 'string', 'max:128'],
            ],
            'password' => ['current_password' => ['required', 'string'], 'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers(), 'max:128']],
            'preferences' => [
                'mode' => ['required', Rule::in(config('platform.backend_ui.theme.options.mode'))],
                'preset' => ['required', Rule::in(config('platform.backend_ui.theme.options.preset'))],
                'density' => ['required', Rule::in(config('platform.backend_ui.theme.options.density'))],
                'radius' => ['required', Rule::in(config('platform.backend_ui.theme.options.radius'))],
                'button_size' => ['required', Rule::in(config('platform.backend_ui.theme.options.button_size'))],
            ],
            default => [],
        };
    }
}
