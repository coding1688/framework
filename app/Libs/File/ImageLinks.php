<?php

namespace App\Libs\File;

use App\Models\Common\File;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ImageLinks
{
    /** @return array{cover_file_id: ?int, external_cover_url: ?string} */
    public function cover(?string $url): array
    {
        $url = trim((string) $url);
        $id = $url === '' ? null : (app(FileReferences::class)->idsFromUrls($url)[0] ?? null);

        return ['cover_file_id' => $id, 'external_cover_url' => $id !== null || $url === '' ? null : $url];
    }

    /** @return list<array{file_id: int}|array{external_url: string}> */
    public function gallery(?array $images): array
    {
        $result = [];
        foreach ($images ?? [] as $image) {
            if (is_string($image)) {
                $cover = $this->cover($image);
                $image = $cover['cover_file_id'] !== null ? ['file_id' => $cover['cover_file_id']] : ['external_url' => $cover['external_cover_url']];
            }
            if (! empty($image['file_id'])) {
                $image = ['file_id' => (int) $image['file_id']];
            } elseif (! empty($image['external_url'])) {
                $cover = $this->cover($image['external_url']);
                $image = $cover['cover_file_id'] !== null ? ['file_id' => $cover['cover_file_id']] : ['external_url' => $cover['external_cover_url']];
            } else {
                continue;
            }
            $result[json_encode($image)] = $image;
        }

        return array_values($result);
    }

    /** @param list<int> $ids */
    public function validatePublicImages(array $ids, string $field): void
    {
        if ($ids === []) {
            return;
        }
        $files = File::whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get();
        if ($files->count() !== count(array_unique($ids)) || $files->contains(fn (File $file): bool => $file->visibility !== 'public' || ! str_starts_with($file->mime_type, 'image/'))) {
            throw ValidationException::withMessages([$field => __('validation.exists', ['attribute' => $field])]);
        }
    }

    /** @return Collection<int, File> */
    public function files(array $images): Collection
    {
        return File::whereIn('id', array_column($images, 'file_id'))->get()->keyBy('id');
    }

    /** @return list<array<string, mixed>> */
    public function presentation(array $images): array
    {
        $files = $this->files($images);

        return array_map(fn (array $image): array => $image + ['url' => isset($image['file_id']) ? $files->get($image['file_id'])?->publicUrl() : $image['external_url']], $images);
    }
}
