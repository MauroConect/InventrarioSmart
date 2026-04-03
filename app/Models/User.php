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

    /** Mostrador / vendedor (menú Blade; no admin). */
    protected function esMostradorPorRole(): bool
    {
        $k = strtolower(trim((string) $this->role));
        if ($k === '') {
            return true;
        }
        if ($k === self::ROLE_ADMIN) {
            return false;
        }

        if ($k === self::ROLE_VENDEDOR || in_array($k, ['vendedora', 'cajero', 'cajera', 'mostrador'], true)) {
            return true;
        }

        $aliases = config('permissions.vendedor_role_aliases', []);
        if (is_array($aliases) && in_array($k, $aliases, true)) {
            return true;
        }

        return str_contains($k, 'vend');
    }

    public function isVendedor(): bool
    {
        return $this->esMostradorPorRole();
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
        // Política: en servidor, todo usuario autenticado tiene los mismos permisos (como admin).
        // La diferencia vendedor/admin es solo el menú Blade (isAdmin / isVendedor).
        if (config('permissions.enforce_role_permissions', false)) {
            if ($this->isAdmin() || $this->esMostradorPorRole()) {
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

        return true;
    }

    /**
     * Lista de permisos del rol (para el front SPA). El admin se expone como ['*'].
     *
     * @return array<int, string>
     */
    public function getPermissionsAttribute(): array
    {
        if (! config('permissions.enforce_role_permissions', false)) {
            return ['*'];
        }

        if ($this->isAdmin() || $this->esMostradorPorRole()) {
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
