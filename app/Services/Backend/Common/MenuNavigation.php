<?php

namespace App\Services\Backend\Common;

use App\Models\Admin\AdminMenu;
use App\Models\Admin\AdminUser;

class MenuNavigation
{
    public function for(AdminUser $administrator): array
    {
        $menus = AdminMenu::where('active', true)->whereIn('type', ['directory', 'page'])->orderBy('sort')->orderBy('id')->get()->keyBy('id');
        $children = $menus->groupBy(fn (AdminMenu $menu) => $menu->parent_id ?? 0);
        $permissions = $administrator->permissions();
        $all = $administrator->isBoard();
        $enabledAncestors = function (AdminMenu $menu) use ($menus): bool {
            $seen = [$menu->id];
            while ($menu->parent_id !== null) {
                $parent = $menus->get($menu->parent_id);
                if ($parent === null || in_array($parent->id, $seen, true)) {
                    return false;
                }
                $seen[] = $parent->id;
                $menu = $parent;
            }

            return true;
        };
        $visit = function (AdminMenu $menu, array $seen = []) use (&$visit, $menus, $children, $permissions, $all, $enabledAncestors): ?array {
            if (in_array($menu->id, $seen, true)) {
                return null;
            }
            $seen[] = $menu->id;
            $context = $menu->context ?? [];
            $branch = [];
            foreach ($children->get($menu->id, []) as $child) {
                if ($node = $visit($child, $seen)) {
                    $branch[] = $node;
                }
            }
            $target = $menu->navigation_target_id === null ? $menu : $menus->get($menu->navigation_target_id);
            $validTarget = $target !== null && $target->type === 'page' && $target->navigation_target_id === null && $enabledAncestors($target);
            $canOpen = $menu->type === 'page'
                && $menu->navigable
                && $validTarget
                && ($all || in_array($target->permission, $permissions, true) || in_array($target->permission.'.list', $permissions, true))
                && is_string($menu->path) && preg_match('#^/(?!/)[a-zA-Z0-9/_-]*$#D', $menu->path);
            if (! $canOpen && $branch === []) {
                return null;
            }
            $title = $menu->name;

            return ['id' => $menu->id, 'parent_id' => $menu->parent_id, 'route_name' => $menu->permission, 'canonical_permission' => $target?->permission, 'title' => $title, 'icon' => $menu->icon, 'component' => $canOpen ? $target->component : null, 'context' => $context, 'name' => $menu->name, 'type' => $menu->type, 'path' => $canOpen ? $menu->path : null, 'children' => $branch];
        };
        $tree = [];
        foreach ($children->get(0, []) as $root) {
            if ($node = $visit($root)) {
                $tree[] = $node;
            }
        }

        return $tree;
    }
}
