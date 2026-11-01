<?php

namespace App\Http\Requests\Api;

use Illuminate\Validation\Rule;

class MemberWalletRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'currency' => ['nullable', Rule::in(array_keys(config('member.wallet.currencies')))],
            'direction' => ['nullable', Rule::in(['credit', 'debit'])], 'type' => ['nullable', 'string', 'max:32'],
            'date_from' => ['nullable', 'date'], 'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
