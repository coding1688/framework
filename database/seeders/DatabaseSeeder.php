<?php

namespace Database\Seeders;

use Database\Seeders\backend\admin\AdminDeparturementSeeder;
use Database\Seeders\backend\admin\AdminMenuSeeder;
use Database\Seeders\backend\admin\AdminUserSeeder;
use Database\Seeders\backend\file\FileStorageSeeder;
use Database\Seeders\backend\member\MemberVipSeeder;
use Database\Seeders\backend\notification\SystemNotificationTemplateSeeder;
use Database\Seeders\backend\schedule\SystemScheduleSeeder;
use Database\Seeders\backend\setting\SystemSettingSeeder;
use Database\Seeders\common\location\PhilippineLocationSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([AdminDeparturementSeeder::class, FileStorageSeeder::class, SystemSettingSeeder::class, SystemNotificationTemplateSeeder::class, SystemScheduleSeeder::class, MemberVipSeeder::class, AdminMenuSeeder::class, AdminUserSeeder::class, PhilippineLocationSeeder::class]);
    }
}
