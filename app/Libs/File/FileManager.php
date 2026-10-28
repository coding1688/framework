<?php

namespace App\Libs\File;

use App\Libs\File\Constants\FileType;
use App\Models\Common\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

final class FileManager
{
    public function __construct(private readonly FileStorage $storage, private readonly UploadConfiguration $uploads) {}

    /**
     * @param  null|callable(Collection<int, File>): void  $afterCreated
     *
     * @throws Throwable
     */
    public function store(UploadedFile $file, string $disk, string $type, string $visibility, ?int $createdBy = null, ?callable $afterCreated = null): File
    {
        return $this->storeMany([$file], $disk, $type, $visibility, $createdBy, $afterCreated)->sole();
    }

    /**
     * @param  list<UploadedFile>  $files
     * @param  null|callable(Collection<int, File>): void  $afterCreated
     * @return Collection<int, File>
     *
     * @throws Throwable
     */
    public function storeMany(array $files, string $disk, string $type, string $visibility, ?int $createdBy = null, ?callable $afterCreated = null): Collection
    {
        if ($files === []) {
            throw ValidationException::withMessages(['files' => __('validation.required', ['attribute' => 'files'])]);
        }
        if (count($files) > 16) {
            throw ValidationException::withMessages(['files' => __('validation.max.array', ['attribute' => 'files', 'max' => 16])]);
        }
        $providers = $this->uploads->providers();
        if (! ($providers[$disk]['enabled'] ?? false)) {
            throw ValidationException::withMessages(['disk' => __('validation.in', ['attribute' => 'disk'])]);
        }
        if (! in_array($type, FileType::values(), true)) {
            throw ValidationException::withMessages(['type' => __('validation.in', ['attribute' => 'type'])]);
        }
        if (! in_array($visibility, $providers[$disk]['visibilities'], true)) {
            throw ValidationException::withMessages(['visibility' => __('validation.in', ['attribute' => 'visibility'])]);
        }

        $directory = 'uploads/'.$type.'/'.now()->format('Y/m/d');
        $writtenPaths = [];
        $records = collect();
        DB::beginTransaction();
        try {
            foreach ($files as $file) {
                $stored = $this->storage->store($file, $disk, $directory);
                $writtenPaths[] = $stored->path;
                $records->push(File::create([...$stored->toArray(),
                    'disk' => $disk,
                    'type' => $type,
                    'visibility' => $visibility,
                    'created_by' => $createdBy,
                ]));
            }
            if ($afterCreated !== null) {
                $afterCreated($records);
            }
            DB::commit();
        } catch (Throwable $exception) {
            DB::rollBack();
            foreach ($writtenPaths as $path) {
                $this->storage->delete($disk, $path);
            }
            throw $exception;
        }

        return $records;
    }
}
