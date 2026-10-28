<?php

namespace App\Services\Backend\System;

use App\Libs\Settings\SettingStore;
use App\Models\System\SystemScheduleRun;
use App\Models\System\SystemScheduleTask;
use App\Models\System\SystemSettingDefinition;
use App\Models\System\SystemSettingValue;
use App\Services\Backend\BaseService;
use App\Services\Backend\Common\Audit;
use App\Services\System\ScheduleCommandRegistry;
use App\Services\System\SystemScheduleDispatcher;
use Cron\CronExpression;
use DateTimeZone;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SystemScheduleService extends BaseService
{
    public function __construct(private readonly SystemScheduleDispatcher $dispatcher, private readonly SettingStore $settings) {}

    public function list(array $validated): LengthAwarePaginator
    {
        $this->guard('list');
        $query = SystemScheduleTask::orderBy('sort')->orderBy('id');
        if ($q = $validated['q'] ?? null) {
            $query->where(fn ($scope) => $scope->where('key', 'like', '%'.$q.'%')->orWhere('command', 'like', '%'.$q.'%'));
        }
        if (isset($validated['active'])) {
            $query->where('active', $validated['active']);
        }
        $page = $query->paginate(20, ['*'], 'page', (int) ($validated['page'] ?? 1));
        $page->getCollection()->each(fn (SystemScheduleTask $task) => $this->withState($task));

        return $page;
    }

    public function options(): array
    {
        $this->guard('options');
        $commands = collect(app(ScheduleCommandRegistry::class)->all())->map(fn (array $command, string $key): array => ['value' => $key, 'label' => $this->localized($command['name_i18n'])])->values()->all();

        return [
            'commands' => $commands, 'timezones' => DateTimeZone::listIdentifiers(),
            'tasks' => SystemScheduleTask::orderBy('sort')->orderBy('id')->get()->map(fn (SystemScheduleTask $task): array => ['value' => $task->id, 'label' => $task->name])->values()->all(),
            'default_timezone' => $this->settings->get('schedule.timezone', config('schedule.defaults.timezone')),
        ];
    }

    public function detail(int $id): SystemScheduleTask
    {
        $this->guard('detail');

        return $this->withState(SystemScheduleTask::findOrFail($id));
    }

    public function create(array $validated): SystemScheduleTask
    {
        $this->guard('create');

        return DB::transaction(function () use ($validated): SystemScheduleTask {
            $task = SystemScheduleTask::create($validated);
            Audit::record('system.schedule.create', (string) $task->id);

            return $this->withState($task);
        });
    }

    public function update(array $validated, int $id): SystemScheduleTask
    {
        $this->guard('update');

        return DB::transaction(function () use ($validated, $id): SystemScheduleTask {
            $task = SystemScheduleTask::lockForUpdate()->findOrFail($id);
            $task->update($validated);
            Audit::record('system.schedule.update', (string) $id);

            return $this->withState($task);
        });
    }

    public function status(array $validated, int $id): SystemScheduleTask
    {
        $this->guard('status');

        return DB::transaction(function () use ($validated, $id): SystemScheduleTask {
            $task = SystemScheduleTask::lockForUpdate()->findOrFail($id);
            $task->update(['active' => $validated['active']]);
            Audit::record('system.schedule.status', (string) $id, ['active' => (bool) $validated['active']]);

            return $this->withState($task);
        });
    }

    public function delete(int $id): void
    {
        $this->guard('delete');
        DB::transaction(function () use ($id): void {
            $task = SystemScheduleTask::lockForUpdate()->findOrFail($id);
            if (SystemScheduleRun::where('system_schedule_task_id', $id)->whereIn('status', ['pending', 'running'])->exists()) {
                throw ValidationException::withMessages(['id' => __('backend/system/operations.task_running')]);
            }
            $task->delete();
            Audit::record('system.schedule.delete', (string) $id);
        });
    }

    public function run(int $id): SystemScheduleRun
    {
        $this->guard('run');
        $task = SystemScheduleTask::findOrFail($id);
        $run = $this->dispatcher->dispatch($task, 'manual', Auth::guard('backend')->id());
        abort_if($run === null, 409, __('backend/system/operations.task_running'));
        Audit::record('system.schedule.run', (string) $id);

        return $run;
    }

    public function runs(array $validated): LengthAwarePaginator
    {
        $this->guard('runs');
        $query = SystemScheduleRun::with(['task', 'administrator'])->latest('id');
        foreach (['system_schedule_task_id', 'status', 'trigger'] as $field) {
            if (isset($validated[$field])) {
                $query->where($field, $validated[$field]);
            }
        }

        return $query->paginate(20, ['*'], 'page', (int) ($validated['page'] ?? 1));
    }

    public function runDetail(int $id): SystemScheduleRun
    {
        $this->guard('runDetail');

        return SystemScheduleRun::with(['task', 'administrator'])->findOrFail($id);
    }

    public function settings(): array
    {
        $this->guard('settings');

        return $this->settingValues();
    }

    public function updateSettings(array $validated): array
    {
        $this->guard('updateSettings');
        $payloads = [
            'schedule.enabled' => ['value_boolean' => $validated['enabled']],
            'schedule.timezone' => ['value_string' => $validated['timezone']],
            'schedule.log_retention_days' => ['value_number' => $validated['log_retention_days']],
        ];
        DB::transaction(function () use ($payloads): void {
            $definitions = SystemSettingDefinition::whereIn('key', array_keys($payloads))->lockForUpdate()->get()->keyBy('key');
            foreach ($payloads as $key => $payload) {
                $definition = $definitions->get($key);
                abort_if($definition === null, 404);
                SystemSettingValue::updateOrCreate(['setting_definition_id' => $definition->id], ['payload' => $payload]);
            }
            Audit::record('system.schedule.updateSettings', null, ['keys' => array_keys($payloads)]);
        });
        $this->settings->forget();

        return $this->settingValues();
    }

    private function withState(SystemScheduleTask $task): SystemScheduleTask
    {
        $last = SystemScheduleRun::where('system_schedule_task_id', $task->id)->latest('id')->first();
        $task->setAttribute('next_run_at', $task->active ? (new CronExpression($task->cron))->getNextRunDate('now', 0, false, $task->timezone)->format(DATE_ATOM) : null);
        $task->setAttribute('running', SystemScheduleRun::where('system_schedule_task_id', $task->id)->whereIn('status', ['pending', 'running'])->exists());
        $task->setAttribute('last_run', $last ? ['id' => $last->id, 'status' => $last->status, 'finished_at' => $last->finished_at?->toIso8601String()] : null);

        return $task;
    }

    private function settingValues(): array
    {
        return [
            'enabled' => (bool) $this->settings->get('schedule.enabled', config('schedule.defaults.enabled')),
            'timezone' => $this->settings->get('schedule.timezone', config('schedule.defaults.timezone')),
            'log_retention_days' => (int) $this->settings->get('schedule.log_retention_days', config('schedule.defaults.log_retention_days')),
        ];
    }

    private function localized(array $translations): string
    {
        foreach (array_unique([app()->getLocale(), 'en', config('app.fallback_locale'), 'zh_CN']) as $locale) {
            if (trim($translations[$locale] ?? '') !== '') {
                return $translations[$locale];
            }
        }

        return '';
    }

    private function guard(string $action): void
    {
        $this->authorize('system.schedule.'.$action, true);
    }
}
