<?php

namespace App\Http\Resources\Backend\System;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SystemNotificationTemplateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'code' => $this->code, 'name' => $this->name,
            'name_i18n' => $this->name_i18n, 'subject_i18n' => $this->subject_i18n,
            'content_i18n' => $this->content_i18n, 'channels' => $this->channels,
            'active' => $this->active, 'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
