<?php

namespace App\Services\Backend\Content;

use App\Models\Content\ContentDocument;
use App\Models\Content\ContentDocumentCategory;
use App\Services\Backend\BaseService;
use App\Services\Backend\Common\Audit;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ContentDocumentService extends BaseService
{
    public function list(array $validated): LengthAwarePaginator
    {
        $query = ContentDocument::with('category')->orderBy('sort')->orderByDesc('updated_at');
        if ($q = $validated['q'] ?? null) {
            $query->where(function ($query) use ($q): void {
                $query->where('title_i18n', 'like', '%'.$q.'%')->orWhere('slug', 'like', '%'.$q.'%');
            });
        }
        if ($type = $validated['type'] ?? null) {
            $query->where('type', $type);
        }
        if ($categoryId = $validated['category_id'] ?? null) {
            $query->where('category_id', $categoryId);
        }
        if (array_key_exists('active', $validated) && $validated['active'] !== null && $validated['active'] !== '') {
            $query->where('active', (bool) $validated['active']);
        }

        return $query->paginate(20, ['*'], 'page', (int) ($validated['page'] ?? 1));
    }

    public function detail(int $id): ContentDocument
    {
        return ContentDocument::with('category')->findOrFail($id);
    }

    public function create(array $validated): ContentDocument
    {
        return DB::transaction(function () use ($validated): ContentDocument {
            $this->validateCategory($validated);
            $model = ContentDocument::create($validated);
            Audit::record('documentation.document.create', (string) $model->id);

            return $model->load('category');
        });
    }

    public function update(int $id, array $validated): ContentDocument
    {
        return DB::transaction(function () use ($id, $validated): ContentDocument {
            $model = ContentDocument::lockForUpdate()->findOrFail($id);
            $this->validateCategory($validated);
            $model->update($validated);
            Audit::record('documentation.document.update', (string) $id);

            return $model->load('category');
        });
    }

    public function status(int $id, array $validated): ContentDocument
    {
        return DB::transaction(function () use ($id, $validated): ContentDocument {
            $model = ContentDocument::lockForUpdate()->findOrFail($id);
            $model->update(['active' => $validated['active']]);
            Audit::record('documentation.document.status', (string) $id, ['active' => $model->active]);

            return $model->load('category');
        });
    }

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id): void {
            $model = ContentDocument::lockForUpdate()->findOrFail($id);
            Audit::record('documentation.document.delete', (string) $id);
            $model->delete();
        });
    }

    private function validateCategory(array $validated): void
    {
        $category = ContentDocumentCategory::findOrFail($validated['category_id']);
        if ($category->type !== $validated['type']) {
            throw ValidationException::withMessages(['category_id' => __('backend/common.in_use')]);
        }
    }
}
