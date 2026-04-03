<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        // CajaController: no pedir permiso (vendedor = admin en cajas). No depende de la URL ni del route:cache.
        $route = $request->route();
        if ($route !== null) {
            $actionName = $route->getActionName();
            if (is_string($actionName) && Str::contains($actionName, 'CajaController')) {
                return $next($request);
            }
            $uses = $route->getAction('controller');
            if (is_string($uses) && Str::contains($uses, 'CajaController')) {
                return $next($request);
            }
            if (is_array($uses) && isset($uses[0])) {
                $c = is_object($uses[0]) ? get_class($uses[0]) : (string) $uses[0];
                if (Str::contains($c, 'CajaController')) {
                    return $next($request);
                }
            }
        }

        // Respaldo por URL / nombre (proxies, rutas raras).
        $uriPath = parse_url($request->getRequestUri(), PHP_URL_PATH) ?? '';
        if ($uriPath !== '' && preg_match('#/api/cajas(/|$)#', $uriPath)) {
            return $next($request);
        }
        if ($uriPath !== '' && preg_match('#/cajas/api(/|$)#', $uriPath)) {
            return $next($request);
        }
        $path = $request->path();
        if ($path === 'api/cajas' || str_starts_with($path, 'api/cajas/')) {
            return $next($request);
        }
        if ($path === 'cajas/api' || str_starts_with($path, 'cajas/api/')) {
            return $next($request);
        }

        $routeName = $route?->getName();
        if (is_string($routeName) && (str_starts_with($routeName, 'api.cajas') || str_starts_with($routeName, 'blade_json.cajas'))) {
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
