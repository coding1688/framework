<?php

namespace Database\Factories\System;

use App\Models\System\SystemSettingDefinition;
use Illuminate\Database\Eloquent\Factories\Factory;

class SystemSettingValueFactory extends Factory
{
    public function definition(): array
    {
        return ['setting_definition_id' => SystemSettingDefinition::factory(), 'payload' => ['value_string' => fake()->word()]];
    }
}
