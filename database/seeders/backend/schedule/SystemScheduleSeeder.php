<?php

namespace Database\Seeders\backend\schedule;

use App\Libs\Settings\SettingStore;
use App\Models\System\SystemScheduleRun;
use App\Models\System\SystemScheduleTask;
use App\Models\System\SystemSettingDefinition;
use App\Models\System\SystemSettingValue;
use App\Services\System\ScheduleCommandRegistry;
use Illuminate\Database\Seeder;

class SystemScheduleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (app(ScheduleCommandRegistry::class)->defaults() as $command => $definition) {
            $key = $definition['key'];
            $names = $definition['name_i18n'];
            $task = SystemScheduleTask::firstOrCreate(['key' => $key], [
                'name_i18n' => $names, 'description_i18n' => null, 'command' => $command,
                'cron' => $definition['cron'], 'timezone' => config('schedule.defaults.timezone'),
                'active' => true, 'without_overlapping' => $definition['without_overlapping'], 'sort' => $definition['sort'],
            ]);
            $task->forceFill(['name_i18n' => array_replace($names, supportedTranslations($task->name_i18n))])->save();
            SystemScheduleRun::where('task_key', $key)->whereNull('system_schedule_task_id')->update(['system_schedule_task_id' => $task->id]);
            SystemScheduleRun::where('task_key', $key)->whereNull('command')->update(['command' => $command]);
        }
        $timezoneDefinition = SystemSettingDefinition::where('key', 'schedule.timezone')->first();
        if ($timezoneDefinition && ($timezoneDefinition->default_value['value_string'] ?? null) === 'UTC') {
            $timezoneDefinition->update(['default_value' => ['value_string' => 'Asia/Shanghai']]);
        }
        $timezoneValue = $timezoneDefinition ? SystemSettingValue::where('setting_definition_id', $timezoneDefinition->id)->first() : null;
        if ($timezoneValue && ($timezoneValue->payload['value_string'] ?? null) === 'UTC') {
            $timezoneValue->update(['payload' => ['value_string' => 'Asia/Shanghai']]);
        }
        app(SettingStore::class)->forget();
    }
}
