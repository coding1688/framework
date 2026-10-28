<?php

namespace App\Services\Backend;

use App\Models\Admin\AdminMenu;
use App\Services\Backend\Common\MenuNavigation;
use Illuminate\Support\Facades\Auth;

class PageService extends BaseService
{
    public function page(string $section): array
    {
        return ['view' => 'backend.spa', 'data' => ['section' => $section]];
    }

    public function menuPage(string $path): array
    {
        $path = '/'.$path;
        abort_unless(AdminMenu::where('type', 'page')->where('path', $path)->exists(), 404);
        $contains = function (array $nodes) use (&$contains, $path): bool {
            foreach ($nodes as $node) {
                if ($node['path'] === $path || $contains($node['children'])) {
                    return true;
                }
            }

            return false;
        };
        abort_unless($contains((new MenuNavigation)->for(Auth::guard('backend')->user())), 403);

        return $this->page('menu');
    }
}
