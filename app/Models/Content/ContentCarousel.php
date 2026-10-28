<?php

namespace App\Models\Content;

use App\Libs\File\Concerns\HasFileReferences;
use App\Models\Common\File;
use App\Models\Concerns\HasLocalizedAttributes;
use Database\Factories\Content\ContentCarouselFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentCarousel extends Model
{
    /** @use HasFactory<ContentCarouselFactory> */
    use HasFactory;

    use HasFileReferences;
    use HasLocalizedAttributes;

    protected $fillable = ['title_i18n', 'image_id', 'link_url', 'sort', 'active'];

    protected $appends = ['title'];

    protected function casts(): array
    {
        return ['title_i18n' => 'array', 'active' => 'boolean'];
    }

    protected function title(): Attribute
    {
        return Attribute::get(fn (): string => $this->localizedValue('title'));
    }

    public function image(): BelongsTo
    {
        return $this->belongsTo(File::class, 'image_id');
    }

    public function fileReferenceFields(): array
    {
        return ['image'];
    }

    public function fileReferenceIdFields(): array
    {
        return ['image' => 'image_id'];
    }
}
