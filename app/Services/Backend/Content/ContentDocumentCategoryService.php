<?php

namespace App\Services\Backend\Content;

use App\Models\Content\ContentDocumentCategory;
use App\Services\Backend\BaseService;
use App\Services\Backend\Common\Audit;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ContentDocumentCategoryService extends BaseService
{
    public function list(array $validated): Collection
    {
        $query = ContentDocumentCategory::query()->orderBy('sort')->orderBy('id');
        if ($q = $validated['q'] ?? null) {
            $query->where(function ($query) use ($q): void {
                $query->where('name_i18n', 'like', '%'.$q.'%')->orWhere('slug', 'like', '%'.$q.'%');
            });
        }
        if ($type = $validated['type'] ?? null) {
            $query->where('type', $type);
        }
        if (array_key_exists('active', $validated) && $validated['active'] !== null && $validated['active'] !== '') {
            $query->where('active', (bool) $validated['active']);
        }

        return $query->get();
    }

    public function options(array $validated): Collection
    {
        return ContentDocumentCategory::query()
            ->when($validated['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->orderBy('sort')->orderBy('id')->get();
    }

    public function detail(int $id): ContentDocumentCategory
    {
        return ContentDocumentCategory::findOrFail($id);
    }

    public function create(array $validated): ContentDocumentCategory
    {
        return DB::transaction(function () use ($validated): ContentDocumentCategory {
            $this->validateParent($validated);
            $model = ContentDocumentCategory::create($validated);
            Audit::record('documentation.category.create', (string) $model->id);

            return $model;
        });
    }

    public function update(int $id, array $validated): ContentDocumentCategory
    {
        return DB::transaction(function () use ($id, $validated): ContentDocumentCategory {
            $model = ContentDocumentCategory::lockForUpdate()->findOrFail($id);
            if ($model->type !== $validated['type'] && ($model->children()->exists() || $model->documents()->exists())) {
                throw ValidationException::withMessages(['type' => __('backend/common.in_use')]);
            }
            $this->validateParent($validated, $model);
            $model->update($validated);
            Audit::record('documentation.category.update', (string) $id);

            return $model;
        });
    }

    public function status(int $id, array $validated): ContentDocumentCategory
    {
        return DB::transaction(function () use ($id, $validated): ContentDocumentCategory {
            $model = ContentDocumentCategory::lockForUpdate()->findOrFail($id);
            $model->update(['active' => $validated['active']]);
            Audit::record('documentation.category.status', (string) $id, ['active' => $model->active]);

            return $model;
        });
    }

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id): void {
            $model = ContentDocumentCategory::lockForUpdate()->findOrFail($id);
            if ($model->children()->exists() || $model->documents()->exists()) {
                throw ValidationException::withMessages(['id' => __('backend/common.in_use')]);
            }
            Audit::record('documentation.category.delete', (string) $id);
            $model->delete();
        });
    }

    private function validateParent(array $validated, ?ContentDocumentCategory $model = null): void
    {
        if (! ($validated['parent_id'] ?? null)) {
            return;
        }
        $parent = ContentDocumentCategory::findOrFail($validated['parent_id']);
        if ($parent->type !== $validated['type']) {
            throw ValidationException::withMessages(['parent_id' => __('backend/common.cycle')]);
        }
        while ($parent) {
            if ($parent->id === $model?->id) {
                throw ValidationException::withMessages(['parent_id' => __('backend/common.cycle')]);
            }
            $parent = $parent->parent;
        }
    }
}
