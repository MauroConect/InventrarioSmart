<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        // Cajas vía /api/cajas: no aplicar permisos (solo importa estar autenticado en la ruta).
        // Cubre route:cache viejo, despliegues parciales o cualquier stack que siga montando este middleware encima.
        $path = $request->path();
        if ($path === 'api/cajas' || str_starts_with($path, 'api/cajas/')) {
            return $next($request);
        }

        // Sesión web primero (Blade + axios same-origin); evita desajuste con token Sanctum residual.
        $user = Auth::guard('web')->user() ?? $request->user();

        if (! $user) {
            abort(401, 'No autenticado.');
        }

        if (! $user->hasPermission($permission)) {
            abort(403, 'No tienes permisos para realizar esta acción.');
        }

        return $next($request);
    }
}
