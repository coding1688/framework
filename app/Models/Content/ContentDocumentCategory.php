<?php

namespace App\Models\Content;

use App\Models\Concerns\HasLocalizedAttributes;
use Database\Factories\Content\ContentDocumentCategoryFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContentDocumentCategory extends Model
{
    /** @use HasFactory<ContentDocumentCategoryFactory> */
    use HasFactory;

    use HasLocalizedAttributes;

    protected $fillable = ['type', 'parent_id', 'slug', 'name_i18n', 'description_i18n', 'sort', 'active'];

    protected $appends = ['name', 'description'];

    protected function casts(): array
    {
        return ['name_i18n' => 'array', 'description_i18n' => 'array', 'active' => 'boolean'];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ContentDocument::class, 'category_id');
    }

    protected function name(): Attribute
    {
        return Attribute::get(fn (): string => $this->localizedValue('name'));
    }

    protected function description(): Attribute
    {
        return Attribute::get(fn (): string => $this->localizedValue('description'));
    }
}
