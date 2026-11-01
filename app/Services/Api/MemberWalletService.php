<?php

namespace App\Services\Api;

use App\Models\Member\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class MemberWalletService extends BaseService
{
    public function __construct(private readonly \App\Services\Member\MemberWalletService $wallets) {}

    public function list(User $user, array $validated): array
    {
        return ['page' => $this->wallets->list($user, $validated), 'main_currency' => $this->wallets->mainCurrency()];
    }

    public function history(User $user, array $validated): LengthAwarePaginator
    {
        return $this->wallets->history($user, $validated);
    }

    public function statistics(User $user, array $validated): LengthAwarePaginator
    {
        return $this->wallets->statistics($user, $validated);
    }
}
