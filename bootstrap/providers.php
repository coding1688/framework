<?php

use App\Providers\AiServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\ContentServiceProvider;
use App\Providers\FieldOptionServiceProvider;
use App\Providers\FileServiceProvider;
use App\Providers\MailServiceProvider;
use App\Providers\MemberServiceProvider;

return [
    AiServiceProvider::class,
    AppServiceProvider::class,
    ContentServiceProvider::class,
    FieldOptionServiceProvider::class,
    FileServiceProvider::class,
    MailServiceProvider::class,
    MemberServiceProvider::class,
];
