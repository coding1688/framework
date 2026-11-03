<?php

namespace App\Libs\File\Providers;

use App\Libs\File\Contracts\StorageProvider;
use App\Libs\File\StorageAccount;
use Illuminate\Filesystem\FilesystemAdapter;

class S3StorageProvider implements StorageProvider
{
    public function __construct(private readonly bool $requiresEndpoint = true) {}

    public function rules(): array
    {
        return [
            'configuration' => ['required', 'array:bucket,region,endpoint,url,use_path_style_endpoint'],
            'configuration.bucket' => ['nullable', 'string', 'max:128', 'regex:/^[a-zA-Z0-9._-]+$/'],
            'configuration.region' => ['nullable', 'string', 'max:64', 'regex:/^[a-zA-Z0-9_-]+$/'],
            'configuration.endpoint' => ['nullable', 'url:http,https', 'max:512'],
            'configuration.url' => ['nullable', 'url:http,https', 'max:512'],
            'configuration.use_path_style_endpoint' => ['sometimes', 'boolean'],
        ];
    }

    public function configuration(StorageAccount $account): array
    {
        $configuration = $account->configuration;

        return [
            'driver' => 's3', 'key' => $account->credentials['key'] ?? null, 'secret' => $account->credentials['secret'] ?? null,
            'bucket' => $configuration['bucket'] ?? null, 'region' => $configuration['region'] ?? 'auto',
            'endpoint' => $configuration['endpoint'] ?? null, 'url' => $configuration['url'] ?? null,
            'use_path_style_endpoint' => (bool) ($configuration['use_path_style_endpoint'] ?? false),
            'visibility' => $account->visibility, 'throw' => true, 'report' => false,
            'http' => ['connect_timeout' => 5, 'timeout' => 30],
        ];
    }

    public function configured(array $configuration): bool
    {
        return ($configuration['driver'] ?? null) === 's3'
            && ! empty($configuration['bucket']) && ! empty($configuration['key']) && ! empty($configuration['secret'])
            && (! $this->requiresEndpoint || ! empty($configuration['endpoint']));
    }

    public function isLocal(): bool
    {
        return false;
    }

    public function endpointRequired(): bool
    {
        return $this->requiresEndpoint;
    }

    public function customize(FilesystemAdapter $filesystem, StorageAccount $account): FilesystemAdapter
    {
        return $filesystem;
    }
}
