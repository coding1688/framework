<?php

namespace Database\Factories\Member;

use App\Models\Member\MemberVipLevel;
use App\Models\Member\MemberVipRecord;
use App\Models\Member\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MemberVipRecord>
 */
class MemberVipRecordFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'to_vip_level_id' => MemberVipLevel::factory(),
            'started_at' => now(),
        ];
    }
}
