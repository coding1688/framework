<?php

namespace App\Http\Resources\Backend\System;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SystemScheduleTaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'key' => $this->key, 'name' => $this->name, 'name_i18n' => $this->name_i18n,
            'description' => $this->description, 'description_i18n' => $this->description_i18n,
            'command' => $this->command, 'cron' => $this->cron, 'timezone' => $this->timezone,
            'active' => $this->active, 'without_overlapping' => $this->without_overlapping, 'sort' => $this->sort,
            'next_run_at' => $this->next_run_at ?? null, 'running' => (bool) ($this->running ?? false),
            'last_run' => $this->last_run ?? null, 'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
