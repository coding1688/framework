<?php

namespace App\Services\Backend\File;

use App\Libs\File\Constants\FileType;
use App\Libs\File\FileManager;
use App\Libs\File\FileStorage;
use App\Libs\File\UploadConfiguration;
use App\Models\Common\File;
use App\Services\Backend\BaseService;
use App\Services\Backend\Common\Audit;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class FileService extends BaseService
{
    public function __construct(private FileManager $files, private FileStorage $fileStorage, private UploadConfiguration $uploads) {}

    public function list(array $validated): LengthAwarePaginator
    {
        $query = File::with('uploader:id,name')->withCount('references')->latest('id');
        if ($q = $validated['q'] ?? null) {
            $query->where(fn ($query) => $query->where('original_name', 'like', '%'.$q.'%')->orWhere('path', 'like', '%'.$q.'%'));
        }
        if ($disk = $validated['disk'] ?? null) {
            $query->where('disk', $disk);
        }
        if ($type = $validated['type'] ?? null) {
            $query->where('type', $type);
        }
        if ($mime = $validated['mime'] ?? null) {
            $query->where('mime_type', 'like', $mime.'%');
        }

        return $query->paginate(20, ['*'], 'page', (int) ($validated['page'] ?? 1));
    }

    /** @return array{providers: array<string, array{provider: string, enabled: bool, visibilities: list<string>}>, default_disk: string, max_size_kb: int, max_files: int} */
    public function options(): array
    {
        return [
            'providers' => $this->uploads->providers(),
            'types' => FileType::options(),
            'default_disk' => $this->uploads->defaultDisk(),
            'allowed_extensions' => $this->uploads->allowedExtensions(),
            'max_size_kb' => $this->uploads->maxSizeKb(),
            'max_files' => 16,
        ];
    }

    public function detail(int $id): File
    {
        return File::with(['uploader:id,name', 'references', 'replicas'])->withCount('references')->findOrFail($id);
    }

    /**
     * @param  array{files: list<UploadedFile>, disk: string, type: string, visibility: string}  $validated
     * @return Collection<int, File>
     *
     * @throws Throwable
     */
    public function upload(array $validated): Collection
    {
        $this->guard('upload');
        $disk = $validated['disk'];
        $records = $this->files->storeMany(
            $validated['files'], $disk, $validated['type'], $validated['visibility'], Auth::guard('backend')->id(),
            function (Collection $records) use ($disk, $validated): void {
                Audit::record('file.item.upload', null, ['disk' => $disk, 'type' => $validated['type'], 'files' => $records->pluck('id')->all()]);
            },
        );

        return $records->each->load('uploader:id,name');
    }

    public function delete(int $id): void
    {
        $this->guard('delete');
        DB::transaction(function () use ($id): void {
            $model = File::lockForUpdate()->findOrFail($id);
            if ($model->references()->exists()) {
                throw ValidationException::withMessages(['file' => __('common/file/const.in_use')]);
            }
            foreach ($model->replicas as $replica) {
                $this->fileStorage->delete($replica->disk, $replica->path);
            }
            $this->fileStorage->delete($model->disk, $model->path);
            Audit::record('file.item.delete', (string) $id, ['disk' => $model->disk, 'path' => $model->path]);
            $model->delete();
        });
    }

    private function guard(string $action): void
    {
        $this->authorize('file.item.'.$action);
    }
}
