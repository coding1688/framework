<?php

namespace App\Http\Requests\Backend\Content;

use App\Http\Requests\Backend\BaseRequest;
use Illuminate\Validation\Rule;

class ContentDocumentCategoryRequest extends BaseRequest
{
    public function rules(): array
    {
        $action = $this->route()->getActionMethod();
        if (in_array($action, ['list', 'options'], true)) {
            return [
                'q' => ['nullable', 'string', 'max:128'],
                'type' => ['nullable', Rule::in(['development', 'usage'])],
                'active' => ['nullable', 'boolean'],
            ];
        }
        if ($action === 'status') {
            return ['active' => ['required', 'boolean']];
        }
        if (! in_array($action, ['create', 'update'], true)) {
            return [];
        }

        return [
            'type' => ['required', Rule::in(['development', 'usage'])],
            'parent_id' => ['nullable', 'integer', 'exists:content_document_categories,id'],
            'slug' => ['required', 'string', 'max:128', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('content_document_categories', 'slug')->where(fn ($query) => $query->where('type', $this->input('type')))->ignore($this->route('id'))],
            ...$this->localizedRules('name_i18n', 256),
            ...$this->localizedRules('description_i18n', 2048, false),
            'sort' => ['required', 'integer', 'min:0', 'max:99999'],
            'active' => ['required', 'boolean'],
        ];
    }
}
