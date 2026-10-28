<?php

namespace App\Providers;

use App\Libs\File\FileReferenceOwnerRegistry;
use App\Models\Member\User;
use Illuminate\Support\ServiceProvider;

class MemberServiceProvider extends ServiceProvider
{
    public function boot(FileReferenceOwnerRegistry $owners): void
    {
        $owners->register(User::class);
    }
}
