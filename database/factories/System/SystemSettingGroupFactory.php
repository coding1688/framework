<?php

namespace Database\Factories\System;

use Illuminate\Database\Eloquent\Factories\Factory;

class SystemSettingGroupFactory extends Factory
{
    public function definition(): array
    {
        return ['parent_id' => null, 'code' => 'group'.fake()->unique()->numerify('########'), 'name_i18n' => ['en' => fake()->words(2, true)], 'active' => true, 'sort' => 0];
    }
}
