<?php

namespace App\Models\Common;

use App\Libs\File\Constants\FileType;
use App\Libs\File\FileStorage;
use App\Models\Admin\AdminUser;
use Database\Factories\Common\FileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class File extends Model
{
    /** @use HasFactory<FileFactory> */
    use HasFactory;

    protected $fillable = ['disk', 'type', 'path', 'original_name', 'extension', 'mime_type', 'size', 'visibility', 'checksum', 'width', 'height', 'metadata', 'created_by'];

    protected function casts(): array
    {
        return ['type' => FileType::class, 'size' => 'integer', 'width' => 'integer', 'height' => 'integer', 'metadata' => 'array'];
    }

    protected static function booted(): void
    {
        static::deleting(function (self $file): void {
            if ($file->references()->exists()) {
                throw ValidationException::withMessages(['file' => __('common/file/const.in_use')]);
            }
        });
    }

    public function references(): HasMany
    {
        return $this->hasMany(FileReference::class, 'file_id');
    }

    public function replicas(): HasMany
    {
        return $this->hasMany(FileReplica::class, 'file_id');
    }

    protected static function newFactory(): FileFactory
    {
        return FileFactory::new();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'created_by');
    }

    public function publicUrl(): ?string
    {
        return app(FileStorage::class)->url($this->disk, $this->path, $this->visibility);
    }
}
