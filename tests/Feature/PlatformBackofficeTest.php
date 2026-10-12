<?php

namespace Tests\Feature;

use App\Http\Controllers\PlatformAuditController;
use App\Http\Controllers\PlatformDashboardController;
use App\Http\Controllers\PlatformModuleController;
use App\Http\Controllers\PlatformOnboardingController;
use App\Http\Controllers\PlatformTenantController;
use App\Models\PlatformAuditEvent;
use App\Models\PlatformModule;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PlatformAuditLogger;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use LogicException;
use Tests\TestCase;

class PlatformBackofficeTest extends TestCase
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
            PlatformModule::query()->create($module + ['is_visible' => true]);
        }

        Route::middleware('web')->group(function (): void {
            Route::get('/__platform-test/dashboard', [PlatformDashboardController::class, 'index']);
            Route::get('/__platform-test/tenants', [PlatformTenantController::class, 'index']);
            Route::get('/__platform-test/tenants/{tenant}', [PlatformTenantController::class, 'show']);
            Route::get('/__platform-test/modules', [PlatformModuleController::class, 'index']);
            Route::get('/__platform-test/onboarding', [PlatformOnboardingController::class, 'create']);
            Route::get('/__platform-test/audit', [PlatformAuditController::class, 'index']);
            Route::put('/__platform-test/tenants/{tenant}/modules', [PlatformModuleController::class, 'update']);
            Route::patch('/__platform-test/tenants/{tenant}/suspend', [PlatformTenantController::class, 'suspend']);
            Route::patch('/__platform-test/tenants/{tenant}/reactivate', [PlatformTenantController::class, 'reactivate']);
            Route::post('/__platform-test/onboarding', [PlatformOnboardingController::class, 'store']);
        });
    }

    protected function tearDown(): void
    {
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

    public function test_audit_logger_redacts_secrets_and_events_are_append_only(): void
    {
        $actor = $this->createSuperadmin();
        $tenant = Tenant::query()->create(['slug' => 'audit-test', 'name' => 'Audit Test']);
        $request = Request::create('/platform/test', 'POST', server: [
            'HTTP_X_REQUEST_ID' => 'c146c741-12a7-41a6-a828-a9ac82b1a5db',
            'HTTP_USER_AGENT' => 'Platform test',
        ]);
        $request->setUserResolver(fn () => $actor);

        $event = app(PlatformAuditLogger::class)->log(
            $request,
            'platform.test.changed',
            $tenant,
            $tenant,
            before: ['password' => 'old-secret'],
            after: ['nested' => ['access_token' => 'new-secret'], 'status' => 'active'],
        );

        $this->assertSame('[REDACTED]', $event->before['password']);
        $this->assertSame('[REDACTED]', $event->after['nested']['access_token']);
        $this->assertSame('active', $event->after['status']);
        $this->assertSame($actor->id, $event->actor_user_id);
        $this->assertSame($tenant->id, $event->tenant_id);

        try {
            $event->update(['action' => 'platform.test.tampered']);
            $this->fail('El evento de auditoría pudo modificarse.');
        } catch (LogicException) {
            $this->assertDatabaseHas('platform_audit_events', [
                'id' => $event->id,
                'action' => 'platform.test.changed',
            ]);
        }

        $event->refresh();

        try {
            $event->delete();
            $this->fail('El evento de auditoría pudo eliminarse.');
        } catch (LogicException) {
            $this->assertDatabaseHas('platform_audit_events', ['id' => $event->id]);
        }
    }

    public function test_module_grants_are_persisted_and_audited(): void
    {
        $actor = $this->createSuperadmin();
        $tenant = Tenant::query()->create([
            'slug' => 'modules-test',
            'name' => 'Modules Test',
            'features' => [],
        ]);

        $this->actingAs($actor)
            ->put('/__platform-test/tenants/'.$tenant->id.'/modules', [
                'modules' => ['core.catalog', 'core.pos'],
            ])
            ->assertRedirect();

        $tenant->refresh();

        $this->assertSame(['core.catalog', 'core.pos'], $tenant->features);
        $this->assertDatabaseHas('tenant_modules', [
            'tenant_id' => $tenant->id,
            'module_key' => 'core.catalog',
            'enabled' => true,
        ]);
        $this->assertDatabaseHas('tenant_modules', [
            'tenant_id' => $tenant->id,
            'module_key' => 'core.pos',
            'enabled' => true,
        ]);
        $this->assertDatabaseHas('platform_audit_events', [
            'tenant_id' => $tenant->id,
            'action' => 'platform.tenant.modules_updated',
        ]);
    }

    public function test_tenant_suspension_and_reactivation_are_audited(): void
    {
        $actor = $this->createSuperadmin();
        $tenant = Tenant::query()->create([
            'slug' => 'status-test',
            'name' => 'Status Test',
            'status' => 'active',
        ]);

        $this->actingAs($actor)
            ->patch('/__platform-test/tenants/'.$tenant->id.'/suspend', [
                'reason' => 'Validación administrativa',
            ])
            ->assertRedirect();

        $tenant->refresh();
        $this->assertSame('suspended', $tenant->status);
        $this->assertSame('Validación administrativa', $tenant->suspension_reason);
        $this->assertNotNull($tenant->suspended_at);

        $this->actingAs($actor)
            ->patch('/__platform-test/tenants/'.$tenant->id.'/reactivate')
            ->assertRedirect();

        $tenant->refresh();
        $this->assertSame('active', $tenant->status);
        $this->assertNull($tenant->suspension_reason);
        $this->assertNull($tenant->suspended_at);
        $this->assertEqualsCanonicalizing([
            'platform.tenant.suspended',
            'platform.tenant.reactivated',
        ], PlatformAuditEvent::query()->where('tenant_id', $tenant->id)->pluck('action')->all());
    }

    public function test_onboarding_audits_tenant_identity_and_membership_mutations(): void
    {
        $actor = $this->createSuperadmin();

        $this->actingAs($actor)
            ->post('/__platform-test/onboarding', [
                'name' => 'Comercio Nuevo',
                'slug' => 'comercio-nuevo',
                'rubro_profile' => 'base',
                'timezone' => 'America/Argentina/Buenos_Aires',
                'owner_name' => 'Dueña Inicial',
                'owner_email' => 'owner@example.test',
                'owner_password' => 'password-seguro',
                'modules' => ['core.catalog', 'core.pos', 'core.cash'],
            ])
            ->assertRedirect();

        $tenant = Tenant::query()->where('slug', 'comercio-nuevo')->firstOrFail();
        $owner = User::query()->where('email', 'owner@example.test')->firstOrFail();

        $this->assertDatabaseHas('tenant_user', [
            'tenant_id' => $tenant->id,
            'user_id' => $owner->id,
            'role' => 'owner',
            'status' => 'active',
        ]);
        $this->assertSame($owner->id, $tenant->owner_user_id);
        $this->assertSame(4, PlatformAuditEvent::query()->where('tenant_id', $tenant->id)->count());
        $this->assertEqualsCanonicalizing([
            'platform.tenant.created',
            'platform.user.created_for_onboarding',
            'platform.membership.created',
            'platform.tenant.modules_updated',
        ], PlatformAuditEvent::query()->where('tenant_id', $tenant->id)->pluck('action')->all());
    }

    public function test_platform_views_render_without_platform_routes_being_registered(): void
    {
        $actor = $this->createSuperadmin();
        $tenant = Tenant::query()->create([
            'slug' => 'view-test',
            'name' => 'View Test',
            'features' => ['core.catalog'],
        ]);

        $this->actingAs($actor)->get('/__platform-test/dashboard')->assertOk();
        $this->actingAs($actor)->get('/__platform-test/tenants')->assertOk()->assertSee('View Test');
        $this->actingAs($actor)->get('/__platform-test/tenants/'.$tenant->id)->assertOk()->assertSee('View Test');
        $this->actingAs($actor)->get('/__platform-test/modules')->assertOk()->assertSee('Módulos y grants');
        $this->actingAs($actor)->get('/__platform-test/onboarding')->assertOk()->assertSee('Onboarding de comercio');
        $this->actingAs($actor)->get('/__platform-test/audit')->assertOk()->assertSee('Auditoría de plataforma');
    }

    private function createSuperadmin(): User
    {
        return User::query()->forceCreate([
            'name' => 'Platform Admin',
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
            'is_platform_superadmin' => true,
        ]);
    }
}
