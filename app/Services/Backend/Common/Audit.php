<?php

namespace App\Services\Backend\Common;

use App\Models\Admin\AdminLog;
use Illuminate\Support\Facades\Auth;

class Audit
{
    public static function record(string $action, ?string $subject = null, array $changes = []): void
    {
        $admin = Auth::guard('backend')->user();
        AdminLog::create([
            'administrator_id' => $admin?->id, 'actor' => $admin?->name ?? 'guest',
            'action' => $action, 'subject' => $subject, 'ip' => request()->ip(),
            'user_agent' => mb_substr(request()->userAgent() ?? '', 0, 512),
            'changes' => $changes, 'created_at' => now(),
        ]);
    }
}
