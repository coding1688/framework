<?php

namespace Database\Seeders\backend\admin;

use App\Models\Admin\AdminDepartment;
use App\Models\Admin\AdminUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdminDeparturementSeeder extends Seeder
{
    public function run(): void
    {
        $boardNames = ['en' => 'Board of directors', 'zh_CN' => '董事会', 'zh_TW' => '董事會', 'th' => 'คณะกรรมการ', 'ja' => '取締役会', 'ko' => '이사회'];
        $board = AdminDepartment::unguarded(fn () => AdminDepartment::firstOrCreate(['code' => 'board'], [
            'name_i18n' => $boardNames,
            'sort' => 0,
            'active' => true,
        ]));
        $this->fillTranslations($board, $boardNames);
        if (! app()->environment(['local', 'testing'])) {
            return;
        }
        // Create the first super administrator before any department leaders.
        $this->call(AdminUserSeeder::class);
        $board = AdminDepartment::where('code', 'board')->sole();
        $defaults = [
            ['operations', 'operate001', '12345qwert', ['en' => 'Operations', 'zh_CN' => '运营', 'zh_TW' => '營運', 'th' => 'การดำเนินงาน', 'ja' => '運営', 'ko' => '운영']],
            ['development', 'dev0001', '12345qwert', ['en' => 'Development', 'zh_CN' => '开发', 'zh_TW' => '開發', 'th' => 'การพัฒนา', 'ja' => '開発', 'ko' => '개발']],
            ['human_resources', 'hr00001', '1234qwer', ['en' => 'Human resources', 'zh_CN' => '人事', 'zh_TW' => '人事', 'th' => 'ทรัพยากรบุคคล', 'ja' => '人事', 'ko' => '인사']],
        ];
        foreach ($defaults as $sort => [$code, $username, $password, $names]) {
            DB::transaction(function () use ($board, $sort, $code, $username, $password, $names) {
                $existing = AdminDepartment::where('code', $code)->first();
                if ($existing) {
                    $this->fillTranslations($existing, $names);

                    return;
                }
                if (AdminUser::where('username', $username)->orWhere('email', $username.'@framework.local')->exists()) {
                    throw new \RuntimeException('Initial department leader account already exists: '.$username);
                }
                $department = AdminDepartment::unguarded(fn () => AdminDepartment::create([
                    'code' => $code, 'parent_id' => $board->id, 'name_i18n' => $names,
                    'sort' => $sort + 1, 'active' => true,
                ]));
                AdminUser::create([
                    'username' => $username, 'name' => $username, 'email' => $username.'@framework.local',
                    'password' => $password, 'department_id' => $department->id,
                    'locale' => 'zh_CN', 'active' => true, 'is_leader' => true,
                ]);
            });
        }
    }

    private function fillTranslations(AdminDepartment $department, array $defaults): void
    {
        $department->forceFill(['name_i18n' => array_replace($defaults, supportedTranslations($department->name_i18n))])->save();
    }
}
