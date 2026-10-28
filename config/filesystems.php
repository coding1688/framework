<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => true,
            'report' => true,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage',
            'visibility' => 'public',
            'throw' => true,
            'report' => true,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'visibility' => env('AWS_UPLOAD_VISIBILITY', 'private'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => true,
            'report' => true,
        ],

        'r2' => [
            'driver' => 's3',
            'key' => env('R2_ACCESS_KEY_ID'),
            'secret' => env('R2_SECRET_ACCESS_KEY'),
            'region' => env('R2_REGION', 'auto'),
            'bucket' => env('R2_BUCKET'),
            'visibility' => env('R2_UPLOAD_VISIBILITY', 'private'),
            'url' => env('R2_URL'),
            'endpoint' => env('R2_ENDPOINT'),
            'use_path_style_endpoint' => env('R2_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => true,
            'report' => true,
        ],

        'oss' => [
            'driver' => 's3',
            'key' => env('OSS_ACCESS_KEY_ID'),
            'secret' => env('OSS_SECRET_ACCESS_KEY'),
            'region' => env('OSS_REGION'),
            'bucket' => env('OSS_BUCKET'),
            'visibility' => env('OSS_UPLOAD_VISIBILITY', 'private'),
            'url' => env('OSS_URL'),
            'endpoint' => env('OSS_ENDPOINT'),
            'use_path_style_endpoint' => env('OSS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => true,
            'report' => true,
        ],

        'gcs' => [
            'driver' => 's3',
            'key' => env('GCS_ACCESS_KEY_ID'),
            'secret' => env('GCS_SECRET_ACCESS_KEY'),
            'region' => env('GCS_REGION', 'auto'),
            'bucket' => env('GCS_BUCKET'),
            'visibility' => env('GCS_UPLOAD_VISIBILITY', 'private'),
            'url' => env('GCS_URL'),
            'endpoint' => env('GCS_ENDPOINT', 'https://storage.googleapis.com'),
            'use_path_style_endpoint' => env('GCS_USE_PATH_STYLE_ENDPOINT', true),
            'throw' => true,
            'report' => true,
        ],

        's3_compatible' => [
            'driver' => 's3',
            'key' => env('OBJECT_STORAGE_ACCESS_KEY_ID'),
            'secret' => env('OBJECT_STORAGE_SECRET_ACCESS_KEY'),
            'region' => env('OBJECT_STORAGE_REGION', 'auto'),
            'bucket' => env('OBJECT_STORAGE_BUCKET'),
            'visibility' => env('OBJECT_STORAGE_UPLOAD_VISIBILITY', 'private'),
            'url' => env('OBJECT_STORAGE_URL'),
            'endpoint' => env('OBJECT_STORAGE_ENDPOINT'),
            'use_path_style_endpoint' => env('OBJECT_STORAGE_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => true,
            'report' => true,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
