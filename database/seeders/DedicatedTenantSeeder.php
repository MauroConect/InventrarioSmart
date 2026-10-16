<?php

namespace Database\Seeders;

use App\Models\ConfiguracionComercio;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Modules\TenantFeatureService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DedicatedTenantSeeder extends Seeder
{
    public function run(): void
    {
        $slug = config('deployment.tenant_slug');
        $name = config('deployment.tenant_name');
        $email = config('deployment.admin_email');

        if (! $slug || ! $name || ! $email) {
            throw new RuntimeException('Definir DEDICATED_TENANT_SLUG, DEDICATED_TENANT_NAME y DEDICATED_ADMIN_EMAIL.');
        }

        DB::transaction(function () use ($slug, $name, $email): void {
            $admin = User::query()->where('email', $email)->first();
            if (! $admin) {
                $password = config('deployment.admin_password');
                if (! is_string($password) || strlen($password) < 12) {
                    throw new RuntimeException('DEDICATED_ADMIN_PASSWORD debe tener al menos 12 caracteres para el primer acceso.');
                }
                $admin = User::query()->create([
                    'name' => 'Administrador '.$name,
                    'email' => $email,
                    'password' => $password,
                    'role' => User::ROLE_ADMIN,
                ]);
                $admin->forceFill(['email_verified_at' => now()])->save();
            }

            $tenant = Tenant::query()->firstOrCreate(['slug' => $slug], [
                'name' => $name,
                'owner_user_id' => $admin->getKey(),
                'status' => 'active',
                'rubro_profile' => 'base',
                'timezone' => 'America/Argentina/Buenos_Aires',
                'activated_at' => now(),
                'onboarding_completed_at' => now(),
            ]);
            if (! $tenant->users()->whereKey($admin->getKey())->exists()) {
                $tenant->users()->attach($admin->getKey(), [
                    'role' => User::ROLE_OWNER,
                    'is_default' => true,
                    'status' => 'active',
                ]);
            }
            if ($tenant->wasRecentlyCreated) {
                app(TenantFeatureService::class)->enableDefaults($tenant, $admin->getKey());
            }
            app(TenantContext::class)->run($tenant, fn () => ConfiguracionComercio::query()->firstOrCreate([], [
                'nombre_comercio' => $name,
                'color_primario' => '#0369a1',
                'color_sidebar' => '#0c4a6e',
                'mensaje_ticket' => 'Gracias por tu visita.',
            ]));
        });
    }
}
