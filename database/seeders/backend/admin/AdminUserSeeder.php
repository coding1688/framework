<?php

namespace Database\Seeders\backend\admin;

use App\Models\Admin\AdminDepartment;
use App\Models\Admin\AdminUser;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        if (AdminUser::where('username', 'admin')->exists()) {
            return;
        }

        $admin = new AdminUser;
        $admin->forceFill([
            'username' => 'admin',
            'name' => 'admin',
            'email' => 'admin@framework.local',
            'password' => '12345qwert',
            'department_id' => AdminDepartment::where('code', 'board')->sole()->id,
            'locale' => 'zh_CN',
            'active' => true,
            'is_super' => true,
        ])->save();
    }
}
