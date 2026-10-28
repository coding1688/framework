<?php

namespace App\Http\Requests\Backend\Content;

use App\Http\Requests\Backend\BaseRequest;
use App\Libs\File\UploadConfiguration;
use Illuminate\Validation\Rule;

class ContentCarouselRequest extends BaseRequest
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
        if ($action === 'upload') {
            return ['image' => app(UploadConfiguration::class)->fileRules(['jpg', 'jpeg', 'png', 'gif', 'webp'])];
        }
        if (! in_array($action, ['create', 'update'], true)) {
            return [];
        }

        return [
            ...$this->localizedRules('title_i18n', 256),
            'image_id' => ['required', 'integer', Rule::exists('files', 'id')->where(fn ($query) => $query->where('visibility', 'public')->where('mime_type', 'like', 'image/%'))],
            'link_url' => ['nullable', 'string', 'max:512', 'starts_with:http://,https://,/'],
            'sort' => ['required', 'integer', 'min:0', 'max:99999'],
            'active' => ['required', 'boolean'],
        ];
    }
}
