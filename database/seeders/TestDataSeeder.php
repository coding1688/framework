<?php

namespace Database\Seeders;

use App\Models\Member\User;
use App\Services\Member\MemberWalletService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $userCount = max(1, min(100, (int) env('TEST_DATA_USERS', 12)));
        $transactionCount = max(1, min(50, (int) env('TEST_DATA_TRANSACTIONS', 6)));
        $base = Carbon::now();

        try {
            for ($userIndex = 0; $userIndex < $userCount; $userIndex++) {
                $user = $this->createUser($userIndex % 2 === 0);
                $this->createWalletHistory($user, $userIndex, $transactionCount, $base);
            }
        } finally {
            Carbon::setTestNow();
        }
    }

    private function createUser(bool $isTest): User
    {
        $factory = $isTest ? User::factory()->test() : User::factory();

        return $factory->create([
            'last_login_at' => now()->subHours(fake()->numberBetween(1, 720)),
            'last_login_ip' => fake()->ipv4(),
            'last_login_device' => fake()->userAgent(),
            'last_login_device_type' => fake()->randomElement(['desktop', 'mobile', 'tablet']),
        ]);
    }

    private function createWalletHistory(User $user, int $userIndex, int $transactionCount, Carbon $base): void
    {
        $wallets = app(MemberWalletService::class);
        $types = ['deposit', 'bonus', 'commission', 'adjustment'];

        for ($transactionIndex = 0; $transactionIndex < $transactionCount; $transactionIndex++) {
            $isUsdt = $transactionIndex % 3 === 0;
            $currency = $isUsdt ? 'USDT' : 'RMB';
            $direction = $transactionIndex > 0 && $transactionIndex % 4 === 3 ? 'debit' : 'credit';
            $amount = $direction === 'credit'
                ? ($isUsdt ? (string) fake()->randomFloat(6, 10, 100) : (string) fake()->randomFloat(2, 50, 500))
                : ($isUsdt ? '0.100000' : '1.00');
            Carbon::setTestNow($base->copy()->subDays(($userIndex + $transactionIndex) % 30)->setTime(8 + $transactionIndex % 10, 0));
            $wallets->{$direction}($user, [
                'currency' => $currency,
                'amount' => $amount,
                'type' => $types[($userIndex + $transactionIndex) % count($types)],
                'reference' => 'demo-'.$user->id.'-'.$transactionIndex,
                'remark' => 'Generated test data',
            ], 'system', 'test-data');
        }
    }
}
