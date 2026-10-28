<?php

namespace App\Http\Resources\Backend\System;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SystemScheduleRunResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'system_schedule_task_id' => $this->system_schedule_task_id,
            'task_key' => $this->task_key, 'task_name' => $this->task?->name ?: $this->task_key,
            'command' => $this->command, 'trigger' => $this->trigger, 'status' => $this->status,
            'administrator' => $this->administrator ? ['id' => $this->administrator->id, 'name' => $this->administrator->name] : null,
            'exit_code' => $this->exit_code, 'error' => $this->error, 'duration_ms' => $this->duration_ms,
            'output' => $this->when($request->route()?->getActionMethod() === 'runDetail', $this->output),
            'started_at' => $this->started_at?->toIso8601String(), 'finished_at' => $this->finished_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
