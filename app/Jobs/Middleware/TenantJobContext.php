<?php

namespace App\Jobs\Middleware;

use App\Jobs\TenantJob;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Support\Tenancy\TenantContext;
use Closure;
use RuntimeException;

class TenantJobContext
{
    public function handle(TenantJob $job, Closure $next): mixed
    {
        $tenant = Tenant::whereKey($job->tenantId)->where('status', 'active')->whereNotNull('onboarding_completed_at')->first();
        if (! $tenant) { throw new RuntimeException('El comercio del job no está disponible.'); }
        if ($job->actorUserId !== null && ! TenantUser::where('tenant_id', $tenant->id)->where('user_id', $job->actorUserId)->where('status', 'active')->exists()) {
            throw new RuntimeException('El actor del job no está autorizado en el comercio.');
        }
        return app(TenantContext::class)->run($tenant, fn () => $next($job));
    }
}
