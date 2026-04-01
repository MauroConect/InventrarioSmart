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
        $k = $this->normalizedRoleKey();
        if ($k === '') {
            return true;
        }

        return $k === self::ROLE_VENDEDOR
            || in_array($k, ['vendedora', 'cajero', 'cajera'], true);
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

    /**
     * @param non-empty-string $permission
     */
    protected function hasRolePermission(string $permission): bool
    {
        if ($this->isAdmin()) {
            return true;
        }
        $roleKey = $this->effectiveRoleKey();
        $rolePermissions = config('permissions.roles.' . $roleKey, []);

        return in_array('*', $rolePermissions, true)
            || in_array($permission, $rolePermissions, true);
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        // Quien puede cargar ventas debe poder listar y abrir/cerrar caja (evita roles mal sincronizados en config).
        if (in_array($permission, ['cajas.view', 'cajas.manage'], true) && $this->hasRolePermission('ventas.create')) {
            return true;
        }

        // Vendedor: operación de mostrador — siempre puede ver y abrir/cerrar caja.
        if ($this->isVendedor() && in_array($permission, ['cajas.view', 'cajas.manage'], true)) {
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
        if ($this->isAdmin()) {
            return ['*'];
        }

        $roleKey = $this->effectiveRoleKey();
        $list = $roleKey === ''
            ? []
            : array_values(config('permissions.roles.' . $roleKey, []));

        if ($this->hasRolePermission('ventas.create')) {
            foreach (['cajas.view', 'cajas.manage'] as $p) {
                if (!in_array($p, $list, true)) {
                    $list[] = $p;
                }
            }
        }

        return $list;
    }
}
