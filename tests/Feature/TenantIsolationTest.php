<?php

namespace Tests\Feature;

use App\Http\Middleware\SetTenantContext;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use LogicException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('tenant_test_records');
        Schema::dropIfExists('tenant_user');
        Schema::dropIfExists('tenants');
        Schema::dropIfExists('users');

        Schema::create('tenants', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('rubro_profile')->default('base');
            $table->json('features')->nullable();
            $table->string('status')->default('active');
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

        Schema::create('tenant_test_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->string('name');
            $table->timestamps();
        });
        Schema::enableForeignKeyConstraints();
    }

    protected function tearDown(): void
    {
        app(TenantContext::class)->clear();
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('tenant_test_records');
        Schema::dropIfExists('tenant_user');
        Schema::dropIfExists('tenants');
        Schema::dropIfExists('users');
        Schema::enableForeignKeyConstraints();

        parent::tearDown();
    }

    public function test_global_scope_isolates_records_and_fails_closed(): void
    {
        [$tenantA, $tenantB] = $this->createTenants();
        $context = app(TenantContext::class);

        $context->run($tenantA, fn () => TenantTestRecord::query()->create(['name' => 'A']));
        $context->run($tenantB, fn () => TenantTestRecord::query()->create(['name' => 'B']));

        $this->assertSame(
            ['A'],
            $context->run($tenantA, fn () => TenantTestRecord::query()->pluck('name')->all())
        );
        $this->assertSame(
            ['B'],
            $context->run($tenantB, fn () => TenantTestRecord::query()->pluck('name')->all())
        );
        $this->assertSame(0, TenantTestRecord::query()->count());
        $this->assertSame(
            2,
            $context->runWithoutScope(fn () => TenantTestRecord::query()->count())
        );

        $this->expectException(LogicException::class);
        TenantTestRecord::query()->create(['name' => 'sin tenant']);
    }

    public function test_role_is_resolved_from_active_tenant_membership(): void
    {
        [$tenantA, $tenantB] = $this->createTenants();
        $user = User::query()->create([
            'name' => 'Operador',
            'email' => 'operador@example.test',
            'password' => 'secret',
            'role' => 'vendedor',
        ]);

        $user->tenants()->attach($tenantA->id, ['role' => 'admin', 'status' => 'active']);
        $user->tenants()->attach($tenantB->id, ['role' => 'vendedor', 'status' => 'active']);

        $context = app(TenantContext::class);

        $this->assertTrue($context->run($tenantA, fn () => $user->isAdmin()));
        $this->assertFalse($context->run($tenantB, fn () => $user->isAdmin()));
    }

    public function test_middleware_accepts_membership_and_clears_context(): void
    {
        [$tenantA] = $this->createTenants();
        $user = User::query()->create([
            'name' => 'Operador',
            'email' => 'middleware@example.test',
            'password' => 'secret',
            'role' => 'vendedor',
        ]);
        $user->tenants()->attach($tenantA->id, ['role' => 'admin', 'status' => 'active']);

        $request = Request::create('/api/tenant-context', 'GET', server: [
            'HTTP_X_TENANT_ID' => $tenantA->uuid,
        ]);
        $request->setUserResolver(fn () => $user);

        $context = app(TenantContext::class);
        $middleware = app(SetTenantContext::class);
        $response = $middleware->handle($request, function () use ($context, $tenantA) {
            $this->assertSame($tenantA->id, $context->id());

            return response()->json(['ok' => true]);
        });

        $this->assertSame(200, $response->getStatusCode());
        $this->assertFalse($context->hasTenant());
    }

    public function test_middleware_rejects_cross_tenant_access(): void
    {
        [$tenantA, $tenantB] = $this->createTenants();
        $user = User::query()->create([
            'name' => 'Operador',
            'email' => 'blocked@example.test',
            'password' => 'secret',
            'role' => 'vendedor',
        ]);
        $user->tenants()->attach($tenantA->id, ['role' => 'admin', 'status' => 'active']);

        $request = Request::create('/api/tenant-context', 'GET', server: [
            'HTTP_X_TENANT_ID' => $tenantB->uuid,
        ]);
        $request->setUserResolver(fn () => $user);

        try {
            app(SetTenantContext::class)->handle($request, fn () => response()->json(['ok' => true]));
            $this->fail('El middleware permitió acceso a un tenant sin membresía.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    public function test_user_can_select_only_an_active_membership(): void
    {
        [$tenantA, $tenantB] = $this->createTenants();
        $user = User::query()->create([
            'name' => 'Operador',
            'email' => 'selector@example.test',
            'password' => 'secret',
            'role' => 'vendedor',
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();
        $user->tenants()->attach($tenantA->id, ['role' => 'admin', 'status' => 'active']);

        $this->actingAs($user)
            ->post('/select-tenant', ['tenant_id' => $tenantA->id])
            ->assertRedirect('/app');
        $this->assertSame(
            $tenantA->id,
            session(config('tenancy.session_key', 'tenant_id'))
        );

        $this->actingAs($user)
            ->post('/select-tenant', ['tenant_id' => $tenantB->id])
            ->assertForbidden();
    }

    private function createTenants(): array
    {
        return [
            Tenant::query()->create(['slug' => 'tenant-a', 'name' => 'Tenant A']),
            Tenant::query()->create(['slug' => 'tenant-b', 'name' => 'Tenant B']),
        ];
    }
}

class TenantTestRecord extends Model
{
    use BelongsToTenant;

    protected $table = 'tenant_test_records';

    protected $fillable = ['name'];
}
