<?php

namespace App\Services\System;

use App\Attributes\Schedulable;
use Cron\CronExpression;
use Illuminate\Support\Facades\Artisan;
use InvalidArgumentException;
use ReflectionClass;

class ScheduleCommandRegistry
{
    /** @var array<string, array{name_i18n: array<string, string>, cron: string|null, key: string, without_overlapping: bool, sort: int}>|null */
    private ?array $commands = null;

    /** @return array<string, array{name_i18n: array<string, string>, cron: string|null, key: string, without_overlapping: bool, sort: int}> */
    public function all(): array
    {
        if ($this->commands !== null) {
            return $this->commands;
        }
        $commands = [];
        foreach (Artisan::all() as $name => $command) {
            $attributes = (new ReflectionClass($command))->getAttributes(Schedulable::class);
            if ($attributes === []) {
                continue;
            }
            /** @var Schedulable $schedulable */
            $schedulable = $attributes[0]->newInstance();
            if ($schedulable->cron !== null && ! CronExpression::isValidExpression($schedulable->cron)) {
                throw new InvalidArgumentException("Invalid schedule Cron for [{$name}].");
            }
            $commands[$name] = [
                'name_i18n' => $schedulable->name, 'cron' => $schedulable->cron, 'key' => $schedulable->key ?? $name,
                'without_overlapping' => $schedulable->withoutOverlapping, 'sort' => $schedulable->sort,
            ];
        }
        ksort($commands);

        return $this->commands = $commands;
    }

    /** @return array<string, array{name_i18n: array<string, string>, cron: string|null, key: string, without_overlapping: bool, sort: int}> */
    public function defaults(): array
    {
        return array_filter($this->all(), fn (array $command): bool => $command['cron'] !== null);
    }

    public function has(string $command): bool
    {
        return array_key_exists($command, $this->all());
    }
}
