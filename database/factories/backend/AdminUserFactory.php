<?php

namespace Database\Factories\backend;

use App\Models\Admin\AdminUser;
use Illuminate\Database\Eloquent\Factories\Factory;

class AdminUserFactory extends Factory
{
    protected $model = AdminUser::class;

    public function definition(): array
    {
        return ['name' => fake()->name(), 'email' => fake()->unique()->safeEmail(), 'password' => 'TestingPass123!', 'locale' => 'zh_CN', 'active' => true, 'auth_version' => 1];
    }

    public function super(): static
    {
        return $this->state(fn () => ['is_super' => true]);
    }
}
