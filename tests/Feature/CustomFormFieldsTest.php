<?php

namespace Tests\Feature;

use App\Models\CustomFormField;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomFormFieldsTest extends TestCase
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
        Schema::dropIfExists('custom_form_values');
        Schema::dropIfExists('custom_form_fields');
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
            $table->timestamp('email_verified_at')->nullable();
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

        Schema::create('custom_form_fields', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('form_key', 40);
            $table->string('name', 80);
            $table->string('label', 120);
            $table->string('type', 20);
            $table->boolean('required')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->json('config')->nullable();
            $table->timestamps();
        });

        Schema::create('custom_form_values', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('custom_form_field_id')->constrained('custom_form_fields')->cascadeOnDelete();
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    protected function tearDown(): void
    {
        app(TenantContext::class)->clear();
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('custom_form_values');
        Schema::dropIfExists('custom_form_fields');
        Schema::dropIfExists('tenant_user');
        Schema::dropIfExists('tenants');
        Schema::dropIfExists('users');
        Schema::enableForeignKeyConstraints();

        parent::tearDown();
    }

    public function test_can_delete_a_custom_field_when_tenant_is_not_in_context_yet(): void
    {
        [$tenant, $admin] = $this->createTenantAdmin();
        app(TenantContext::class)->set($tenant);

        $field = CustomFormField::query()->create([
            'form_key' => 'clientes',
            'name' => 'edad',
            'label' => 'Edad',
            'type' => 'number',
            'required' => false,
            'sort_order' => 10,
        ]);

        app(TenantContext::class)->clear();

        $this->actingAs($admin)
            ->deleteJson('/api/formularios/clientes/campos/'.$field->id, [], [
                'X-Tenant-Id' => (string) $tenant->id,
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Campo eliminado.');

        $this->assertDatabaseMissing('custom_form_fields', ['id' => $field->id]);
    }

    /**
     * @return array{0: Tenant, 1: User}
     */
    private function createTenantAdmin(): array
    {
        $admin = User::query()->forceCreate([
            'name' => 'Admin Comercio',
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
        ]);

        $tenant = Tenant::query()->create([
            'slug' => 'custom-fields-test',
            'name' => 'Custom Fields Test',
            'features' => [],
            'status' => 'active',
            'onboarding_completed_at' => now(),
        ]);

        $tenant->users()->attach($admin->id, [
            'role' => 'admin',
            'is_default' => true,
            'status' => 'active',
        ]);

        return [$tenant, $admin];
    }
}
