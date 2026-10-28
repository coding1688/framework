<?php

namespace App\Services\Backend\Common;

use App\Attributes\MenuAction;
use App\Attributes\MenuPage;
use App\Http\Controllers\Backend\BaseController;
use App\Models\Admin\AdminMenu;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use ReflectionClass;

class MenuDiscovery
{
    /** @return array{created: int, updated: int} */
    public function sync(?string $permission = null): array
    {
        $definitions = $this->definitions($permission);
        if ($permission !== null) {
            $definitions = array_filter($definitions, fn (array $page): bool => $page['permission'] === $permission);
            $this->require(count($definitions) === 1, $permission);
        }

        return DB::transaction(function () use ($definitions): array {
            $existing = AdminMenu::query()->orderBy('id')->lockForUpdate()->get()->keyBy('permission');
            $counts = ['created' => 0, 'updated' => 0];
            foreach ($definitions as $page) {
                $parent = $existing->get($page['parent']);
                $this->require($parent !== null && $parent->type === 'directory', $page['parent']);
                $actions = $page['actions'];
                unset($page['parent'], $page['actions']);
                $model = $this->upsert($page + ['parent_id' => $parent->id], $counts);
                foreach ($actions as $action) {
                    $this->upsert($action + ['parent_id' => $model->id], $counts);
                }
            }

            return $counts;
        });
    }

    /** @return array{created: int, updated: int} */
    public function syncActions(AdminMenu $menu): array
    {
        if ($menu->navigation_target_id !== null) {
            return ['created' => 0, 'updated' => 0];
        }

        $definitions = $this->definitions($menu->permission);
        $counts = ['created' => 0, 'updated' => 0];
        if ($definitions === []) {
            return $counts;
        }
        $this->require(count($definitions) === 1, $menu->permission);

        return DB::transaction(function () use ($menu, $definitions, $counts): array {
            AdminMenu::query()->orderBy('id')->lockForUpdate()->get();
            $page = $definitions[0];
            $actions = $page['actions'];
            unset($page['parent'], $page['actions']);
            $this->upsert($page + ['parent_id' => $menu->parent_id], $counts);
            foreach ($actions as $action) {
                $this->upsert($action + ['parent_id' => $menu->id], $counts);
            }

            return $counts;
        });
    }

    /** @return list<array<string, mixed>> */
    private function definitions(?string $permission = null): array
    {
        $pages = [];
        $claims = [];
        $routes = collect(Route::getRoutes()->getRoutes())->filter(fn ($route): bool => $route->getDomain() === config('platform.backend_domain') && in_array('backend.permission', $route->gatherMiddleware(), true));
        foreach ($routes as $route) {
            $class = $route->getControllerClass();
            if (! $class || ! is_subclass_of($class, BaseController::class)) {
                continue;
            }
            $reflection = new ReflectionClass($class);
            $attributes = $reflection->getAttributes(MenuPage::class);
            if ($attributes === []) {
                continue;
            }
            $page = $attributes[0]->newInstance();
            if ($permission !== null && $page->permission !== $permission) {
                continue;
            }
            if (! isset($pages[$class])) {
                $this->claim($claims, $page->permission);
                $data = ['permission' => $page->permission, 'name_i18n' => array_intersect_key($page->name, config('platform.locales')), 'path' => $page->path, 'component' => $page->component, 'icon' => $page->icon, 'sort' => $page->sort, 'navigable' => $page->navigable];
                $this->validate($data, true);
                $pages[$class] = $data + ['type' => 'page', 'parent' => $page->parent, 'actions' => []];
            }
            $method = $reflection->getMethod($route->getActionMethod());
            $attributes = $method->getAttributes(MenuAction::class);
            if ($attributes === []) {
                continue;
            }
            $action = $attributes[0]->newInstance();
            $name = $route->getName();
            $this->require($name === $page->permission.'.'.$method->name, $class.'::'.$method->name);
            $this->claim($claims, $name);
            $data = ['permission' => $name, 'name_i18n' => array_intersect_key($action->name, config('platform.locales')), 'sort' => $action->sort];
            $this->validate($data, false);
            $pages[$class]['actions'][] = $data + ['type' => 'button', 'route_names' => []];
        }

        return array_values($pages);
    }

    /** @param array<string, mixed> $data */
    private function validate(array $data, bool $page): void
    {
        Validator::make($data, [
            'permission' => ['required', 'string', 'max:128', 'regex:/^[a-z][a-zA-Z0-9_.-]+$/'],
            'name_i18n' => ['required', 'array:'.implode(',', array_keys(config('platform.locales')))],
            'name_i18n.en' => ['required', 'string', 'max:128'],
            'name_i18n.*' => ['nullable', 'string', 'max:128'],
            'sort' => ['required', 'integer', 'min:0', 'max:99999'],
            ...($page ? [
                'path' => ['required', 'string', 'max:256', 'regex:#^/admin/[a-zA-Z0-9/_-]+$#'],
                'component' => ['required', 'string', 'max:128', 'regex:#^(?:[a-zA-Z0-9]+/)+List$#'],
                'icon' => ['required', 'string', 'max:64'],
                'navigable' => ['required', 'boolean'],
            ] : []),
        ])->validate();
        if ($page) {
            $this->require(is_file(resource_path('js/backend/pages/'.$data['component'].'.vue')), $data['component']);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array{created: int, updated: int}  $counts
     */
    private function upsert(array $data, array &$counts): AdminMenu
    {
        $model = AdminMenu::where('permission', $data['permission'])->first();
        if ($model) {
            $this->require($model->type === $data['type'], $data['permission']);
            if ($model->type === 'button') {
                $this->require($model->parent_id === $data['parent_id'], $data['permission']);
            } else {
                $this->require($model->path === $data['path'], $data['permission']);
            }
            $legacyIcons = [
                'user-settings-line' => 'group-line',
                'tools-line' => 'list-settings-line',
                'settings-3-line' => 'equalizer-line',
                'database-2-line' => 'hard-drive-2-line',
                'file-cloud-line' => 'folder-cloud-line',
            ];
            $icon = $data['icon'] ?? null;
            $model->forceFill([
                'name_i18n' => array_replace($data['name_i18n'], supportedTranslations($model->name_i18n)),
                'icon' => $icon && (! $model->icon || ($legacyIcons[$model->icon] ?? null) === $icon) ? $icon : $model->icon,
                'component' => $data['component'] ?? $model->component,
                'navigable' => $data['navigable'] ?? $model->navigable,
                'route_names' => [],
                'is_system' => true,
            ]);
            if ($model->isDirty()) {
                $model->save();
                $counts['updated']++;
            }

            return $model;
        }
        if ($data['type'] === 'page') {
            $this->require(! AdminMenu::where('path', $data['path'])->exists(), $data['path']);
        }
        $model = new AdminMenu;
        $model->forceFill($data + ['route_names' => [], 'is_system' => true, 'active' => true])->save();
        $counts['created']++;

        return $model;
    }

    /** @param array<string, bool> $claims */
    private function claim(array &$claims, string $name): void
    {
        $this->require(! isset($claims[$name]), $name);
        $claims[$name] = true;
    }

    private function require(bool $condition, string $value): void
    {
        if (! $condition) {
            throw ValidationException::withMessages(['menus' => __('backend/menu.discovery_conflict', ['name' => $value])]);
        }
    }
}
