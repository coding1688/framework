<?php

namespace Database\Seeders\backend\member;

use App\Models\Member\MemberVipLevel;
use App\Models\Member\User;
use Illuminate\Database\Seeder;

class MemberVipSeeder extends Seeder
{
    public function run(): void
    {
        $locales = array_keys(config('platform.locales'));
        $defaults = [1 => 30, 2 => 365, 3 => null];
        $levels = array_unique([...array_keys($defaults), ...User::where('vip_level', '>', 0)->distinct()->pluck('vip_level')->map(fn ($level): int => (int) $level)->all()]);
        sort($levels);
        foreach ($levels as $sort => $level) {
            $names = array_fill_keys($locales, 'VIP '.$level);
            $model = MemberVipLevel::firstOrCreate(['level' => $level], [
                'name_i18n' => $names, 'description_i18n' => null, 'benefits_i18n' => null,
                'duration_days' => $defaults[$level] ?? null, 'active' => true, 'sort' => $sort,
            ]);
            $model->forceFill(['name_i18n' => array_replace($names, supportedTranslations($model->name_i18n))])->save();
            User::where('vip_level', $level)->whereNull('vip_level_id')->get()->each(function (User $user) use ($model): void {
                $user->forceFill(['vip_level_id' => $model->id, 'vip_started_at' => $user->created_at ?? now(), 'vip_expires_at' => null])->save();
            });
        }
    }
}
