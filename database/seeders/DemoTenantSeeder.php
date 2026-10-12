<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use App\Services\Modules\TenantFeatureService;
use Illuminate\Database\Seeder;
use RuntimeException;

class DemoTenantSeeder extends Seeder
{
    public function run(): void
    {
        $name = env('TENANCY_SEED_TENANT_NAME');

        if (! $name) {
            $this->command?->warn('TENANCY_SEED_TENANT_NAME no definido; se omite el tenant inicial.');

            return;
        }

        $slug = env('TENANCY_SEED_TENANT_SLUG');

        if (! $slug) {
            throw new RuntimeException('TENANCY_SEED_TENANT_SLUG es obligatorio.');
        }

        $tenant = Tenant::query()->updateOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'rubro_profile' => env('TENANCY_SEED_RUBRO_PROFILE', 'base'),
                'status' => config('tenancy.active_status', 'active'),
                'timezone' => env('TENANCY_SEED_TIMEZONE', 'UTC'),
                'activated_at' => now(),
                'onboarding_completed_at' => now(),
            ]
        );

        $adminEmail = env('TENANCY_SEED_ADMIN_EMAIL');

        if (! $adminEmail) {
            return;
        }

        $admin = User::query()->where('email', $adminEmail)->first();

        if (! $admin) {
            $password = env('TENANCY_SEED_ADMIN_PASSWORD');

            if (! $password) {
                throw new RuntimeException('TENANCY_SEED_ADMIN_PASSWORD es obligatorio para crear el admin del tenant.');
            }

            $admin = User::query()->create([
                'name' => env('TENANCY_SEED_ADMIN_NAME', 'Tenant Admin'),
                'email' => $adminEmail,
                'password' => $password,
                'role' => User::ROLE_ADMIN,
            ]);
            $admin->forceFill(['email_verified_at' => now()])->save();
        }

        $tenant->users()->syncWithoutDetaching([
            $admin->getKey() => [
                'role' => User::ROLE_ADMIN,
                'is_default' => true,
                'status' => config('tenancy.active_status', 'active'),
            ],
        ]);

        app(TenantFeatureService::class)->enableDefaults($tenant, $admin->getKey());
    }
}
