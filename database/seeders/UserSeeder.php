<?php

namespace Database\Seeders;

use App\Models\Member\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        foreach (['test001' => true, 'user001' => false] as $username => $isTest) {
            $user = User::firstOrNew(['username' => $username]);
            if (! $user->exists) {
                $user->forceFill([
                    'username' => $username,
                    'email' => $username.'@example.com',
                    'email_verified_at' => now(),
                    'password' => '12345qwert',
                    'active' => true,
                    'is_test' => $isTest,
                ])->save();
            }
            $user->profile()->firstOrCreate([], ['nickname' => $username, 'notification_preferences' => []]);
        }
    }
}
