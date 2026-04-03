<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rutas /punto-caja: solo perfiles de mostrador (isVendedor), no admin.
 */
class EnsureVendedorPuntoCaja
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! self::usuarioEsMostrador($request->user())) {
            abort(403, 'Punto de caja solo para vendedores.');
        }

        return $next($request);
    }

    private static function usuarioEsMostrador(?object $user): bool
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

        return in_array($k, ['vendedor', 'vendedora', 'cajero', 'cajera'], true);
    }
}
