<?php

namespace App\Http\Requests\Backend\Member;

use App\Http\Requests\Backend\BaseRequest;
use Illuminate\Validation\Rule;

class MemberVipLevelRequest extends BaseRequest
{
    public function rules(): array
    {
        $action = $this->route()->getActionMethod();
        if ($action === 'list') {
            return ['active' => ['nullable', 'boolean'], 'page' => ['nullable', 'integer', 'min:1']];
        }
        if ($action === 'status') {
            return ['active' => ['required', 'boolean']];
        }
        if (! in_array($action, ['create', 'update'], true)) {
            return [];
        }

        return [
            'level' => ['required', 'integer', 'min:1', 'max:65535', Rule::unique('member_vip_levels', 'level')->ignore($this->route('id'))],
            ...$this->localizedRules('name_i18n', 128),
            ...$this->localizedRules('description_i18n', 512, false),
            ...$this->localizedRules('benefits_i18n', 2048, false),
            'duration_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'active' => ['required', 'boolean'], 'sort' => ['required', 'integer', 'min:0', 'max:99999'],
        ];
    }
}
