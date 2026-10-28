<?php

namespace App\Http\Requests\Backend\System;

use App\Http\Requests\Backend\BaseRequest;
use Illuminate\Validation\Rule;

class SystemNotificationTemplateRequest extends BaseRequest
{
    public function rules(): array
    {
        if ($this->route()->getActionMethod() === 'list') {
            return ['q' => ['nullable', 'string', 'max:128'], 'active' => ['nullable', 'boolean'], 'page' => ['nullable', 'integer', 'min:1']];
        }

        return [
            'code' => ['required', 'string', 'max:128', 'regex:/^[a-z][a-z0-9_.-]+$/', Rule::unique('system_notification_templates', 'code')->ignore($this->route('id'))],
            ...$this->localizedRules('name_i18n', 128),
            ...$this->localizedRules('subject_i18n', 256),
            ...$this->localizedRules('content_i18n', 4096),
            'channels' => ['required', 'array', 'min:1', 'max:2'],
            'channels.*' => ['required', 'string', 'distinct', Rule::in(['database', 'mail'])],
            'active' => ['required', 'boolean'],
        ];
    }
}
