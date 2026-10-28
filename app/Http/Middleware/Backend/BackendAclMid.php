<?php

namespace App\Http\Middleware\Backend;

use App\Models\Admin\AdminMenu;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class BackendAclMid
{
    public function handle(Request $request, Closure $next): Response
    {
        $actor = Auth::guard('backend')->user();
        abort_unless($actor, 403, __('backend/common.forbidden'));
        if ($actor->isBoard()) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();
        abort_unless(is_string($routeName) && $routeName !== '', 403, __('backend/common.forbidden'));
        $registered = AdminMenu::whereNull('navigation_target_id')->whereIn('type', ['page', 'button']);
        $hasOwnPermission = (clone $registered)->where('permission', $routeName)->exists();
        $menus = $registered->where('active', true)
            ->where(function ($query) use ($hasOwnPermission, $routeName): void {
                if ($hasOwnPermission) {
                    $query->where('permission', $routeName);
                } else {
                    $query->whereJsonContains('route_names', $routeName);
                }
            })->get();
        abort_unless($menus->count() === 1, 403, __('backend/common.forbidden'));
        $menu = $menus->first();
        abort_unless($actor->permits($menu->permission), 403, __('backend/common.forbidden'));

        return $next($request);
    }
}
