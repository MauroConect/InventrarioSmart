<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\TenantOnboarding;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AssistedOnboardingTest extends TestCase
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
        config()->set('tenancy.legacy_mode', false);
        DB::purge();
        DB::reconnect('sqlite');

        Schema::disableForeignKeyConstraints();
        foreach ([
            'onboarding_status_events',
            'onboarding_notes',
            'tenant_modules',
            'platform_modules',
            'platform_audit_events',
            'tenant_onboardings',
            'tenant_user',
            'tenants',
            'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('role')->default('vendedor');
            $table->boolean('is_platform_superadmin')->default(false);
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('tenants', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('owner_user_id')->nullable();
            $table->uuid('uuid')->unique();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('rubro_profile')->default('base');
            $table->json('features')->nullable();
            $table->string('status')->default('active');
            $table->string('timezone')->default('UTC');
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->text('suspension_reason')->nullable();
            $table->timestamp('onboarding_completed_at')->nullable();
            $table->string('repository_url')->nullable();
            $table->string('core_version')->nullable();
            $table->string('environment_url')->nullable();
            $table->text('delivery_notes')->nullable();
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
        });
        Schema::create('tenant_onboardings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('step')->default('verify_email');
            $table->string('status')->default('registered');
            $table->json('draft')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->string('next_action')->nullable();
            $table->timestamp('next_action_at')->nullable();
            $table->timestamp('discovery_scheduled_at')->nullable();
            $table->text('last_status_comment')->nullable();
            $table->string('suggested_profile')->nullable();
            $table->json('suggested_modules')->nullable();
            $table->text('customer_message')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
        Schema::create('onboarding_notes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_onboarding_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->boolean('is_internal')->default(true);
            $table->timestamps();
        });
        Schema::create('onboarding_status_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_onboarding_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->text('comment')->nullable();
            $table->string('next_action')->nullable();
            $table->timestamps();
        });
        Schema::create('platform_modules', function (Blueprint $table): void {
            $table->string('key')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('depends_on')->nullable();
            $table->boolean('default_enabled')->default(false);
            $table->boolean('is_visible')->default(true);
            $table->boolean('is_ready')->default(true);
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
        });
        Schema::create('platform_audit_events', function (Blueprint $table): void {
            $table->id();
            $table->uuid('event_uuid')->unique();
            $table->uuid('correlation_id');
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->string('action');
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
    }

    public function test_customer_registers_answers_questionnaire_and_cannot_self_activate(): void
    {
        Notification::fake();

        $this->post('/register', [
            'name' => 'Dueña Demo',
            'email' => 'duena@example.test',
            'password' => 'secreto123',
            'password_confirmation' => 'secreto123',
        ])->assertRedirect(route('verification.notice'));

        $user = User::query()->where('email', 'duena@example.test')->firstOrFail();
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmail::class);

        $user->forceFill(['email_verified_at' => now()])->save();
        $this->actingAs($user->fresh())
            ->get('/onboarding')
            ->assertOk()
            ->assertSee('Contanos cómo trabaja tu comercio');

        $payload = $this->answers();
        $this->actingAs($user)->post('/onboarding', $payload + ['intent' => 'draft'])->assertRedirect();
        $this->actingAs($user)->post('/onboarding', $payload + ['intent' => 'submit'])->assertRedirect(route('onboarding.show'));

        $onboarding = TenantOnboarding::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame('review_pending', $onboarding->status);
        $this->assertNotNull($onboarding->submitted_at);
        $this->assertContains('catalog.weighted', $onboarding->suggested_modules);

        $this->actingAs($user)->get('/app')->assertRedirect(route('onboarding.show'));
        $this->assertSame(0, Tenant::query()->count());
    }

    public function test_platform_provisions_and_activates_the_multitenant_commerce(): void
    {
        $user = User::query()->create([
            'name' => 'Dueña Demo',
            'email' => 'duena2@example.test',
            'password' => 'secreto123',
            'role' => User::ROLE_ADMIN,
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();
        $onboarding = TenantOnboarding::query()->create([
            'user_id' => $user->id,
            'step' => 'waiting',
            'status' => 'review_pending',
            'draft' => ['answers' => $this->answers()],
            'submitted_at' => now(),
            'suggested_profile' => 'heladeria',
            'suggested_modules' => ['core.catalog', 'core.pos', 'core.cash', 'sales.open_orders', 'platform.support'],
        ]);
        $admin = User::query()->create([
            'name' => 'Plataforma',
            'email' => 'platform@example.test',
            'password' => 'secreto123',
            'role' => User::ROLE_ADMIN,
        ]);
        $admin->forceFill([
            'email_verified_at' => now(),
            'is_platform_superadmin' => true,
        ])->save();

        $this->actingAs($admin)
            ->post('/platform/onboarding/'.$onboarding->id.'/provision', [
                'name' => 'Heladería Sur',
                'slug' => 'heladeria-sur',
                'rubro_profile' => 'heladeria',
                'timezone' => 'America/Argentina/Buenos_Aires',
                'modules' => ['core.catalog', 'core.pos', 'core.cash', 'platform.support'],
                'repository_url' => 'https://github.com/example/client-heladeria-sur',
                'core_version' => 'v1.0.0',
            ])
            ->assertRedirect();

        $tenant = Tenant::query()->where('slug', 'heladeria-sur')->firstOrFail();
        $this->assertSame('provisioning', $tenant->status);
        $this->assertSame($user->id, $tenant->owner_user_id);
        $this->assertSame('https://github.com/example/client-heladeria-sur', $tenant->repository_url);

        $this->actingAs($user->fresh())->get('/app')->assertRedirect(route('onboarding.show'));

        $this->actingAs($admin)
            ->patch('/platform/onboarding/'.$onboarding->id.'/status', [
                'status' => 'active',
                'comment' => 'Entrega validada',
                'next_action' => 'Soporte continuo',
            ])
            ->assertRedirect();

        $this->assertSame('active', $tenant->fresh()->status);
        $this->assertNotNull($tenant->fresh()->onboarding_completed_at);
        $this->assertNotNull($onboarding->fresh()->completed_at);
        $this->actingAs($user->fresh())->get('/onboarding')->assertRedirect(route('home'));
    }

    /**
     * @return array<string, mixed>
     */
    private function answers(): array
    {
        return [
            'business_name' => 'Heladería Sur',
            'locality' => 'Rosario',
            'contact_phone' => '3410000000',
            'rubro' => 'heladeria',
            'secondary_activities' => 'cafetería',
            'branches_count' => 1,
            'cash_registers_count' => 2,
            'users_count' => 4,
            'monthly_sales' => 2500,
            'products_count' => 80,
            'sale_types' => ['unit', 'weight'],
            'peripherals' => ['scanner'],
            'stock_management' => 'yes',
            'open_orders' => 'yes',
            'uses_tables' => 'no',
            'appointments' => 'no',
            'credit_accounts' => 'no',
            'cheques' => 'no',
            'identify_customers' => 'yes',
            'electronic_invoicing' => 'no',
        ];
    }
}
