<?php

namespace App\Services\Backend\Member;

use App\Models\Member\MemberVipLevel;
use App\Models\Member\MemberVipRecord;
use App\Models\Member\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MemberVipService
{
    public function update(int $userId, array $validated): User
    {
        return DB::transaction(function () use ($userId, $validated): User {
            $user = User::lockForUpdate()->findOrFail($userId);
            $level = isset($validated['vip_level_id']) ? MemberVipLevel::where('active', true)->findOrFail($validated['vip_level_id']) : null;
            $changing = $user->vip_level_id !== $level?->id;
            $startedAt = isset($validated['started_at']) ? Carbon::parse($validated['started_at']) : ($changing ? now() : $user->vip_started_at ?? now());
            $expiresAt = array_key_exists('expires_at', $validated) ? $validated['expires_at'] : ($level?->duration_days ? $startedAt->copy()->addDays($level->duration_days) : null);
            $record = new MemberVipRecord([
                'from_vip_level_id' => $user->vip_level_id, 'to_vip_level_id' => $level?->id,
                'from_expires_at' => $user->vip_expires_at, 'to_expires_at' => $level ? $expiresAt : null,
                'started_at' => $level ? $startedAt : now(), 'administrator_id' => Auth::guard('backend')->id(),
                'reason' => $validated['reason'] ?? null,
            ]);
            $user->forceFill([
                'vip_level_id' => $level?->id, 'vip_level' => $level?->level ?? 0,
                'vip_started_at' => $level ? $startedAt : null, 'vip_expires_at' => $level ? $expiresAt : null,
            ])->save();
            $user->vipRecords()->save($record);

            return $user->fresh()->load(['socialAccounts', 'vipLevel']);
        });
    }

    public function options(): array
    {
        return MemberVipLevel::where('active', true)->orderBy('sort')->orderBy('level')->get()->map(fn (MemberVipLevel $level): array => ['id' => $level->id, 'level' => $level->level, 'name' => $level->name, 'duration_days' => $level->duration_days])->values()->all();
    }

    public function history(int $userId, array $validated): LengthAwarePaginator
    {
        return MemberVipRecord::with(['fromLevel', 'toLevel', 'administrator'])->where('user_id', $userId)->latest('id')
            ->paginate(20, ['*'], 'page', (int) ($validated['page'] ?? 1));
    }
}
