<?php

namespace App\Libs\Security\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

interface SecurityPasswordAccount extends Authenticatable
{
    public function securityPasswordHash(): ?string;

    public function securityPasswordIdentifier(): string;

    public function hasSecurityPassword(): bool;
}
