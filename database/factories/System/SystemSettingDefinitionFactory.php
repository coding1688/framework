<?php

namespace Database\Factories\System;

use App\Models\System\SystemSettingGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

class SystemSettingDefinitionFactory extends Factory
{
    public function definition(): array
    {
        return ['group_id' => SystemSettingGroup::factory(), 'key' => 'test.setting'.fake()->unique()->numerify('########'), 'name_i18n' => ['en' => fake()->words(2, true)], 'input_type' => 'text', 'multiple' => false, 'option_source_type' => 'manual', 'option_source_key' => null, 'validation' => ['min_length' => 0, 'max_length' => 128], 'default_value' => ['value_string' => 'default'], 'secret' => false, 'active' => true, 'sort' => 0];
    }

    public function secret(): static
    {
        return $this->state(fn (): array => ['secret' => true, 'default_value' => null]);
    }
}
