<?php

namespace Tests\Feature;

use App\Models\Admin\AdminUser;
use App\Models\Member\MemberWallet;
use App\Models\Member\MemberWalletDailyStat;
use App\Models\Member\MemberWalletTransaction;
use App\Models\Member\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberWalletTest extends TestCase
{
    use RefreshDatabase;

    private const SecurityPassword = 'SeparateSafe123';

    public function test_credit_debit_idempotency_and_history_are_transactional(): void
    {
        $admin = $this->backend();
        $user = User::factory()->create();
        $credit = $this->payload('RMB', '100.25', 'wallet-credit-1');

        $this->postJson(route('member.wallet.credit', $user), $credit)->assertUnprocessable()->assertJsonValidationErrors('security_password');
        $this->postJson(route('member.wallet.credit', $user), [...$credit, 'security_password' => self::SecurityPassword])
            ->assertOk()->assertJsonPath('data.amount', '100.25')->assertJsonPath('data.balance_after', '100.25');
        $transaction = MemberWalletTransaction::where('reference', 'wallet-credit-1')->sole();
        $this->postJson(route('member.wallet.credit', $user), [...$credit, 'security_password' => self::SecurityPassword])
            ->assertOk()->assertJsonPath('data.id', $transaction->id);
        $this->assertSame(1, MemberWalletTransaction::where('user_id', $user->id)->count());
        $this->assertSame(10025, MemberWallet::where('user_id', $user->id)->sole()->balance_minor);

        $this->postJson(route('member.wallet.debit', $user), [...$this->payload('RMB', '25.25', 'wallet-debit-1'), 'security_password' => self::SecurityPassword])
            ->assertOk()->assertJsonPath('data.balance_before', '100.25')->assertJsonPath('data.balance_after', '75.00');
        $this->postJson(route('member.wallet.debit', $user), [...$this->payload('RMB', '100', 'wallet-debit-2'), 'security_password' => self::SecurityPassword])
            ->assertUnprocessable()->assertJsonValidationErrors('amount');
        $this->assertSame(7500, MemberWallet::where('user_id', $user->id)->sole()->balance_minor);
        $this->assertSame(2, MemberWalletTransaction::where('user_id', $user->id)->count());

        $this->getJson(route('member.wallet.history', ['id' => $user->id, 'direction' => 'credit']))
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.balance_before', '0.00')->assertJsonPath('data.0.balance_after', '100.25');
        $testUser = User::factory()->test()->create();
        $this->postJson(route('member.wallet.credit', $testUser), [...$this->payload('RMB', '10', 'wallet-test-user-1'), 'security_password' => self::SecurityPassword])->assertOk();
        $this->getJson(route('member.wallet.list', ['is_test' => 1]))->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.user_id', $testUser->id)->assertJsonPath('data.0.user.is_test', true);
        $this->getJson(route('member.wallet.list', ['is_test' => 0]))->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.user_id', $user->id)->assertJsonPath('data.0.user.is_test', false);
        $this->actingAs($user, 'web')->withSession(['web.auth_version' => 1]);
        $this->getJson(route('wallets.list'))->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.balance', '75.00')->assertJsonPath('main_currency', 'RMB');
        $this->getJson(route('wallets.history', ['direction' => 'debit']))->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.amount', '25.25')->assertJsonPath('data.0.balance_after', '75.00');
        $this->assertDatabaseHas('admin_logs', ['action' => 'member.wallet.credit', 'subject' => (string) $user->id]);
        $this->assertSame($admin->name, $transaction->actor);
    }

    public function test_daily_statistics_accumulate_by_date_currency_and_type(): void
    {
        $this->backend();
        $user = User::factory()->create();
        $this->travelTo(Carbon::parse('2026-10-28 08:00:00'));
        $firstCredit = [...$this->payload('RMB', '100', 'daily-credit-1'), 'type' => 'deposit'];
        $this->postJson(route('member.wallet.credit', $user), [...$firstCredit, 'security_password' => self::SecurityPassword])->assertOk();
        $this->postJson(route('member.wallet.credit', $user), [...$firstCredit, 'security_password' => self::SecurityPassword])->assertOk();
        $this->postJson(route('member.wallet.credit', $user), [...$this->payload('RMB', '50', 'daily-credit-2'), 'type' => 'deposit', 'security_password' => self::SecurityPassword])->assertOk();
        $this->postJson(route('member.wallet.credit', $user), [...$this->payload('RMB', '20', 'daily-bonus-1'), 'type' => 'bonus', 'security_password' => self::SecurityPassword])->assertOk();
        $this->postJson(route('member.wallet.debit', $user), [...$this->payload('RMB', '30', 'daily-debit-1'), 'type' => 'deposit', 'security_password' => self::SecurityPassword])->assertOk();
        $this->postJson(route('member.wallet.debit', $user), [...$this->payload('RMB', '1000', 'daily-debit-denied'), 'type' => 'deposit', 'security_password' => self::SecurityPassword])
            ->assertUnprocessable()->assertJsonValidationErrors('amount');

        $this->travelTo(Carbon::parse('2026-10-29 08:00:00'));
        $this->postJson(route('member.wallet.credit', $user), [...$this->payload('USDT', '1.123456', 'daily-usdt-1'), 'type' => 'deposit', 'security_password' => self::SecurityPassword])->assertOk();

        $this->assertSame(3, MemberWalletDailyStat::where('user_id', $user->id)->count());
        $stat = MemberWalletDailyStat::where('user_id', $user->id)->where('stat_date', '2026-10-28')->where('type', 'deposit')->sole();
        $this->assertSame(15000, $stat->credit_amount_minor);
        $this->assertSame(3000, $stat->debit_amount_minor);
        $this->assertSame(2, $stat->credit_count);
        $this->assertSame(1, $stat->debit_count);
        $this->assertSame(3, $stat->transaction_count);

        $this->getJson(route('member.wallet.statistics', ['id' => $user->id, 'type' => 'deposit', 'date_from' => '2026-10-28', 'date_to' => '2026-10-28']))
            ->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.credit_amount', '150.00')
            ->assertJsonPath('data.0.debit_amount', '30.00')->assertJsonPath('data.0.transaction_count', 3);
        $this->getJson(route('member.wallet.statistics', $user))->assertOk()->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.stat_date', '2026-10-29')->assertJsonPath('data.0.credit_amount', '1.123456');

        $this->actingAs($user, 'web')->withSession(['web.auth_version' => 1]);
        $this->getJson(route('wallets.statistics', ['type' => 'bonus']))->assertOk()->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.credit_amount', '20.00')->assertJsonPath('data.0.credit_count', 1);
        $otherUser = User::factory()->create();
        $this->actingAs($otherUser, 'web')->withSession(['web.auth_version' => 1]);
        $this->getJson(route('wallets.statistics'))->assertOk()->assertJsonPath('total', 0)->assertJsonCount(0, 'data');

        $ordinaryAdmin = AdminUser::factory()->create(['is_super' => false]);
        $this->actingAs($ordinaryAdmin, 'backend')->withSession(['backend.auth_version' => 1]);
        $this->getJson(route('member.wallet.statistics', $user))->assertForbidden();
    }

    public function test_usdt_precision_and_reference_conflicts_never_change_balance(): void
    {
        $this->backend();
        $user = User::factory()->create();
        $credit = $this->payload('USDT', '1.123456', 'wallet-usdt-1');
        $this->postJson(route('member.wallet.credit', $user), [...$credit, 'security_password' => self::SecurityPassword])
            ->assertOk()->assertJsonPath('data.amount', '1.123456');

        $this->postJson(route('member.wallet.credit', $user), [...$credit, 'amount' => '1.1234567', 'security_password' => self::SecurityPassword])
            ->assertUnprocessable()->assertJsonValidationErrors('amount');
        $this->postJson(route('member.wallet.credit', $user), [...$credit, 'amount' => '2', 'security_password' => self::SecurityPassword])
            ->assertUnprocessable()->assertJsonValidationErrors('reference');
        $this->assertSame(1123456, MemberWallet::where('user_id', $user->id)->sole()->balance_minor);
        $this->assertSame(1, MemberWalletTransaction::where('user_id', $user->id)->count());
        $this->getJson(route('member.wallet.options'))->assertOk()
            ->assertJsonPath('data.main_currency', 'RMB')->assertJsonCount(2, 'data.currencies');
    }

    public function test_wallet_endpoints_enforce_permissions_and_authentication(): void
    {
        $admin = AdminUser::factory()->create(['is_super' => false]);
        $user = User::factory()->create();
        $this->actingAs($admin, 'backend')->withSession(['backend.auth_version' => 1]);
        $this->getJson(route('member.wallet.list'))->assertForbidden();
        $this->postJson(route('member.wallet.credit', $user), $this->payload('RMB', '1', 'wallet-denied'))->assertForbidden();
        $this->getJson(route('wallets.list'))->assertUnauthorized();
    }

    private function backend(): AdminUser
    {
        $admin = AdminUser::factory()->super()->create(['security_password' => self::SecurityPassword]);
        $this->actingAs($admin, 'backend')->withSession(['backend.auth_version' => $admin->auth_version]);

        return $admin;
    }

    private function payload(string $currency, string $amount, string $reference): array
    {
        return ['currency' => $currency, 'amount' => $amount, 'type' => 'adjustment', 'reference' => $reference, 'remark' => 'Verification'];
    }
}
