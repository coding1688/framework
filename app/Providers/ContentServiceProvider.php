<?php

namespace App\Providers;

use App\Libs\File\FileReferenceOwnerRegistry;
use App\Models\Content\ContentCarousel;
use Illuminate\Support\ServiceProvider;

class ContentServiceProvider extends ServiceProvider
{
    public function boot(FileReferenceOwnerRegistry $owners): void
    {
        $owners->register(ContentCarousel::class);
    }
}
