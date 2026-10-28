<?php

namespace App\Libs\File\Concerns;

use App\Libs\File\FileReferences;
use App\Models\Common\FileReference;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\DB;

trait HasFileReferences
{
    /** @return list<string> */
    abstract public function fileReferenceFields(): array;

    /** @return array<string, string> Reference field => file ID attribute. */
    public function fileReferenceIdFields(): array
    {
        return [];
    }

    public static function bootHasFileReferences(): void
    {
        static::saved(function ($model): void {
            $fields = array_map(fn (string $field): string => $model->fileReferenceIdFields()[$field] ?? $field, $model->fileReferenceFields());
            if ($model->wasRecentlyCreated || $model->wasChanged($fields)) {
                app(FileReferences::class)->syncModel($model);
            }
        });
        static::deleted(fn ($model) => $model->fileReferences()->delete());
    }

    public function save(array $options = []): bool
    {
        return DB::transaction(fn (): bool => parent::save($options));
    }

    public function delete(): ?bool
    {
        return DB::transaction(fn (): ?bool => parent::delete());
    }

    public function fileReferences(): MorphMany
    {
        return $this->morphMany(FileReference::class, 'reference');
    }
}
