<?php

namespace App\Services\Backend\Member;

use App\Models\Member\MemberVipLevel;
use App\Models\Member\MemberVipRecord;
use App\Services\Backend\BaseService;
use App\Services\Backend\Common\Audit;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MemberVipLevelService extends BaseService
{
    public function list(array $validated): LengthAwarePaginator
    {
        $this->guard('list');
        $query = MemberVipLevel::withCount('users')->orderBy('sort')->orderBy('level');
        if (isset($validated['active'])) {
            $query->where('active', $validated['active']);
        }

        return $query->paginate(20, ['*'], 'page', (int) ($validated['page'] ?? 1));
    }

    public function options(): array
    {
        $this->guard('options');

        return MemberVipLevel::where('active', true)->orderBy('sort')->orderBy('level')->get()->map(fn (MemberVipLevel $level): array => ['id' => $level->id, 'level' => $level->level, 'name' => $level->name, 'duration_days' => $level->duration_days])->values()->all();
    }

    public function detail(int $id): MemberVipLevel
    {
        $this->guard('detail');

        return MemberVipLevel::withCount('users')->findOrFail($id);
    }

    public function create(array $validated): MemberVipLevel
    {
        $this->guard('create');

        return DB::transaction(function () use ($validated): MemberVipLevel {
            $level = MemberVipLevel::create($validated);
            Audit::record('member.vipLevel.create', (string) $level->id);

            return $level;
        });
    }

    public function update(array $validated, int $id): MemberVipLevel
    {
        $this->guard('update');

        return DB::transaction(function () use ($validated, $id): MemberVipLevel {
            $level = MemberVipLevel::lockForUpdate()->findOrFail($id);
            $level->update($validated);
            Audit::record('member.vipLevel.update', (string) $id);

            return $level;
        });
    }

    public function status(array $validated, int $id): MemberVipLevel
    {
        $this->guard('status');

        return DB::transaction(function () use ($validated, $id): MemberVipLevel {
            $level = MemberVipLevel::lockForUpdate()->findOrFail($id);
            $level->update(['active' => $validated['active']]);
            Audit::record('member.vipLevel.status', (string) $id, ['active' => (bool) $validated['active']]);

            return $level;
        });
    }

    public function delete(int $id): void
    {
        $this->guard('delete');
        DB::transaction(function () use ($id): void {
            $level = MemberVipLevel::lockForUpdate()->findOrFail($id);
            if ($level->users()->exists() || MemberVipRecord::where('from_vip_level_id', $id)->orWhere('to_vip_level_id', $id)->exists()) {
                throw ValidationException::withMessages(['id' => __('backend/common.in_use')]);
            }
            $level->delete();
            Audit::record('member.vipLevel.delete', (string) $id);
        });
    }

    private function guard(string $action): void
    {
        $this->authorize('member.vipLevel.'.$action);
    }
}
