<?php

namespace App\Http\Middleware;

use App\Services\Modules\TenantFeatureService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantFeature
{
    public function __construct(private readonly TenantFeatureService $features)
    {
    }

    public function handle(Request $request, Closure $next, string $feature): Response
    {
        abort_unless($this->features->enabled($feature), 404, 'El módulo solicitado no está habilitado.');

        return $next($request);
    }
}
