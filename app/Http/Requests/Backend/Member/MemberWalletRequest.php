<?php

namespace App\Http\Requests\Backend\Member;

use App\Http\Requests\Backend\BaseRequest;
use Illuminate\Validation\Rule;

class MemberWalletRequest extends BaseRequest
{
    public function rules(): array
    {
        return match ($this->route()->getActionMethod()) {
            'list' => [
                'q' => ['nullable', 'string', 'max:128'], 'currency' => ['nullable', Rule::in(array_keys(config('member.wallet.currencies')))],
                'is_test' => ['nullable', 'boolean'], 'page' => ['nullable', 'integer', 'min:1'],
            ],
            'options' => ['q' => ['nullable', 'string', 'max:128']],
            'history', 'statistics' => $this->historyRules(),
            'credit', 'debit' => [
                'currency' => ['required', Rule::in(array_keys(config('member.wallet.currencies')))],
                'amount' => ['required', 'string', 'max:64'],
                'type' => ['required', 'string', 'max:32'],
                'reference' => ['required', 'string', 'max:128'],
                'remark' => ['nullable', 'string', 'max:512'],
            ],
            default => [],
        };
    }

    private function historyRules(): array
    {
        return [
            'currency' => ['nullable', Rule::in(array_keys(config('member.wallet.currencies')))],
            'direction' => ['nullable', Rule::in(['credit', 'debit'])], 'type' => ['nullable', 'string', 'max:32'],
            'date_from' => ['nullable', 'date'], 'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
