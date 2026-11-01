<?php

namespace App\Services\Member;

use App\Models\Member\User;
use Illuminate\Validation\ValidationException;

class UserRestrictionService
{
    public function types(): array
    {
        return config('member.restrictions');
    }

    public function has(User $user, string $type): bool
    {
        $this->ensureType($type);

        return $user->restrictions()->where('type', $type)->whereNull('lifted_at')->exists();
    }

    public function allows(User $user, string $capability): bool
    {
        foreach ($this->types() as $type => $definition) {
            if (in_array($capability, $definition['capabilities'], true) && $this->has($user, $type)) {
                return false;
            }
        }

        return true;
    }

    public function change(User $user, string $type, bool $restricted, ?string $reason, string $actor): bool
    {
        $this->ensureType($type);
        $restriction = $user->restrictions()->where('type', $type)->whereNull('lifted_at')->lockForUpdate()->first();
        if ($restricted === ($restriction !== null)) {
            return false;
        }
        if ($restricted) {
            $user->restrictions()->create([
                'type' => $type, 'active_key' => $user->id.':'.$type, 'reason' => $reason,
                'created_by' => $actor, 'created_at' => now(),
            ]);
        } else {
            $restriction->forceFill([
                'active_key' => null, 'lifted_at' => now(), 'lifted_reason' => $reason, 'lifted_by' => $actor,
            ])->save();
        }
        if ($this->revokesSessions($type)) {
            $user->forceFill([
                'active' => $this->allows($user, 'authentication'),
                'auth_version' => $user->auth_version + 1,
            ])->save();
        }

        return true;
    }

    public function revokesSessions(string $type): bool
    {
        $this->ensureType($type);

        return (bool) $this->types()[$type]['revoke_sessions'];
    }

    private function ensureType(string $type): void
    {
        if (! array_key_exists($type, $this->types())) {
            throw ValidationException::withMessages(['type' => [__('validation.in', ['attribute' => 'type'])]]);
        }
    }
}
