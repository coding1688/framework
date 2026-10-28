<?php

namespace App\Libs\Security;

use App\Libs\Security\Contracts\SecurityPasswordAccount;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class SecurityPassword
{
    public function assertValid(SecurityPasswordAccount $account, string $password, string $scope, string $ip, string $field = 'security_password'): void
    {
        $key = $this->key($account, $scope, 'verify');
        $this->assertNotThrottled($key);
        if (! $account->hasSecurityPassword()) {
            throw ValidationException::withMessages([$field => __('common/security/main.not_configured')]);
        }
        if ($password === '' || ! Hash::check($password, $account->securityPasswordHash())) {
            RateLimiter::hit($key, 300);
            throw ValidationException::withMessages([$field => __('common/security/main.incorrect')]);
        }
        RateLimiter::clear($key);
    }

    public function authorizeChange(SecurityPasswordAccount $account, ?string $loginPassword, ?string $currentSecurityPassword, string $scope, string $ip): void
    {
        if ($account->hasSecurityPassword()) {
            $this->assertValid($account, $currentSecurityPassword ?? '', $scope, $ip, 'current_security_password');

            return;
        }
        $key = $this->key($account, $scope, 'setup');
        $this->assertNotThrottled($key);
        $hash = $account->getAuthPassword();
        if (! is_string($hash) || $hash === '') {
            throw ValidationException::withMessages(['current_password' => __('common/security/main.login_password_required')]);
        }
        if ($loginPassword === null || ! Hash::check($loginPassword, $hash)) {
            RateLimiter::hit($key, 300);
            throw ValidationException::withMessages(['current_password' => __('common/security/main.login_password_incorrect')]);
        }
        RateLimiter::clear($key);
    }

    public function assertDistinct(SecurityPasswordAccount $account, string $newPassword): void
    {
        $loginHash = $account->getAuthPassword();
        if (is_string($loginHash) && $loginHash !== '' && Hash::check($newPassword, $loginHash)) {
            throw ValidationException::withMessages(['security_password' => __('common/security/main.must_differ')]);
        }
    }

    private function key(SecurityPasswordAccount $account, string $scope, string $purpose): string
    {
        if (! in_array($scope, ['backend', 'partner', 'api'], true)) {
            throw new InvalidArgumentException('Unsupported security password scope.');
        }

        return $scope.':security-password:'.$purpose.':'.hash('sha256', $account->securityPasswordIdentifier());
    }

    private function assertNotThrottled(string $key): void
    {
        if (RateLimiter::tooManyAttempts($key, 5)) {
            abort(429, __('common/security/main.throttled', ['seconds' => RateLimiter::availableIn($key)]), ['Retry-After' => (string) RateLimiter::availableIn($key)]);
        }
    }
}
