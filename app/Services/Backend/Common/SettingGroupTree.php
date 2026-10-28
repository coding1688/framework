<?php

namespace App\Services\Backend\Common;

use App\Models\System\SystemSettingGroup;

class SettingGroupTree
{
    /** @return array{groups: list<array<string, mixed>>, groups_tree: list<array<string, mixed>>} */
    public function options(): array
    {
        $models = SystemSettingGroup::orderBy('sort')->orderBy('id')->get()->keyBy('id');
        $rows = [];
        foreach ($models as $model) {
            $lineage = [];
            $seen = [];
            $current = $model;
            $active = true;
            while ($current !== null) {
                if (isset($seen[$current->id])) {
                    $active = false;
                    break;
                }
                $seen[$current->id] = true;
                $lineage[] = $current;
                $active = $active && $current->active;
                if ($current->parent_id === null) {
                    break;
                }
                $current = $models->get($current->parent_id);
                if ($current === null) {
                    $active = false;
                }
            }
            $lineage = array_reverse($lineage);
            $rows[$model->id] = [...$model->toArray(),
                'ancestor_ids' => array_map(fn (SystemSettingGroup $group): int => $group->id, array_slice($lineage, 0, -1)),
                'depth' => count($lineage) - 1,
                'path' => array_map(fn (SystemSettingGroup $group): string => $group->name, $lineage),
                'effective_active' => $active, 'disabled' => ! $active,
            ];
        }
        $children = collect($rows)->groupBy(fn (array $row): int => $row['parent_id'] ?? 0);
        $seen = [];
        $visit = function (array $row) use (&$visit, &$seen, $children): ?array {
            if (isset($seen[$row['id']])) {
                return null;
            }
            $seen[$row['id']] = true;
            $branch = [];
            foreach ($children->get($row['id'], []) as $child) {
                if (($node = $visit($child)) !== null) {
                    $branch[] = $node;
                }
            }

            return [...$row, 'children' => $branch];
        };
        $tree = [];
        foreach ($rows as $row) {
            if ($row['parent_id'] === null || ! isset($rows[$row['parent_id']])) {
                $tree[] = $visit($row);
            }
        }
        foreach ($rows as $row) {
            if (! isset($seen[$row['id']])) {
                $tree[] = $visit($row);
            }
        }

        return ['groups' => array_values($rows), 'groups_tree' => $tree];
    }

    public function active(int $id): bool
    {
        $rows = collect($this->options()['groups'])->keyBy('id');

        return $rows->get($id)['effective_active'] ?? false;
    }
}
