<?php

namespace App\Console\Commands\Ai;

use App\Libs\Ai\AiManager;
use Illuminate\Console\Command;

class AiStatus extends Command
{
    protected $signature = 'ai:status';

    protected $description = 'Show AI capabilities and queue settings without exposing credentials';

    public function handle(AiManager $ai): int
    {
        $capabilities = $ai->capabilities();
        $rows = [];
        foreach ($capabilities as $key => $value) {
            $rows[] = [$key, is_bool($value) ? ($value ? 'yes' : 'no') : $value];
        }
        $rows[] = ['queue_connection', config('ai.tasks.connection')];
        $rows[] = ['queue', config('ai.tasks.queue')];
        $rows[] = ['draft_ttl_seconds', config('ai.tasks.ttl')];
        $this->table(['Setting', 'Value'], $rows);
        if (! $capabilities['enabled'] || ! $capabilities['configured']) {
            $this->warn('Configure the selected provider key and enable AI in System settings > AI, or use the corresponding server environment variables.');
        }

        return self::SUCCESS;
    }
}
