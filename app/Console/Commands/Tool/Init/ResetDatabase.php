<?php

namespace App\Console\Commands\Tool\Init;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('tool:init
    {--full : 删除全部表，重建迁移和默认 seed，再生成测试数据}
    {--reset-seed : 删除全部表，重建迁移和默认 seed}
    {--demo : 仅追加测试数据，不删除或重建任何表}
    {--fresh : 仅删除全部表并重建迁移，不执行 seed}
    {--users=12 : 测试用户数量，范围 1-100}
    {--transactions=6 : 每个测试用户的账变笔数，范围 1-50}
    {--yes : 跳过 RESET 文字确认，仍仅限 local/testing}
')]
#[Description('安全重建数据库或生成测试数据')]
class ResetDatabase extends Command
{
    public function handle(): int
    {
        $actions = array_values(array_filter(['full', 'reset-seed', 'demo', 'fresh'], fn (string $action): bool => (bool) $this->option($action)));
        if (count($actions) > 1) {
            $this->error('一次只能选择一个操作。');

            return self::INVALID;
        }
        $action = $actions[0] ?? $this->selectAction();
        if ($action === null) {
            return self::SUCCESS;
        }

        $users = filter_var($this->option('users'), FILTER_VALIDATE_INT);
        $transactions = filter_var($this->option('transactions'), FILTER_VALIDATE_INT);
        if ($users === false || $users < 1 || $users > 100 || $transactions === false || $transactions < 1 || $transactions > 50) {
            $this->error('用户数量必须是 1-100，账变笔数必须是 1-50。');

            return self::INVALID;
        }
        if (! $this->laravel->environment(['local', 'testing'])) {
            $this->error('安全限制：只允许 local/testing 环境。');

            return self::FAILURE;
        }

        if ($action !== 'demo') {
            if (! $this->confirmReset()) {
                $this->warn('已取消，数据库未修改。');

                return self::FAILURE;
            }
            $options = ['--force' => true];
            if ($action !== 'fresh') {
                $options['--seed'] = true;
            }
            if ($this->call('migrate:fresh', $options) !== self::SUCCESS) {
                return self::FAILURE;
            }
        }
        if (($action === 'full' || $action === 'demo') && $this->runDemo($users, $transactions) !== self::SUCCESS) {
            return self::FAILURE;
        }

        $this->info('操作完成。');

        return self::SUCCESS;
    }

    private function selectAction(): ?string
    {
        $labels = ['完整重建并生成测试数据', '完整重建和默认 seed', '仅追加测试数据', '仅重建表结构', '退出'];
        $actions = ['full', 'reset-seed', 'demo', 'fresh', null];
        $selected = $this->choice('请选择操作', $labels, array_key_last($labels));

        return $actions[array_search($selected, $labels, true)];
    }

    private function confirmReset(): bool
    {
        if ($this->option('yes')) {
            return true;
        }
        $this->warn('警告：此操作将删除当前数据库的全部表，且无法恢复。');

        return $this->ask('请输入 RESET 继续') === 'RESET';
    }

    private function runDemo(int $users, int $transactions): int
    {
        $previousUsers = getenv('TEST_DATA_USERS');
        $previousTransactions = getenv('TEST_DATA_TRANSACTIONS');
        putenv('TEST_DATA_USERS='.$users);
        putenv('TEST_DATA_TRANSACTIONS='.$transactions);

        try {
            return $this->call('db:seed', ['--class' => 'TestDataSeeder', '--force' => true]);
        } finally {
            $previousUsers === false ? putenv('TEST_DATA_USERS') : putenv('TEST_DATA_USERS='.$previousUsers);
            $previousTransactions === false ? putenv('TEST_DATA_TRANSACTIONS') : putenv('TEST_DATA_TRANSACTIONS='.$previousTransactions);
        }
    }
}
