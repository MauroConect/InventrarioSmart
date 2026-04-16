<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sanctum only runs the web session stack on /api when the request is "first-party":
 * Referer or Origin must match config('sanctum.stateful'). Otherwise axios calls from
 * Blade pages get no session and auth:sanctum returns 401 even though the user is logged in.
 *
 * This middleware merges the current HTTP host (and host:port) into the stateful list for
 * this request, and supplies a same-origin Referer when both Referer and Origin are absent
 * (some browsers or referrer policies strip them).
 */
class PrepareSanctumStatefulRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        $httpHost = $request->getHttpHost();
        $host = $request->getHost();

        $stateful = config('sanctum.stateful', []);
        if (! is_array($stateful)) {
            $stateful = [];
        }

        $add = array_unique(array_filter([$httpHost, $host]));
        $merged = array_values(array_unique(array_merge($stateful, $add)));
        if ($merged !== $stateful) {
            config(['sanctum.stateful' => $merged]);
        }

        if (! $request->headers->get('referer') && ! $request->headers->get('origin')) {
            $request->headers->set('Referer', $request->getSchemeAndHttpHost().'/');
        }

        return $next($request);
    }
}
