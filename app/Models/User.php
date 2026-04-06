<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';
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
    ];

    protected $appends = [
        'permissions',
    ];

    public function ventas()
    {
        return $this->hasMany(Venta::class, 'usuario_id');
    }

    public function isAdmin(): bool
    {
        return $this->normalizedRoleKey() === self::ROLE_ADMIN;
    }

    /** Mostrador: usa el mismo conjunto de permisos que "vendedor" en config. */
    public function isVendedor(): bool
    {
        return $this->logicalRoleKey() === self::ROLE_VENDEDOR;
    }

    protected function normalizedRoleKey(): string
    {
        return strtolower(trim((string) $this->role));
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
