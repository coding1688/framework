<?php

namespace App\Services\Backend\Content;

use App\Models\Content\ContentAnnouncement;
use App\Services\Backend\BaseService;
use App\Services\Backend\Common\Audit;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ContentAnnouncementService extends BaseService
{
    public function list(array $validated): LengthAwarePaginator
    {
        $query = ContentAnnouncement::query()->orderBy('sort')->orderByDesc('id');
        if ($q = $validated['q'] ?? null) {
            $query->where('title_i18n', 'like', '%'.$q.'%');
        }
        if (array_key_exists('active', $validated) && $validated['active'] !== null && $validated['active'] !== '') {
            $query->where('active', (bool) $validated['active']);
        }

        return $query->paginate(20, ['*'], 'page', (int) ($validated['page'] ?? 1));
    }

    public function detail(int $id): ContentAnnouncement
    {
        return ContentAnnouncement::findOrFail($id);
    }

    public function create(array $validated): ContentAnnouncement
    {
        return DB::transaction(function () use ($validated): ContentAnnouncement {
            $model = ContentAnnouncement::create($validated);
            Audit::record('content.announcement.create', (string) $model->id);

            return $model;
        });
    }

    public function update(int $id, array $validated): ContentAnnouncement
    {
        return DB::transaction(function () use ($id, $validated): ContentAnnouncement {
            $model = ContentAnnouncement::lockForUpdate()->findOrFail($id);
            $model->update($validated);
            Audit::record('content.announcement.update', (string) $id);

            return $model;
        });
    }

    public function status(int $id, array $validated): ContentAnnouncement
    {
        return DB::transaction(function () use ($id, $validated): ContentAnnouncement {
            $model = ContentAnnouncement::lockForUpdate()->findOrFail($id);
            $model->update(['active' => $validated['active']]);
            Audit::record('content.announcement.status', (string) $id, ['active' => $model->active]);

            return $model;
        });
    }

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id): void {
            $model = ContentAnnouncement::lockForUpdate()->findOrFail($id);
            Audit::record('content.announcement.delete', (string) $id);
            $model->delete();
        });
    }
}
