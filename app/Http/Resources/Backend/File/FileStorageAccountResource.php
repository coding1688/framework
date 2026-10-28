<?php

namespace App\Http\Resources\Backend\File;

use App\Libs\File\StorageRegistry;
use App\Models\Common\FileConfiguration;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FileStorageAccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'disk' => $this->disk, 'name' => $this->name, 'name_i18n' => supportedTranslations($this->name_i18n),
            'provider' => $this->provider, 'visibility' => $this->visibility, 'enabled' => $this->enabled,
            'configuration' => $this->configuration,
            'has_access_key' => ! empty($this->credentials['key']), 'has_secret_key' => ! empty($this->credentials['secret']),
            'configured' => app(StorageRegistry::class)->providers()[$this->disk]['configured'] ?? false,
            'is_default' => FileConfiguration::find(1)?->default_disk === $this->disk,
            'builtin' => in_array($this->disk, ['public', 'local'], true),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
