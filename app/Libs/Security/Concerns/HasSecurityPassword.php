<?php

namespace App\Libs\Security\Concerns;

use Illuminate\Database\Eloquent\Casts\Attribute;

trait HasSecurityPassword
{
    public function initializeHasSecurityPassword(): void
    {
        $this->mergeCasts(['security_password' => 'hashed', 'security_password_changed_at' => 'datetime']);
        $this->makeHidden(['security_password']);
        $this->append('security_password_set');
    }

    public function securityPasswordHash(): ?string
    {
        $hash = $this->getAttribute('security_password');

        return is_string($hash) && $hash !== '' ? $hash : null;
    }

    public function securityPasswordIdentifier(): string
    {
        return $this->getMorphClass().':'.$this->getKey();
    }

    public function hasSecurityPassword(): bool
    {
        return $this->securityPasswordHash() !== null;
    }

    protected function securityPasswordSet(): Attribute
    {
        return Attribute::make(get: fn (): bool => $this->hasSecurityPassword());
    }
}
