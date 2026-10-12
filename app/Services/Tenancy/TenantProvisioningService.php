<?php

namespace App\Services\Tenancy;

use App\Models\Tenant;
use App\Models\TenantOnboarding;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TenantProvisioningService
{
    public function provisionProfile(User $user, TenantOnboarding $onboarding, array $attributes): Tenant
    {
        return DB::transaction(function () use ($user, $onboarding, $attributes) {
            $tenant = $onboarding->tenant ?: new Tenant;
            $tenant->fill($attributes + ['status' => 'provisioning']);
            $tenant->owner_user_id = $user->getKey();
            $tenant->save();

            $tenant->users()->syncWithoutDetaching([
                $user->getKey() => [
                    'role' => User::ROLE_OWNER,
                    'is_default' => true,
                    'status' => 'active',
                ],
            ]);

            $onboarding->update([
                'tenant_id' => $tenant->getKey(),
                'draft' => array_merge($onboarding->draft ?? [], ['profile' => $attributes]),
            ]);

            return $tenant;
        });
    }

    public function activate(TenantOnboarding $onboarding): Tenant
    {
        return DB::transaction(function () use ($onboarding) {
            $tenant = $onboarding->tenant;
            abort_unless($tenant, 409, 'No existe un comercio para activar.');

            $tenant->forceFill([
                'status' => 'active',
                'activated_at' => $tenant->activated_at ?? now(),
                'onboarding_completed_at' => now(),
            ])->save();

            $onboarding->update([
                'step' => 'complete',
                'status' => 'active',
                'completed_at' => now(),
            ]);

            return $tenant->fresh();
        });
    }
}
