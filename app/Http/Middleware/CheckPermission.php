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
        // Cajas API: nunca bloquear por permiso. Usamos URI cruda porque path() falla en algunos nginx/proxy/subcarpetas.
        $uriPath = parse_url($request->getRequestUri(), PHP_URL_PATH) ?? '';
        if ($uriPath !== '' && preg_match('#/api/cajas(/|$)#', $uriPath)) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();
        if (is_string($routeName) && str_starts_with($routeName, 'api.cajas')) {
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
