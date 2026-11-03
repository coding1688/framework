<?php

namespace App\Models\Content;

use App\Models\Concerns\HasLocalizedAttributes;
use Database\Factories\Content\ContentDocumentFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentDocument extends Model
{
    /** @use HasFactory<ContentDocumentFactory> */
    use HasFactory;

    use HasLocalizedAttributes;

    protected $fillable = ['category_id', 'type', 'slug', 'title_i18n', 'summary_i18n', 'content_i18n', 'published_at', 'sort', 'active'];

    protected $appends = ['title', 'summary', 'content'];

    protected function casts(): array
    {
        return ['title_i18n' => 'array', 'summary_i18n' => 'array', 'content_i18n' => 'array', 'published_at' => 'datetime', 'active' => 'boolean'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ContentDocumentCategory::class, 'category_id');
    }

    protected function title(): Attribute
    {
        return Attribute::get(fn (): string => $this->localizedValue('title'));
    }

    protected function summary(): Attribute
    {
        return Attribute::get(fn (): string => $this->localizedValue('summary'));
    }

    protected function content(): Attribute
    {
        return Attribute::get(fn (): string => $this->localizedValue('content'));
    }
}
