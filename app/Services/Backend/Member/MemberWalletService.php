<?php

namespace App\Services\Backend\Member;

use App\Models\Member\MemberWallet;
use App\Models\Member\MemberWalletTransaction;
use App\Models\Member\User;
use App\Services\Backend\BaseService;
use App\Services\Backend\Common\Audit;
use App\Services\Member\UserLogService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MemberWalletService extends BaseService
{
    public function __construct(private readonly \App\Services\Member\MemberWalletService $wallets, private readonly UserLogService $userLogs) {}

    public function list(array $validated): LengthAwarePaginator
    {
        $this->authorize('member.wallet.list');
        $query = MemberWallet::with(['user.profile'])->latest('id');
        if ($q = $validated['q'] ?? null) {
            $query->whereHas('user', fn ($user) => $user->where('username', 'like', '%'.$q.'%')->orWhere('email', 'like', '%'.$q.'%')->orWhereHas('profile', fn ($profile) => $profile->where('nickname', 'like', '%'.$q.'%')));
        }
        if ($currency = $validated['currency'] ?? null) {
            $query->where('currency', $currency);
        }
        if (isset($validated['is_test'])) {
            $query->whereHas('user', fn ($user) => $user->where('is_test', $validated['is_test']));
        }

        return $query->paginate(20, ['*'], 'page', (int) ($validated['page'] ?? 1));
    }

    public function history(int $id, array $validated): LengthAwarePaginator
    {
        $this->authorize('member.wallet.history');

        return $this->wallets->history(User::findOrFail($id), $validated);
    }

    public function statistics(int $id, array $validated): LengthAwarePaginator
    {
        $this->authorize('member.wallet.statistics');

        return $this->wallets->statistics(User::findOrFail($id), $validated);
    }

    public function options(array $validated): array
    {
        $this->authorize('member.wallet.options');
        $query = User::with('profile')->orderByDesc('id')->limit(50);
        if ($q = $validated['q'] ?? null) {
            $query->where(fn ($user) => $user->where('username', 'like', '%'.$q.'%')->orWhere('email', 'like', '%'.$q.'%')->orWhereHas('profile', fn ($profile) => $profile->where('nickname', 'like', '%'.$q.'%')));
        }

        return [
            'main_currency' => $this->wallets->mainCurrency(),
            'currencies' => array_keys(config('member.wallet.currencies')),
            'users' => $query->get()->map(fn (User $user): array => ['id' => $user->id, 'username' => $user->username, 'name' => $user->name, 'email' => $user->email, 'is_test' => $user->is_test])->values()->all(),
        ];
    }

    public function credit(int $id, array $validated): MemberWalletTransaction
    {
        $this->authorize('member.wallet.credit');

        return $this->change($id, 'credit', $validated);
    }

    public function debit(int $id, array $validated): MemberWalletTransaction
    {
        $this->authorize('member.wallet.debit');

        return $this->change($id, 'debit', $validated);
    }

    private function change(int $id, string $direction, array $validated): MemberWalletTransaction
    {
        return DB::transaction(function () use ($id, $direction, $validated): MemberWalletTransaction {
            $user = User::findOrFail($id);
            $admin = Auth::guard('backend')->user();
            $transaction = $this->wallets->{$direction}($user, $validated, 'admin', $admin?->name ?? 'system');
            if ($transaction->wasRecentlyCreated) {
                $context = ['currency' => $transaction->currency, 'amount' => $transaction->amount, 'type' => $transaction->type, 'reference' => $transaction->reference];
                Audit::record('member.wallet.'.$direction, (string) $user->id, $context);
                $this->userLogs->record($user, 'wallet.'.$direction, 'admin', $admin?->name ?? 'system', $context);
            }

            return $transaction;
        });
    }
}
