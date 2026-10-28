<?php

namespace App\Services\Backend\Content;

use App\Libs\File\Constants\FileType;
use App\Libs\File\FileManager;
use App\Libs\File\UploadConfiguration;
use App\Models\Common\File;
use App\Models\Content\ContentCarousel;
use App\Services\Backend\BaseService;
use App\Services\Backend\Common\Audit;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ContentCarouselService extends BaseService
{
    public function __construct(private readonly FileManager $files, private readonly UploadConfiguration $uploads) {}

    public function list(array $validated): LengthAwarePaginator
    {
        $query = ContentCarousel::with('image')->orderBy('sort')->orderByDesc('id');
        if ($q = $validated['q'] ?? null) {
            $query->where('title_i18n', 'like', '%'.$q.'%');
        }
        if (array_key_exists('active', $validated) && $validated['active'] !== null && $validated['active'] !== '') {
            $query->where('active', (bool) $validated['active']);
        }

        return $query->paginate(20, ['*'], 'page', (int) ($validated['page'] ?? 1));
    }

    public function detail(int $id): ContentCarousel
    {
        return ContentCarousel::with('image')->findOrFail($id);
    }

    public function options(): array
    {
        return [
            'allowed_extensions' => array_values(array_intersect($this->uploads->allowedExtensions(), ['jpg', 'jpeg', 'png', 'gif', 'webp'])),
            'max_size_kb' => $this->uploads->maxSizeKb(),
        ];
    }

    public function upload(array $validated): File
    {
        return $this->files->store(
            $validated['image'], $this->uploads->publicDisk(), FileType::System->value, 'public', Auth::guard('backend')->id(),
            function (Collection $records): void {
                Audit::record('content.carousel.upload', (string) $records->sole()->id);
            },
        );
    }

    public function create(array $validated): ContentCarousel
    {
        return DB::transaction(function () use ($validated): ContentCarousel {
            $model = ContentCarousel::create($validated);
            Audit::record('content.carousel.create', (string) $model->id);

            return $model->load('image');
        });
    }

    public function update(int $id, array $validated): ContentCarousel
    {
        return DB::transaction(function () use ($id, $validated): ContentCarousel {
            $model = ContentCarousel::lockForUpdate()->findOrFail($id);
            $model->update($validated);
            Audit::record('content.carousel.update', (string) $id);

            return $model->load('image');
        });
    }

    public function status(int $id, array $validated): ContentCarousel
    {
        return DB::transaction(function () use ($id, $validated): ContentCarousel {
            $model = ContentCarousel::lockForUpdate()->findOrFail($id);
            $model->update(['active' => $validated['active']]);
            Audit::record('content.carousel.status', (string) $id, ['active' => $model->active]);

            return $model->load('image');
        });
    }

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id): void {
            $model = ContentCarousel::lockForUpdate()->findOrFail($id);
            Audit::record('content.carousel.delete', (string) $id);
            $model->delete();
        });
    }
}
