<?php

use App\Models\Content\ContentCarousel;

$uploads = require __DIR__.'/../app/Libs/File/config.php';
$uploads['default_disk'] = env('UPLOAD_DISK', $uploads['default_disk']);
$uploads['max_upload_mb'] = (float) env('UPLOAD_MAX_SIZE_KB', $uploads['max_upload_mb'] * 1024) / 1024;

return [
    'uploads' => $uploads,
    'reference_models' => [ContentCarousel::class],
];
