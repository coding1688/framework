<?php

namespace App\Services\Member;

use App\Libs\Settings\SettingStore;
use App\Models\Member\MemberWalletDailyStat;
use App\Models\Member\MemberWalletTransaction;
use App\Models\Member\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MemberWalletService
{
    public function __construct(private readonly SettingStore $settings) {}

    public function mainCurrency(): string
    {
        $currency = $this->settings->get('member.wallet.main_currency', config('member.wallet.main_currency'));
        MemberWalletAmount::precision($currency);

        return $currency;
    }

    public function list(User $user, array $validated = []): LengthAwarePaginator
    {
        $query = $user->wallets()->latest('id');
        if ($currency = $validated['currency'] ?? null) {
            $query->where('currency', $currency);
        }

        return $query->paginate(20, ['*'], 'page', (int) ($validated['page'] ?? 1));
    }

    public function history(User $user, array $validated): LengthAwarePaginator
    {
        $query = MemberWalletTransaction::where('user_id', $user->id)->latest('id');
        if ($currency = $validated['currency'] ?? null) {
            $query->where('currency', $currency);
        }
        if ($direction = $validated['direction'] ?? null) {
            $query->where('direction', $direction);
        }
        if ($type = $validated['type'] ?? null) {
            $query->where('type', $type);
        }
        if ($from = $validated['date_from'] ?? null) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $validated['date_to'] ?? null) {
            $query->whereDate('created_at', '<=', $to);
        }

        return $query->paginate(20, ['*'], 'page', (int) ($validated['page'] ?? 1));
    }

    public function statistics(User $user, array $validated): LengthAwarePaginator
    {
        $query = MemberWalletDailyStat::where('user_id', $user->id);
        if ($currency = $validated['currency'] ?? null) {
            $query->where('currency', $currency);
        }
        if ($type = $validated['type'] ?? null) {
            $query->where('type', $type);
        }
        if ($from = $validated['date_from'] ?? null) {
            $query->where('stat_date', '>=', $from);
        }
        if ($to = $validated['date_to'] ?? null) {
            $query->where('stat_date', '<=', $to);
        }

        return $query->orderByDesc('stat_date')->orderBy('currency')->orderBy('type')
            ->paginate(20, ['*'], 'page', (int) ($validated['page'] ?? 1));
    }

    public function credit(User $user, array $validated, string $actorType, string $actor): MemberWalletTransaction
    {
        return $this->change($user, 'credit', $validated, $actorType, $actor);
    }

    public function debit(User $user, array $validated, string $actorType, string $actor): MemberWalletTransaction
    {
        return $this->change($user, 'debit', $validated, $actorType, $actor);
    }

    private function change(User $user, string $direction, array $validated, string $actorType, string $actor): MemberWalletTransaction
    {
        $currency = $validated['currency'];
        $amount = MemberWalletAmount::toMinor($validated['amount'], $currency);

        return DB::transaction(function () use ($user, $direction, $validated, $currency, $amount, $actorType, $actor): MemberWalletTransaction {
            $existing = MemberWalletTransaction::where('reference', $validated['reference'])->first();
            if ($existing) {
                if ($existing->user_id !== $user->id || $existing->currency !== $currency || $existing->direction !== $direction || $existing->type !== $validated['type'] || $existing->amount_minor !== $amount) {
                    throw ValidationException::withMessages(['reference' => [__('member/wallet.reference_mismatch')]]);
                }

                return $existing;
            }
            $user = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $wallet = $user->wallets()->where('currency', $currency)->lockForUpdate()->first();
            if (! $wallet) {
                $wallet = $user->wallets()->create(['currency' => $currency, 'balance_minor' => 0]);
            }
            $before = $wallet->balance_minor;
            if ($direction === 'credit' && $before > PHP_INT_MAX - $amount) {
                throw ValidationException::withMessages(['amount' => [__('validation.regex', ['attribute' => 'amount'])]]);
            }
            if ($direction === 'debit' && $before < $amount) {
                throw ValidationException::withMessages(['amount' => [__('member/wallet.insufficient_balance')]]);
            }
            $after = $direction === 'credit' ? $before + $amount : $before - $amount;
            $wallet->forceFill(['balance_minor' => $after])->save();

            $transaction = $wallet->transactions()->create([
                'user_id' => $user->id,
                'direction' => $direction,
                'currency' => $currency,
                'type' => $validated['type'],
                'amount_minor' => $amount,
                'balance_before_minor' => $before,
                'balance_after_minor' => $after,
                'reference' => $validated['reference'],
                'remark' => $validated['remark'] ?? null,
                'actor_type' => $actorType,
                'actor' => mb_substr($actor, 0, 128),
                'created_at' => now(),
            ]);
            $this->recordDailyStat($transaction);

            return $transaction;
        });
    }

    private function recordDailyStat(MemberWalletTransaction $transaction): void
    {
        $stat = MemberWalletDailyStat::firstOrCreate([
            'user_id' => $transaction->user_id,
            'stat_date' => $transaction->created_at->toDateString(),
            'currency' => $transaction->currency,
            'type' => $transaction->type,
        ]);
        $amountField = $transaction->direction.'_amount_minor';
        $countField = $transaction->direction.'_count';
        $stat->forceFill([
            $amountField => $stat->{$amountField} + $transaction->amount_minor,
            $countField => $stat->{$countField} + 1,
        ])->save();
    }
}
