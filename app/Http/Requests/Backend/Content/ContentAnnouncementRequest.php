<?php

namespace App\Http\Requests\Backend\Content;

use App\Http\Requests\Backend\BaseRequest;

class ContentAnnouncementRequest extends BaseRequest
{
    public function rules(): array
    {
        $action = $this->route()->getActionMethod();
        if ($action === 'list') {
            return [
                'q' => ['nullable', 'string', 'max:128'],
                'active' => ['nullable', 'boolean'],
                'page' => ['nullable', 'integer', 'min:1'],
            ];
        }
        if ($action === 'status') {
            return ['active' => ['required', 'boolean']];
        }
        if (! in_array($action, ['create', 'update'], true)) {
            return [];
        }

        return [
            ...$this->localizedRules('title_i18n', 256),
            ...$this->localizedRules('content_i18n', 65536),
            'published_at' => ['nullable', 'date'],
            'sort' => ['required', 'integer', 'min:0', 'max:99999'],
            'active' => ['required', 'boolean'],
        ];
    }
}
