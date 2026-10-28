<?php

namespace Database\Factories\Member;

use App\Models\Member\MemberVipLevel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MemberVipLevel>
 */
class MemberVipLevelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'level' => fake()->unique()->numberBetween(10, 1000),
            'name_i18n' => ['en' => 'VIP '.fake()->unique()->numberBetween(10, 1000)],
            'description_i18n' => null,
            'benefits_i18n' => null,
            'duration_days' => null,
            'active' => true,
            'sort' => 0,
        ];
    }
}
