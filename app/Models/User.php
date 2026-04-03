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

    public function isAdmin(): bool
    {
        return $this->normalizedRoleKey() === self::ROLE_ADMIN;
    }

    public function isVendedor(): bool
    {
        return user_es_mostrador($this);
    }

    protected function normalizedRoleKey(): string
    {
        return strtolower(trim((string) $this->role));
    }

    /**
     * Rol lógico para permisos en config (alias de mostrador → vendedor).
     */
    protected function effectiveRoleKey(): string
    {
        $k = $this->normalizedRoleKey();
        if ($k === '') {
            return self::ROLE_VENDEDOR;
        }
        if (in_array($k, ['vendedora', 'cajero', 'cajera'], true)) {
            return self::ROLE_VENDEDOR;
        }

        return $k;
    }

    public function hasPermission(string $permission): bool
    {
        // Admin y vendedor/mostrador: mismos permisos en toda la app (incluye cajas vía API).
        if ($this->isAdmin() || user_es_mostrador($this)) {
            return true;
        }

        $roleKey = $this->effectiveRoleKey();
        $rolePermissions = $roleKey === ''
            ? []
            : config('permissions.roles.' . $roleKey, []);
        if (in_array('*', $rolePermissions, true)) {
            return true;
        }

        return in_array($permission, $rolePermissions, true);
    }

    /**
     * Lista de permisos del rol (para el front SPA). El admin se expone como ['*'].
     *
     * @return array<int, string>
     */
    public function getPermissionsAttribute(): array
    {
        if ($this->isAdmin() || user_es_mostrador($this)) {
            return ['*'];
        }

        $roleKey = $this->effectiveRoleKey();
        $list = $roleKey === ''
            ? []
            : array_values(config('permissions.roles.' . $roleKey, []));

        foreach (['cajas.view', 'cajas.manage'] as $p) {
            if (! in_array($p, $list, true)) {
                $list[] = $p;
            }
        }

        return $list;
    }
}
