<?php

namespace App\Models\Admin;

use App\Libs\Security\Concerns\HasSecurityPassword;
use App\Libs\Security\Contracts\SecurityPasswordAccount;
use App\Notifications\ResetAdminPasswordNotification;
use Database\Factories\backend\AdminUserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class AdminUser extends Authenticatable implements SecurityPasswordAccount
{
    use HasFactory, HasSecurityPassword, Notifiable;

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetAdminPasswordNotification($token));
    }

    protected $table = 'admin_users';

    private ?array $resolvedPermissions = null;

    private ?bool $boardStatus = null;

    protected $attributes = ['is_leader' => false, 'is_super' => false, 'auth_version' => 1];

    protected $fillable = ['name', 'username', 'email', 'password', 'department_id', 'locale', 'active', 'is_leader'];

    protected $hidden = ['password', 'security_password', 'remember_token', 'auth_version'];

    protected function casts(): array
    {
        return ['password' => 'hashed', 'active' => 'boolean', 'is_super' => 'boolean', 'is_leader' => 'boolean', 'preferences' => 'array', 'notification_preferences' => 'array', 'last_login_at' => 'datetime', 'password_changed_at' => 'datetime'];
    }

    public function themePreferences(): array
    {
        return array_replace(config('platform.backend_ui.theme.defaults'), $this->preferences ?? []);
    }

    public function clearAuthorizationCache(): void
    {
        $this->resolvedPermissions = null;
        $this->boardStatus = null;
    }

    public function refresh(): static
    {
        $this->clearAuthorizationCache();

        return parent::refresh();
    }

    protected static function newFactory(): AdminUserFactory
    {
        return AdminUserFactory::new();
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(AdminDepartment::class, 'department_id');
    }

    public function permissions(): array
    {
        if ($this->resolvedPermissions !== null) {
            return $this->resolvedPermissions;
        }
        if (! $this->active) {
            return $this->resolvedPermissions = [];
        }
        if ($this->isBoard()) {
            return $this->resolvedPermissions = ['*'];
        }
        $departmentIds = $this->department()->first()?->effectiveMenuIds() ?? [];
        $departmentPermissions = AdminMenu::whereIn('id', $departmentIds)->get()->pluck('permission')->unique()->values()->all();

        if (! $this->is_leader) {
            $departmentPermissions = array_values(array_filter($departmentPermissions, fn (string $permission): bool => ! preg_match('/^admin\.(user|department)\./', $permission)
                || in_array($permission, ['admin.user.list', 'admin.user.detail', 'admin.user.options', 'admin.department.list', 'admin.department.options'], true)
            ));
        }

        return $this->resolvedPermissions = $departmentPermissions;
    }

    public function isBoard(): bool
    {
        return $this->boardStatus ??= $this->active && ($this->is_super || $this->department()->where('code', 'board')->where('active', true)->exists());
    }

    public function permits(string $permission): bool
    {
        return $this->active && ($this->isBoard() || in_array($permission, $this->permissions(), true));
    }
}
