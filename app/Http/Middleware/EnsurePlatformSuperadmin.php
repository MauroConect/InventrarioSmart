<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlatformSuperadmin
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(
            $request->user()?->isPlatformSuperadmin(),
            403,
            'Acceso reservado a la administración de plataforma.'
        );

        return $next($request);
    }
}
