<?php

return [
    'default_disk' => 'public',
    'enabled_disks' => ['public', 'local'],
    'max_upload_mb' => 32,
    'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'zip', 'mp4', 'webm', 'mov', 'mp3', 'wav', 'ogg'],
    'limits' => [
        'hard_max_upload_mb' => 1024,
        'forbidden_extensions' => ['php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'pht', 'phar', 'cgi', 'pl', 'sh', 'exe', 'bat', 'cmd', 'com', 'msi', 'dll', 'html', 'htm', 'js'],
    ],
    'providers' => [
        'public' => ['provider' => 'local', 'driver' => 'local', 'visibility' => 'public'],
        'local' => ['provider' => 'local_private', 'driver' => 'local', 'visibility' => 'private'],
        's3' => ['provider' => 'aws', 'driver' => 's3', 'visibility' => 'private'],
        'r2' => ['provider' => 'cloudflare', 'driver' => 's3', 'visibility' => 'private'],
        'oss' => ['provider' => 'aliyun', 'driver' => 's3', 'visibility' => 'private'],
        'gcs' => ['provider' => 'google', 'driver' => 's3', 'visibility' => 'private'],
        's3_compatible' => ['provider' => 's3_compatible', 'driver' => 's3', 'visibility' => 'private'],
    ],
];
