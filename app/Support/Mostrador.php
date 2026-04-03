<?php

namespace App\Support;

use App\Models\User;

/**
 * Perfil mostrador / vendedor (misma lógica en toda la app si falta User::isVendedor en deploy).
 */
final class Mostrador
{
    public static function es(?object $user): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        $k = strtolower(trim((string) $user->role));
        if ($k === '') {
            return true;
        }
        if ($k === User::ROLE_ADMIN) {
            return false;
        }

        return $k === User::ROLE_VENDEDOR
            || in_array($k, ['vendedora', 'cajero', 'cajera'], true);
    }
}
