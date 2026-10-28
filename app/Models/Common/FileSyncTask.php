<?php

namespace App\Models\Common;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FileSyncTask extends Model
{
    use HasFactory;

    protected $fillable = ['source_disk', 'target_disk', 'status', 'last_file_id', 'max_file_id', 'total', 'copied', 'skipped', 'failed', 'errors', 'started_at', 'finished_at', 'created_by'];

    protected function casts(): array
    {
        return ['errors' => 'array', 'started_at' => 'datetime', 'finished_at' => 'datetime'];
    }
}
