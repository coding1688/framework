<?php

namespace App\Models\Common;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FileReplica extends Model
{
    use HasFactory;

    protected $fillable = ['file_id', 'disk', 'path', 'checksum'];
}
