<?php

namespace Tests\Feature;

use App\Http\Controllers\TenantSettingsModuleController;
use App\Models\PlatformAuditEvent;
use App\Models\PlatformModule;
use App\Models\Tenant;
use App\Models\TenantModule;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TenantSettingsModulesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        DB::purge();
        DB::reconnect('sqlite');

        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('platform_audit_events');
        Schema::dropIfExists('tenant_modules');
        Schema::dropIfExists('platform_modules');
        Schema::dropIfExists('tenant_user');
        Schema::dropIfExists('tenants');
        Schema::dropIfExists('users');

        Schema::create('tenants', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('owner_user_id')->nullable();
            $table->uuid('uuid')->unique();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('rubro_profile')->default('base');
            $table->json('features')->nullable();
            $table->string('status')->default('active');
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->text('suspension_reason')->nullable();
            $table->timestamp('onboarding_completed_at')->nullable();
            $table->string('timezone')->default('UTC');
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->default('vendedor');
            $table->boolean('is_platform_superadmin')->default(false);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('tenant_user', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role');
            $table->boolean('is_default')->default(false);
            $table->string('status')->default('active');
            $table->timestamps();
            $table->unique(['tenant_id', 'user_id']);
        });

        Schema::create('platform_modules', function (Blueprint $table): void {
            $table->string('key')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('depends_on')->nullable();
            $table->boolean('default_enabled')->default(false);
            $table->boolean('is_visible')->default(true);
            $table->boolean('is_ready')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('tenant_modules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('module_key');
            $table->boolean('enabled')->default(true);
            $table->json('config')->nullable();
            $table->timestamp('enabled_at')->nullable();
            $table->unsignedBigInteger('enabled_by_user_id')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'module_key']);
        });

        Schema::create('platform_audit_events', function (Blueprint $table): void {
            $table->id();
            $table->uuid('event_uuid')->unique();
            $table->uuid('correlation_id')->index();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('actor_user_id')->nullable()->index();
            $table->string('action')->index();
            $table->string('subject_type')->nullable();
            $table->string('subject_id')->nullable();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->json('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 1024)->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::enableForeignKeyConstraints();

        foreach (config('modules.catalog', []) as $module) {
            PlatformModule::query()->create(collect($module)->except('family')->all() + ['is_visible' => true]);
        }

        Route::middleware('web')->group(function (): void {
            Route::get('/__settings-test/modulos', [TenantSettingsModuleController::class, 'index']);
            Route::put('/__settings-test/modulos', [TenantSettingsModuleController::class, 'update']);
        });
    }

    protected function tearDown(): void
    {
        app(TenantContext::class)->clear();
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('platform_audit_events');
        Schema::dropIfExists('tenant_modules');
        Schema::dropIfExists('platform_modules');
        Schema::dropIfExists('tenant_user');
        Schema::dropIfExists('tenants');
        Schema::dropIfExists('users');
        Schema::enableForeignKeyConstraints();

        parent::tearDown();
    }

    public function test_settings_catalog_marks_base_modules_as_locked(): void
    {
        [$tenant, $admin] = $this->createTenantAdmin();
        app(TenantContext::class)->set($tenant);

        $payload = $this->actingAs($admin)
            ->getJson('/__settings-test/modulos')
            ->assertOk()
            ->json();

        $byKey = collect($payload['modules'])->keyBy('key');

        $this->assertTrue($byKey['core.pos']['locked']);
        $this->assertTrue($byKey['core.pos']['enabled']);
        $this->assertFalse($byKey['sales.open_orders']['locked']);
        $this->assertTrue($byKey['sales.open_orders']['is_ready']);
        $this->assertFalse($byKey['sales.tables']['is_ready']);
        $this->assertFalse($byKey['services.appointments']['enabled']);
    }

    public function test_tenant_can_toggle_optional_modules_without_disabling_the_base(): void
    {
        [$tenant, $admin] = $this->createTenantAdmin();
        app(TenantContext::class)->set($tenant);

        $this->actingAs($admin)
            ->putJson('/__settings-test/modulos', [
                'modules' => ['sales.open_orders', 'services.appointments'],
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Módulos actualizados.');

        $tenant->refresh();

        $this->assertContains('core.pos', $tenant->features);
        $this->assertContains('core.catalog', $tenant->features);
        $this->assertContains('sales.open_orders', $tenant->features);
        $this->assertContains('services.appointments', $tenant->features);
        $this->assertDatabaseHas('tenant_modules', [
            'tenant_id' => $tenant->id,
            'module_key' => 'sales.open_orders',
            'enabled' => true,
        ]);
        $this->assertDatabaseHas('platform_audit_events', [
            'tenant_id' => $tenant->id,
            'action' => 'tenant.settings.modules_updated',
        ]);

        $this->actingAs($admin)
            ->putJson('/__settings-test/modulos', [
                'modules' => ['core.pos'],
            ])
            ->assertOk();

        $this->assertFalse(
            TenantModule::query()
                ->where('tenant_id', $tenant->id)
                ->where('module_key', 'sales.open_orders')
                ->where('enabled', true)
                ->exists()
        );
        $this->assertTrue(
            TenantModule::query()
                ->where('tenant_id', $tenant->id)
                ->where('module_key', 'core.pos')
                ->where('enabled', true)
                ->exists()
        );
    }

    public function test_tenant_cannot_enable_a_module_that_is_not_ready(): void
    {
        [$tenant, $admin] = $this->createTenantAdmin();
        app(TenantContext::class)->set($tenant);

        $this->actingAs($admin)
            ->putJson('/__settings-test/modulos', [
                'modules' => ['sales.tables'],
            ])
            ->assertUnprocessable();
    }

    /**
     * @return array{0: Tenant, 1: User}
     */
    private function createTenantAdmin(): array
    {
        $admin = User::query()->forceCreate([
            'name' => 'Admin Comercio',
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
        ]);

        $tenant = Tenant::query()->create([
            'slug' => 'settings-test',
            'name' => 'Settings Test',
            'features' => [],
            'status' => 'active',
        ]);

        $tenant->users()->attach($admin->id, [
            'role' => 'admin',
            'is_default' => true,
            'status' => 'active',
        ]);

        return [$tenant, $admin];
    }
}
