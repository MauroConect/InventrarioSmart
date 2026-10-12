<?php

namespace App\Support\Tenancy;

use App\Models\Tenant;
use Closure;

class TenantScheduler
{
    public function forEachActive(Closure $callback): void
    {
        Tenant::where('status', 'active')->whereNotNull('onboarding_completed_at')->orderBy('id')->chunkById(100, function ($tenants) use ($callback) {
            foreach ($tenants as $tenant) { app(TenantContext::class)->run($tenant, $callback); }
        });
    }
}
