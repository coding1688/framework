<?php

namespace App\Http\Resources\Backend\System;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SystemSettingValueResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $value = $this->secret ? null : ($this->resolved_value ?? $this->value?->payload);
        if (is_array($value) && array_key_exists('value_i18n', $value)) {
            $value['value_i18n'] = supportedTranslations($value['value_i18n']);
        }

        return [
            ...(new SystemSettingDefinitionResource($this->resource))->toArray($request),
            'definition_id' => $this->id,
            'display_value' => $this->display_value,
            'has_value' => $this->value !== null,
            'has_effective_value' => $this->has_effective_value ?? ($this->value !== null),
            'value' => $value,
        ];
    }
}
