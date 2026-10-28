<?php

namespace App\Models\Common;

use App\Models\Concerns\HasLocalizedAttributes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FileStorageAccount extends Model
{
    use HasFactory;
    use HasLocalizedAttributes;

    protected $table = 'file_storages';

    protected $fillable = ['disk', 'name_i18n', 'provider', 'visibility', 'enabled', 'configuration', 'credentials'];

    protected $hidden = ['credentials'];

    protected $appends = ['name'];

    protected function casts(): array
    {
        return ['name_i18n' => 'array', 'enabled' => 'boolean', 'configuration' => 'array', 'credentials' => 'encrypted:array'];
    }

    protected function name(): Attribute
    {
        return Attribute::get(fn (): string => $this->localizedValue('name'));
    }
}
