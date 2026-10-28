<?php

namespace App\Models\Content;

use App\Models\Concerns\HasLocalizedAttributes;
use Database\Factories\Content\ContentAnnouncementFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContentAnnouncement extends Model
{
    /** @use HasFactory<ContentAnnouncementFactory> */
    use HasFactory;

    use HasLocalizedAttributes;

    protected $fillable = ['title_i18n', 'content_i18n', 'published_at', 'sort', 'active'];

    protected $appends = ['title', 'content'];

    protected function casts(): array
    {
        return ['title_i18n' => 'array', 'content_i18n' => 'array', 'published_at' => 'datetime', 'active' => 'boolean'];
    }

    protected function title(): Attribute
    {
        return Attribute::get(fn (): string => $this->localizedValue('title'));
    }

    protected function content(): Attribute
    {
        return Attribute::get(fn (): string => $this->localizedValue('content'));
    }
}
