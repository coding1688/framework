<?php

namespace Database\Factories\System;

use App\Models\System\SystemScheduleTask;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SystemScheduleTask>
 */
class SystemScheduleTaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => fake()->unique()->bothify('task-##??'),
            'name_i18n' => ['en' => fake()->words(3, true)],
            'description_i18n' => null,
            'command' => 'notifications:prune',
            'cron' => '0 0 * * *',
            'timezone' => 'UTC',
            'active' => true,
            'without_overlapping' => true,
            'sort' => 0,
        ];
    }
}
