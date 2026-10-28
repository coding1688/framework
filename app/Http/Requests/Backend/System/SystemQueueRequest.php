<?php

namespace App\Http\Requests\Backend\System;

use App\Http\Requests\Backend\BaseRequest;

class SystemQueueRequest extends BaseRequest
{
    public function rules(): array
    {
        return match ($this->route()->getActionMethod()) {
            'retryBatch' => ['ids' => ['required', 'array', 'min:1', 'max:16'], 'ids.*' => ['required', 'uuid', 'distinct']],
            default => ['queue' => ['nullable', 'string', 'max:128'], 'page' => ['nullable', 'integer', 'min:1']],
        };
    }
}
