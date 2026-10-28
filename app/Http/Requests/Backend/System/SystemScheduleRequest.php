<?php

namespace App\Http\Requests\Backend\System;

use App\Http\Requests\Backend\BaseRequest;
use App\Services\System\ScheduleCommandRegistry;
use Cron\CronExpression;
use DateTimeZone;
use Illuminate\Validation\Rule;

class SystemScheduleRequest extends BaseRequest
{
    public function rules(): array
    {
        $action = $this->route()->getActionMethod();
        if ($action === 'list') {
            return ['q' => ['nullable', 'string', 'max:128'], 'active' => ['nullable', 'boolean'], 'page' => ['nullable', 'integer', 'min:1']];
        }
        if ($action === 'runs') {
            return ['system_schedule_task_id' => ['nullable', 'integer', 'exists:system_schedule_tasks,id'], 'status' => ['nullable', Rule::in(['pending', 'running', 'success', 'failed'])], 'trigger' => ['nullable', Rule::in(['schedule', 'manual'])], 'page' => ['nullable', 'integer', 'min:1']];
        }
        if ($action === 'status') {
            return ['active' => ['required', 'boolean']];
        }
        if ($action === 'updateSettings') {
            return ['enabled' => ['required', 'boolean'], 'timezone' => ['required', 'string', 'max:64', Rule::in(DateTimeZone::listIdentifiers())], 'log_retention_days' => ['required', 'integer', 'min:1', 'max:3650']];
        }
        if (! in_array($action, ['create', 'update'], true)) {
            return [];
        }

        return [
            'key' => ['required', 'string', 'max:128', 'regex:/^[a-z][a-z0-9:_-]+$/', Rule::unique('system_schedule_tasks', 'key')->ignore($this->route('id'))],
            ...$this->localizedRules('name_i18n', 128),
            ...$this->localizedRules('description_i18n', 512, false),
            'command' => ['required', 'string', 'max:128', Rule::in(array_keys(app(ScheduleCommandRegistry::class)->all()))],
            'cron' => ['required', 'string', 'max:32', function (string $attribute, mixed $value, callable $fail): void {
                if (! is_string($value) || ! CronExpression::isValidExpression($value)) {
                    $fail(__('backend/system/operations.invalid_cron'));
                }
            }],
            'timezone' => ['required', 'string', 'max:64', Rule::in(DateTimeZone::listIdentifiers())],
            'active' => ['required', 'boolean'], 'without_overlapping' => ['required', 'boolean'],
            'sort' => ['required', 'integer', 'min:0', 'max:99999'],
        ];
    }
}
