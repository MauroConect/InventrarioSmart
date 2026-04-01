<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Si hay sesión web (login Blade), usar ese usuario en toda la petición API.
 * Evita que un token viejo en localStorage mande sobre la sesión del vendedor.
 */
class PreferWebSessionUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $webUser = Auth::guard('web')->user();
        if ($webUser) {
            $request->setUserResolver(static fn () => $webUser);
        }

        return $next($request);
    }
}
