<?php

namespace App\Http\Requests\Backend\Content;

use App\Http\Requests\Backend\BaseRequest;
use Illuminate\Validation\Rule;

class ContentDocumentRequest extends BaseRequest
{
    public function rules(): array
    {
        $action = $this->route()->getActionMethod();
        if ($action === 'list') {
            return [
                'q' => ['nullable', 'string', 'max:128'],
                'type' => ['nullable', Rule::in(['development', 'usage'])],
                'category_id' => ['nullable', 'integer', 'exists:content_document_categories,id'],
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
            'category_id' => ['required', 'integer', 'exists:content_document_categories,id'],
            'type' => ['required', Rule::in(['development', 'usage'])],
            'slug' => ['required', 'string', 'max:128', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('content_documents', 'slug')->where(fn ($query) => $query->where('type', $this->input('type')))->ignore($this->route('id'))],
            ...$this->localizedRules('title_i18n', 256),
            ...$this->localizedRules('summary_i18n', 2048, false),
            ...$this->localizedRules('content_i18n', 65536),
            'published_at' => ['nullable', 'date'],
            'sort' => ['required', 'integer', 'min:0', 'max:99999'],
            'active' => ['required', 'boolean'],
        ];
    }
}
