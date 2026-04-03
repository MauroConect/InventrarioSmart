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
        if (! user_es_mostrador($request->user())) {
            abort(403, 'Punto de caja solo para vendedores.');
        }

        return $next($request);
    }
}
