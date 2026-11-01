<?php

namespace Database\Factories\Member;

use App\Models\Member\User;
use App\Models\Member\UserProfile;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'username' => fake()->unique()->userName(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'vip_level' => 0,
            'avatar_url' => null,
            'remember_token' => Str::random(10),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (User $user): void {
            $profile = new UserProfile([
                'nickname' => $user->getAttribute('name'),
                'avatar_url' => $user->getAttribute('avatar_url'),
                'notification_preferences' => $user->getAttribute('notification_preferences'),
            ]);
            $user->offsetUnset('name');
            $user->offsetUnset('avatar_url');
            $user->offsetUnset('notification_preferences');
            $user->setRelation('profile', $profile);
        })->afterCreating(function (User $user): void {
            $user->profile()->save($user->getRelation('profile'));
        });
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function test(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_test' => true,
        ]);
    }
}
