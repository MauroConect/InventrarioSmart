<?php

namespace App\Models;

use App\Support\Tenancy\TenantContext;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmailContract
{
    use HasApiTokens, HasFactory, MustVerifyEmail, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_OWNER = 'owner';

    public const ROLE_VENDEDOR = 'vendedor';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_platform_superadmin' => 'boolean',
    ];

    protected $appends = [
        'permissions',
    ];

    public function ventas()
    {
        return $this->hasMany(Venta::class, 'usuario_id');
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(TenantUser::class);
    }

    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class)
            ->using(TenantUser::class)
            ->withPivot(['role', 'is_default', 'status'])
            ->withTimestamps();
    }

    public function onboarding(): HasOne
    {
        return $this->hasOne(TenantOnboarding::class);
    }

    public function isPlatformSuperadmin(): bool
    {
        return (bool) $this->is_platform_superadmin;
    }

    public function isAdmin(): bool
    {
        return in_array($this->normalizedRoleKey(), [self::ROLE_OWNER, self::ROLE_ADMIN], true);
    }

    /** Mostrador: usa el mismo conjunto de permisos que "vendedor" en config. */
    public function isVendedor(): bool
    {
        return $this->logicalRoleKey() === self::ROLE_VENDEDOR;
    }

    protected function normalizedRoleKey(): string
    {
        return strtolower(trim((string) ($this->currentTenantRole() ?? $this->role)));
    }

    public function currentTenantRole(): ?string
    {
        $context = app(TenantContext::class);

        if (! $context->hasTenant()) {
            return null;
        }

        $tenantId = $context->id();

        if ($this->relationLoaded('memberships')) {
            return $this->memberships
                ->first(fn (TenantUser $membership) => (int) $membership->tenant_id === $tenantId
                    && $membership->status === config('tenancy.active_status', 'active'))
                ?->role ?? '';
        }

        return $this->memberships()
            ->where('tenant_id', $tenantId)
            ->where('status', config('tenancy.active_status', 'active'))
            ->value('role') ?? '';
    }

    /**
     * Rol lógico para buscar permisos en config/permissions.php → roles.{clave}
     */
    public function logicalRoleKey(): string
    {
        $k = $this->normalizedRoleKey();
        if ($k === '') {
            return self::ROLE_VENDEDOR;
        }
        if ($k === self::ROLE_ADMIN) {
            return self::ROLE_ADMIN;
        }
        if ($k === self::ROLE_OWNER) {
            return self::ROLE_OWNER;
        }
        $vendedorNames = config('permissions.vendedor_role_names', []);
        if (is_array($vendedorNames) && in_array($k, $vendedorNames, true)) {
            return self::ROLE_VENDEDOR;
        }

        return $k;
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $roleKey = $this->logicalRoleKey();
        $rolePermissions = config('permissions.roles.'.$roleKey, []);

        if (in_array('*', $rolePermissions, true)) {
            return true;
        }

        return in_array($permission, $rolePermissions, true);
    }

    /**
     * @return array<int, string>
     */
    public function getPermissionsAttribute(): array
    {
        if ($this->isAdmin()) {
            return ['*'];
        }

        $roleKey = $this->logicalRoleKey();

        return array_values(config('permissions.roles.'.$roleKey, []));
    }
}
