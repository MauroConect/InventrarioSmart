<?php

/**
 * Mostrador / vendedor por campo role. Función global: middleware, Blade y rutas
 * no dependen de métodos extra en User (evita "undefined method" con deploy parcial).
 */
if (! function_exists('user_es_mostrador')) {
    function user_es_mostrador(?object $user): bool
    {
        if ($user === null || ! property_exists($user, 'role')) {
            return false;
        }

        $k = strtolower(trim((string) $user->role));
        if ($k === '') {
            return true;
        }
        if ($k === 'admin') {
            return false;
        }

        return $k === 'vendedor'
            || in_array($k, ['vendedora', 'cajero', 'cajera'], true);
    }
}
