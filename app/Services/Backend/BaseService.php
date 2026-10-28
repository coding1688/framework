<?php

namespace App\Services\Backend;

use Illuminate\Support\Facades\Auth;

abstract class BaseService
{
    protected function authorize(string $permission, bool $boardOnly = false): void
    {
        $actor = Auth::guard('backend')->user();
        abort_unless($actor !== null && (! $boardOnly || $actor->isBoard()) && $actor->permits($permission), 403);
    }
}
