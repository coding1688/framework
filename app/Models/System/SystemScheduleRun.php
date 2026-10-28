<?php

namespace App\Models\System;

use App\Models\Admin\AdminUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemScheduleRun extends Model
{
    protected $fillable = ['system_schedule_task_id', 'task_key', 'command', 'trigger', 'administrator_id', 'status', 'exit_code', 'output', 'error', 'started_at', 'finished_at', 'duration_ms'];

    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'finished_at' => 'datetime'];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(SystemScheduleTask::class, 'system_schedule_task_id')->withTrashed();
    }

    public function administrator(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'administrator_id');
    }
}
