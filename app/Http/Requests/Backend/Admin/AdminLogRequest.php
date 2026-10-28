<?php

namespace App\Http\Requests\Backend\Admin;

use App\Http\Requests\Backend\BaseRequest;

class AdminLogRequest extends BaseRequest
{
    public function rules(): array
    {
        return ['q' => ['nullable', 'string', 'max:128'], 'page' => ['nullable', 'integer', 'min:1']];
    }
}
