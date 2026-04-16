<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * Si el navegador envía Authorization: Bearer con un token inválido o revocado,
 * auth:sanctum falla con 401 antes de poder usar la sesión web.
 * Cuando ya hay sesión iniciada por el login Blade, se elimina ese header para que
 * Sanctum autentique por cookie como SPA stateful.
 */
class DropInvalidBearerForWebSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $raw = $request->header('Authorization', '');
        if ($raw === '' || ! str_starts_with($raw, 'Bearer ')) {
            return $next($request);
        }

        $bearer = $request->bearerToken();
        if ($bearer === null || $bearer === '' || strlen($bearer) < 8) {
            return $next($request);
        }

        $tokenModel = PersonalAccessToken::findToken($bearer);
        if ($tokenModel !== null) {
            return $next($request);
        }

        if (Auth::guard('web')->check()) {
            $request->headers->remove('Authorization');
        }

        return $next($request);
    }
}
