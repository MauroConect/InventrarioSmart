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
        return $this->role === self::ROLE_ADMIN;
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        // Vendedor: operación de mostrador — siempre puede ver y abrir/cerrar caja.
        if ($this->role === self::ROLE_VENDEDOR && in_array($permission, ['cajas.view', 'cajas.manage'], true)) {
            return true;
        }

        $rolePermissions = config('permissions.roles.' . $this->role, []);
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

        $list = array_values(config('permissions.roles.' . $this->role, []));

        if ($this->role === self::ROLE_VENDEDOR) {
            foreach (['cajas.view', 'cajas.manage'] as $p) {
                if (!in_array($p, $list, true)) {
                    $list[] = $p;
                }
            }
        }

        return $list;
    }
}
