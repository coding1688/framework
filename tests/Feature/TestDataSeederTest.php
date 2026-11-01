<?php

namespace Tests\Feature;

use App\Models\Member\MemberWalletDailyStat;
use App\Models\Member\MemberWalletTransaction;
use App\Models\Member\User;
use Database\Seeders\TestDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TestDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_configurable_demo_data_uses_wallet_business_logic_and_daily_statistics(): void
    {
        putenv('TEST_DATA_USERS=4');
        putenv('TEST_DATA_TRANSACTIONS=5');
        try {
            $this->seed(TestDataSeeder::class);
        } finally {
            putenv('TEST_DATA_USERS');
            putenv('TEST_DATA_TRANSACTIONS');
        }

        $users = User::with('wallets')->get();
        $this->assertCount(4, $users);
        $this->assertSame(2, $users->where('is_test', true)->count());
        $this->assertSame(2, $users->where('is_test', false)->count());

        foreach ($users as $user) {
            $this->assertNotEmpty($user->wallets);
            $this->assertSame(5, MemberWalletTransaction::where('user_id', $user->id)->count());
            $stats = MemberWalletDailyStat::where('user_id', $user->id)->get();
            $this->assertGreaterThan(1, $stats->count());
            $this->assertSame(5, $stats->sum(fn (MemberWalletDailyStat $stat): int => $stat->transaction_count));
        }
    }

    public function test_tool_init_demo_option_generates_data_without_resetting_tables(): void
    {
        $this->artisan('tool:init', ['--demo' => true, '--users' => 2, '--transactions' => 3])->assertExitCode(0);

        $users = User::all();
        $this->assertCount(2, $users);
        foreach ($users as $user) {
            $this->assertSame(3, MemberWalletTransaction::where('user_id', $user->id)->count());
            $this->assertSame(3, MemberWalletDailyStat::where('user_id', $user->id)->get()->sum(fn (MemberWalletDailyStat $stat): int => $stat->transaction_count));
        }
    }
}
