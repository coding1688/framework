<?php

namespace App\Http\Requests\Backend\File;

use App\Http\Requests\Backend\BaseRequest;
use App\Libs\File\StorageRegistry;
use App\Libs\File\UploadConfiguration;
use Illuminate\Validation\Rule;

class FileStorageAccountRequest extends BaseRequest
{
    public function rules(): array
    {
        $action = $this->route()->getActionMethod();
        if ($action === 'saveSettings') {
            return [
                'default_disk' => ['nullable', 'string', Rule::in(app(UploadConfiguration::class)->enabledDisks())],
                'max_upload_mb' => ['required', 'integer', 'min:0', 'max:'.app(UploadConfiguration::class)->defaults()['limits']['hard_max_upload_mb']],
                'allowed_extensions' => ['present', 'array', 'max:128'],
                'allowed_extensions.*' => ['required', 'string', 'regex:/^[a-z0-9]{1,16}$/', 'distinct', Rule::notIn(app(UploadConfiguration::class)->defaults()['limits']['forbidden_extensions'])],
            ];
        }
        if ($action === 'sync') {
            return [
                'source_disk' => ['required', 'string', Rule::in(app(UploadConfiguration::class)->enabledDisks())],
                'target_disk' => ['required', 'string', 'different:source_disk', Rule::in(app(UploadConfiguration::class)->enabledDisks())],
            ];
        }
        if (! in_array($action, ['create', 'update'], true)) {
            return [];
        }

        $storages = app(StorageRegistry::class);
        $provider = (string) $this->input('provider');
        $providerRules = $storages->hasProvider($provider) ? $storages->provider($provider)->rules() : ['configuration' => ['required', 'array']];

        return [
            'disk' => ['required', 'string', 'max:32', 'regex:/^[a-z][a-z0-9_]*$/', Rule::unique('file_storages', 'disk')->ignore($this->route('id'))],
            ...$this->localizedRules('name_i18n', 128),
            'provider' => ['required', Rule::in($storages->providerKeys())],
            'visibility' => ['required', Rule::in(['public', 'private'])],
            'enabled' => ['required', 'boolean'],
            ...$providerRules,
            'access_key' => ['nullable', 'string', 'max:512'],
            'secret_key' => ['nullable', 'string', 'max:512'],
        ];
    }
}
