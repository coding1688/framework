<?php

namespace App\Http\Requests\Backend;

class NotificationRequest extends BaseRequest
{
    public function rules(): array
    {
        return match ($this->route()->getActionMethod()) {
            'preferences' => ['preferences' => ['required', 'array', 'max:64'], 'preferences.*' => ['array', 'max:2'], 'preferences.*.*' => ['string', 'in:database,mail']],
            default => ['page' => ['nullable', 'integer', 'min:1']],
        };
    }
}
