<?php

namespace App\Models\Common;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FileConfiguration extends Model
{
    use HasFactory;

    protected $fillable = ['id', 'default_disk', 'max_upload_mb', 'allowed_extensions'];

    protected function casts(): array
    {
        return ['max_upload_mb' => 'integer', 'allowed_extensions' => 'array'];
    }
}
