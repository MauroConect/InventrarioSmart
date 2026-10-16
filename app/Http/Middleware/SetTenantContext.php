<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Models\TenantDomain;
use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class SetTenantContext
{
    public function __construct(private readonly TenantContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->context->clear();

        $user = $request->user();
        abort_unless($user, 401);

        $identifier = $request->header(config('tenancy.header', 'X-Tenant-Id'));

        if (! $identifier && $request->hasSession()) {
            $identifier = $request->session()->get(config('tenancy.session_key', 'tenant_id'));
        }
        if (! $identifier && \Illuminate\Support\Facades\Schema::hasTable('tenant_domains')) {
            $identifier = TenantDomain::query()
                ->where('host', $request->getHost())
                ->where('status', 'active')
                ->value('tenant_id');
        }

        $membershipQuery = $user->tenants()
            ->wherePivot('status', config('tenancy.active_status', 'active'))
            ->where('tenants.status', config('tenancy.active_status', 'active'));

        if (! $identifier) {
            $tenants = (clone $membershipQuery)->limit(2)->get();

            if ($tenants->count() === 1) {
                $tenant = $tenants->first();
                $this->persistTenant($request, $tenant);
            } elseif ($tenants->isEmpty()) {
                if ($user->onboarding && ! $user->onboarding->completed_at) {
                    return $request->expectsJson()
                        ? response()->json(['message' => 'Tu comercio todavía está en onboarding asistido.'], 409)
                        : redirect()->route('onboarding.show');
                }

                if (config('tenancy.legacy_mode', true)) {
                    return $next($request);
                }

                return $request->expectsJson()
                    ? response()->json(['message' => 'Debe seleccionar un comercio.'], 409)
                    : redirect()->route('tenant.select');
            } else {
                return $request->expectsJson()
                    ? response()->json(['message' => 'Debe seleccionar un comercio.'], 409)
                    : redirect()->route('tenant.select');
            }
        } else {
            $tenant = (clone $membershipQuery)
                ->where(function ($query) use ($identifier): void {
                    if (is_numeric($identifier)) {
                        $query->whereKey((int) $identifier);
                    } elseif (Str::isUuid($identifier)) {
                        $query->where('tenants.uuid', $identifier)
                            ->orWhere('tenants.slug', $identifier);
                    } else {
                        $query->where('tenants.slug', $identifier);
                    }
                })
                ->first();

            abort_unless($tenant, 403, 'No tienes acceso al comercio solicitado.');
            $this->persistTenant($request, $tenant);
        }

        $this->context->set($tenant);

        try {
            return $next($request);
        } finally {
            $this->context->clear();
        }
    }

    private function persistTenant(Request $request, Tenant $tenant): void
    {
        if ($request->hasSession()) {
            $request->session()->put(config('tenancy.session_key', 'tenant_id'), $tenant->getKey());
        }
    }
}
