<?php

namespace App\Services\Backend\File;

use App\Jobs\FileSyncBatch;
use App\Libs\File\FileReferenceOwnerRegistry;
use App\Libs\File\StorageRegistry;
use App\Libs\File\StorageSynchronizer;
use App\Libs\File\UploadConfiguration;
use App\Models\Common\File;
use App\Models\Common\FileConfiguration;
use App\Models\Common\FileReference;
use App\Models\Common\FileReplica;
use App\Models\Common\FileStorageAccount;
use App\Models\Common\FileSyncTask;
use App\Services\Backend\BaseService;
use App\Services\Backend\Common\Audit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FileStorageAccountService extends BaseService
{
    public function __construct(private readonly UploadConfiguration $uploads, private readonly StorageRegistry $storages, private readonly StorageSynchronizer $synchronizer, private readonly FileReferenceOwnerRegistry $referenceOwners) {}

    public function list(): Collection
    {
        return FileStorageAccount::orderBy('id')->get();
    }

    /** @return list<string> */
    public function providerOptions(): array
    {
        return $this->storages->providerOptions();
    }

    /** @return list<string> */
    public function localProviderOptions(): array
    {
        return $this->storages->localProviderKeys();
    }

    public function settings(): array
    {
        return [
            'default_disk' => $this->uploads->defaultDisk(), 'max_upload_mb' => $this->uploads->maxSizeKb() / 1024,
            'allowed_extensions' => $this->uploads->allowedExtensions(), 'extension_options' => $this->uploads->defaults()['allowed_extensions'],
            'hard_max_upload_mb' => $this->uploads->defaults()['limits']['hard_max_upload_mb'],
        ];
    }

    public function saveSettings(array $validated): array
    {
        DB::transaction(function () use ($validated): void {
            FileStorageAccount::orderBy('id')->lockForUpdate()->get();
            if (($validated['default_disk'] ?? null) !== null && ! in_array($validated['default_disk'], $this->uploads->enabledDisks(), true)) {
                $this->reject('default_disk', 'storage_not_ready');
            }
            FileConfiguration::updateOrCreate(['id' => 1], $validated);
            Audit::record('file.storage.saveSettings', '1', $validated);
        });

        return $this->settings();
    }

    public function create(array $validated): FileStorageAccount
    {
        return $this->save(null, $validated);
    }

    public function update(int $id, array $validated): FileStorageAccount
    {
        return $this->save($id, $validated);
    }

    private function save(?int $id, array $validated): FileStorageAccount
    {
        return DB::transaction(function () use ($id, $validated): FileStorageAccount {
            FileStorageAccount::orderBy('id')->lockForUpdate()->get();
            $model = $id ? FileStorageAccount::findOrFail($id) : new FileStorageAccount;
            if ($id && ($model->disk !== $validated['disk'] || $model->provider !== $validated['provider'])) {
                $this->reject('disk', 'immutable_disk');
            }
            if (! $id && array_key_exists($validated['disk'], $this->uploads->defaults()['providers'])) {
                $this->reject('disk', 'reserved_disk');
            }
            $configuration = $validated['configuration'];
            $credentials = $model->credentials ?? [];
            foreach (['access_key' => 'key', 'secret_key' => 'secret'] as $input => $key) {
                if (! empty($validated[$input])) {
                    $credentials[$key] = $validated[$input];
                }
            }
            unset($validated['access_key'], $validated['secret_key']);
            if (in_array($model->disk, ['public', 'local'], true)) {
                $configuration = array_replace($model->configuration, ['url' => $configuration['url'] ?? null]);
                $validated['visibility'] = $model->visibility;
            }
            if ($id && $this->inUse($model->disk)) {
                foreach (['directory', 'bucket', 'endpoint'] as $key) {
                    if (($model->configuration[$key] ?? null) !== ($configuration[$key] ?? null)) {
                        $this->reject('configuration.'.$key, 'storage_in_use');
                    }
                }
                if (($model->configuration['url'] ?? null) !== ($configuration['url'] ?? null)) {
                    if ($this->activeTasks($model->disk)->exists()) {
                        $this->reject('configuration.url', 'sync_busy');
                    }
                    if ($this->hasUrlReferences($model->disk)) {
                        $this->reject('configuration.url', 'legacy_url_references');
                    }
                }
                if ($model->visibility !== $validated['visibility']) {
                    $this->reject('visibility', 'storage_in_use');
                }
            }
            if ($id && ! $validated['enabled'] && $this->activeTasks($model->disk)->exists()) {
                $this->reject('enabled', 'sync_busy');
            }
            if ($id && ! $validated['enabled'] && FileConfiguration::find(1)?->default_disk === $model->disk) {
                $this->reject('enabled', 'default_storage');
            }
            $local = in_array($validated['provider'], $this->storages->localProviderKeys(), true);
            if ($local && ! in_array($validated['disk'], ['public', 'local'], true) && empty($configuration['directory'])) {
                $this->reject('configuration.directory', 'directory_required');
            }
            if ($local && ! in_array($validated['disk'], ['public', 'local'], true)) {
                $root = trim($configuration['directory'], '/');
                $parent = storage_path('app/'.$root);
                while (! file_exists($parent) && ! is_link($parent)) {
                    $parent = dirname($parent);
                }
                $baseDirectory = realpath(storage_path('app'));
                $resolvedParent = realpath($parent);
                if ($baseDirectory === false || $resolvedParent === false || ($resolvedParent !== $baseDirectory && ! str_starts_with($resolvedParent, $baseDirectory.DIRECTORY_SEPARATOR))) {
                    $this->reject('configuration.directory', 'directory_required');
                }
                foreach (FileStorageAccount::whereIn('provider', $this->storages->localProviderKeys())->where('id', '!=', $model->id ?? 0)->get() as $other) {
                    $otherRoot = $other->configuration['directory'] ?? ($other->disk === 'public' ? 'public' : 'private');
                    if ($root === $otherRoot || str_starts_with($root.'/', $otherRoot.'/') || str_starts_with($otherRoot.'/', $root.'/')) {
                        $this->reject('configuration.directory', 'overlapping_directory');
                    }
                }
            }
            if (! $local && ! empty($configuration['bucket'])) {
                foreach (FileStorageAccount::whereNotIn('provider', $this->storages->localProviderKeys())->where('id', '!=', $model->id ?? 0)->get() as $other) {
                    if (($other->configuration['bucket'] ?? null) === $configuration['bucket']
                        && rtrim((string) ($other->configuration['endpoint'] ?? ''), '/') === rtrim((string) ($configuration['endpoint'] ?? ''), '/')
                        && ($other->configuration['region'] ?? 'auto') === ($configuration['region'] ?? 'auto')) {
                        $this->reject('configuration.bucket', 'duplicate_bucket');
                    }
                }
            }
            if ($validated['enabled'] && ! $local) {
                if (empty($configuration['bucket']) || empty($credentials['key']) || empty($credentials['secret']) || ($this->storages->endpointRequired($validated['provider']) && empty($configuration['endpoint']))) {
                    $this->reject('configuration', 'storage_not_ready');
                }
            }
            if ($validated['enabled'] && $validated['visibility'] === 'public' && ! in_array($validated['disk'], ['public'], true) && empty($configuration['url'])) {
                $this->reject('configuration.url', 'public_url_required');
            }
            $model->fill($validated + ['credentials' => $credentials]);
            $model->configuration = $configuration;
            $model->save();
            Audit::record('file.storage.'.($id ? 'update' : 'create'), (string) $model->id, $model->only(['disk', 'provider', 'enabled', 'visibility']));

            return $model;
        });
    }

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id): void {
            $model = FileStorageAccount::lockForUpdate()->findOrFail($id);
            if (in_array($model->disk, ['public', 'local'], true) || $this->inUse($model->disk) || FileConfiguration::find(1)?->default_disk === $model->disk) {
                $this->reject('storage', 'storage_in_use');
            }
            Audit::record('file.storage.delete', (string) $id, ['disk' => $model->disk]);
            $model->delete();
        });
    }

    public function tasks(): Collection
    {
        return FileSyncTask::latest('id')->limit(30)->get();
    }

    public function sync(array $validated): FileSyncTask
    {
        return DB::transaction(function () use ($validated): FileSyncTask {
            FileStorageAccount::orderBy('id')->lockForUpdate()->get();
            $source = $validated['source_disk'];
            $target = $validated['target_disk'];
            if ($this->activeTasks($source)->exists() || $this->activeTasks($target)->exists()) {
                $this->reject('target_disk', 'sync_busy');
            }
            $providers = $this->storages->providers();
            if (! ($providers[$source]['enabled'] ?? false) || ! ($providers[$target]['enabled'] ?? false)) {
                $this->reject('target_disk', 'storage_not_ready');
            }
            if ($this->synchronizer->files($source)->whereNotIn('visibility', $providers[$target]['visibilities'])->exists()) {
                $this->reject('target_disk', 'visibility_mismatch');
            }
            $query = $this->synchronizer->files($source);
            $maximum = (int) $query->max('id');
            $task = FileSyncTask::create($validated + [
                'max_file_id' => $maximum, 'total' => $this->synchronizer->files($source)->where('id', '<=', $maximum)->count(),
                'created_by' => Auth::guard('backend')->id(),
            ]);
            FileSyncBatch::dispatch($task->id)->onConnection('file-sync')->onQueue('file-sync')->afterCommit();
            Audit::record('file.storage.sync', (string) $task->id, $validated);

            return $task;
        });
    }

    public function retry(int $id): FileSyncTask
    {
        return DB::transaction(function () use ($id): FileSyncTask {
            FileStorageAccount::orderBy('id')->lockForUpdate()->get();
            $task = FileSyncTask::lockForUpdate()->findOrFail($id);
            if ($task->status !== 'failed' || $this->activeTasks($task->source_disk)->exists() || $this->activeTasks($task->target_disk)->exists()) {
                $this->reject('task', 'sync_busy');
            }
            $providers = $this->storages->providers();
            if (! ($providers[$task->source_disk]['enabled'] ?? false) || ! ($providers[$task->target_disk]['enabled'] ?? false)) {
                $this->reject('task', 'storage_not_ready');
            }
            $task->fill(['status' => 'pending', 'last_file_id' => 0, 'copied' => 0, 'skipped' => 0, 'failed' => 0, 'errors' => [], 'started_at' => null, 'finished_at' => null])->save();
            FileSyncBatch::dispatch($id)->onConnection('file-sync')->onQueue('file-sync')->afterCommit();
            Audit::record('file.storage.retry', (string) $id);

            return $task;
        });
    }

    private function inUse(string $disk): bool
    {
        return File::where('disk', $disk)->exists() || FileReplica::where('disk', $disk)->exists() || $this->activeTasks($disk)->exists();
    }

    private function hasUrlReferences(string $disk): bool
    {
        $idConsumers = collect($this->referenceOwners->classes())->map(fn (string $class) => new $class)
            ->filter(fn ($owner): bool => $owner->fileReferenceIdFields() !== []);

        return FileReference::whereIn('file_id', File::where('disk', $disk)->select('id'))
            ->when($idConsumers->isNotEmpty(), fn ($query) => $query->whereNot(function ($query) use ($idConsumers): void {
                foreach ($idConsumers as $owner) {
                    $query->orWhere(fn ($query) => $query->where('reference_type', $owner->getMorphClass())->whereIn('field', array_keys($owner->fileReferenceIdFields())));
                }
            }))->exists();
    }

    private function activeTasks(string $disk): Builder
    {
        return FileSyncTask::whereIn('status', ['pending', 'running'])->where(fn ($query) => $query->where('source_disk', $disk)->orWhere('target_disk', $disk));
    }

    private function reject(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => __('common/file/storage.'.$message)]);
    }
}
