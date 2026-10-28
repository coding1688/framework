<?php

namespace App\Http\Resources\Backend\System;

use App\Libs\Fields\FieldRegistry;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SystemSettingDefinitionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'group_id' => $this->group_id, 'group' => $this->whenLoaded('group'), 'key' => $this->key,
            'name' => $this->name, 'name_i18n' => supportedTranslations($this->name_i18n), 'description' => $this->description, 'description_i18n' => supportedTranslations($this->description_i18n),
            'input_type' => $this->input_type, 'data_type' => app(FieldRegistry::class)->get($this->input_type)->dataType($this->multiple),
            'multiple' => $this->multiple, 'validation' => $this->validation, 'options' => (object) $this->options, 'options_i18n' => supportedTranslations($this->options_i18n),
            'option_source_type' => $this->option_source_type ?? 'manual', 'option_source_key' => $this->option_source_key, 'default_value' => $this->secret ? null : $this->default_value,
            'secret' => $this->secret, 'active' => $this->active, 'sort' => $this->sort,
        ];
    }
}
