<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureTenantFeature;
use App\Models\Cliente;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use LogicException;
use RuntimeException;
use Tests\TestCase;

class ClientTenantIsolationTest extends TestCase
{
    private const TABLES = ['cheques', 'deudas_clientes', 'cuentas_corrientes', 'custom_form_values', 'custom_form_fields', 'turnos', 'ventas', 'productos', 'cajas', 'tenant_user', 'clientes', 'users', 'tenants'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertStringContainsString('testing', DB::connection()->getDatabaseName(), 'Use a dedicated testing database.');
        putenv('TENANCY_CLIENT_BACKFILL_TENANT_ID');
        $this->dropSchema();

        Schema::create('tenants', function (Blueprint $table): void {
            $table->id(); $table->uuid('uuid')->unique(); $table->string('slug')->unique();
            $table->string('name'); $table->string('status')->default('active');
            $table->timestamp('onboarding_completed_at')->nullable(); $table->timestamps();
        });
        Schema::create('users', function (Blueprint $table): void {
            $table->id(); $table->string('name'); $table->string('email')->unique();
            $table->string('password'); $table->string('role')->default('admin');
            $table->timestamp('email_verified_at')->nullable(); $table->boolean('is_platform_superadmin')->default(false);
            $table->rememberToken(); $table->timestamps();
        });
        Schema::create('tenant_user', function (Blueprint $table): void {
            $table->id(); $table->foreignId('tenant_id')->constrained(); $table->foreignId('user_id')->constrained();
            $table->string('role')->default('admin'); $table->boolean('is_default')->default(true);
            $table->string('status')->default('active'); $table->timestamps();
        });
        Schema::create('clientes', function (Blueprint $table): void {
            $table->id(); $table->string('nombre'); $table->string('apellido'); $table->string('dni')->unique();
            $table->string('cuit')->nullable(); $table->string('telefono')->nullable(); $table->string('email')->nullable();
            $table->text('direccion')->nullable(); $table->boolean('activo')->default(true); $table->timestamps();
        });
        foreach (['ventas', 'turnos'] as $name) {
            Schema::create($name, function (Blueprint $table): void {
                $table->id(); $table->unsignedBigInteger('tenant_id'); $table->unsignedBigInteger('cliente_id')->nullable();
            });
        }
        Schema::create('cuentas_corrientes', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('tenant_id'); $table->unsignedBigInteger('cliente_id');
        });
        Schema::create('deudas_clientes', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('tenant_id'); $table->unsignedBigInteger('cliente_id');
        });
        foreach (['productos', 'cajas'] as $name) {
            Schema::create($name, function (Blueprint $table): void {
                $table->id(); $table->unsignedBigInteger('tenant_id');
            });
        }
        Schema::create('cheques', function (Blueprint $table): void {
            $table->id(); $table->string('numero_cheque')->unique();
        });
        Schema::create('custom_form_fields', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('tenant_id'); $table->string('form_key'); $table->string('name');
            $table->integer('sort_order')->default(0);
        });
        Schema::create('custom_form_values', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('tenant_id'); $table->unsignedBigInteger('custom_form_field_id');
            $table->string('entity_type'); $table->unsignedBigInteger('entity_id'); $table->text('value')->nullable();
        });
        config()->set('tenancy.legacy_mode', false);
        $this->withoutMiddleware(EnsureTenantFeature::class);
    }

    protected function tearDown(): void
    {
        app(TenantContext::class)->clear();
        putenv('TENANCY_CLIENT_BACKFILL_TENANT_ID');
        $this->dropSchema();
        parent::tearDown();
    }

    public function test_crud_search_and_dni_uniqueness_are_isolated_per_tenant(): void
    {
        [$a, $b, $userA, $userB] = $this->tenantsAndUsers();
        $this->migration()->up();
        $payload = ['nombre' => 'Ana', 'apellido' => 'Ejemplo', 'dni' => 'TEST-123'];
        $responseA = $this->actingAs($userA, 'web')->postJson('/api/clientes', $payload, ['X-Tenant-Id'=>(string)$a->id]);
        $this->assertSame(201, $responseA->status(), (string) $responseA->json('message'));
        $aId = $responseA->json('id');
        $responseB = $this->actingAs($userB, 'web')->postJson('/api/clientes', $payload, ['X-Tenant-Id'=>(string)$b->id]);
        $this->assertSame(201, $responseB->status(), (string) $responseB->json('message'));
        $bId = $responseB->json('id');
        $this->assertNotSame($aId, $bId);

        $this->actingAs($userA, 'web')->getJson('/api/clientes?all=1&search=TEST-123', ['X-Tenant-Id'=>(string)$a->id])->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $aId);
        $this->getJson('/api/clientes')->assertOk()->assertJsonPath('total', 1);
        $this->postJson('/api/clientes', $payload)->assertUnprocessable()->assertJsonValidationErrors('dni');
        $this->putJson('/api/clientes/'.$aId, $payload + ['telefono' => '555'])->assertOk()->assertJsonPath('telefono', '555');
        $this->getJson('/api/clientes/'.$bId)->assertNotFound();
        $this->putJson('/api/clientes/'.$bId, $payload)->assertNotFound();
        $this->deleteJson('/api/clientes/'.$bId)->assertNotFound();
        $this->deleteJson('/api/clientes/'.$aId)->assertNoContent();
        $this->actingAs($userB, 'web')->getJson('/api/clientes/'.$bId, ['X-Tenant-Id'=>(string)$b->id])->assertOk();
        $this->assertDatabaseHas('clientes', ['id' => $bId, 'tenant_id' => $b->id]);
    }

    public function test_relational_endpoints_reject_a_client_from_another_tenant(): void
    {
        [$a, $b, $userA] = $this->tenantsAndUsers();
        $this->migration()->up();
        $foreign = app(TenantContext::class)->run($b, fn () => Cliente::create(['nombre'=>'B', 'apellido'=>'Ejemplo', 'dni'=>'B-1']));
        DB::table('cajas')->insert(['id'=>1, 'tenant_id'=>$a->id]);
        DB::table('productos')->insert(['id'=>1, 'tenant_id'=>$a->id]);
        $this->actingAs($userA, 'web');
        $this->postJson('/api/ventas', ['caja_id'=>1, 'cliente_id'=>$foreign->id, 'items'=>[['producto_id'=>1,'cantidad'=>1]], 'tipo_pago'=>'efectivo'])->assertUnprocessable()->assertJsonValidationErrors('cliente_id');
        $this->postJson('/api/turnos', ['cliente_id'=>$foreign->id,'nombre'=>'Turno de ejemplo','starts_at'=>now()->addDay()->toDateTimeString()])->assertUnprocessable()->assertJsonValidationErrors('cliente_id');
        $this->postJson('/api/cuentas-corrientes', ['cliente_id'=>$foreign->id,'limite_credito'=>100])->assertUnprocessable()->assertJsonValidationErrors('cliente_id');
        $this->postJson('/api/deudas-clientes', ['cliente_id'=>$foreign->id,'monto_total'=>100])->assertUnprocessable()->assertJsonValidationErrors('cliente_id');
        $this->postJson('/api/cheques', ['cliente_id'=>$foreign->id,'numero_cheque'=>'EJEMPLO-1','banco'=>'Ficticio','fecha_ingreso'=>now()->toDateString(),'fecha_vencimiento'=>now()->addDay()->toDateString(),'monto'=>100,'tipo'=>'recibido'])->assertUnprocessable()->assertJsonValidationErrors('cliente_id');
        $this->assertDatabaseCount('ventas', 0);
        $this->assertDatabaseCount('turnos', 0);
        $this->assertDatabaseCount('cuentas_corrientes', 0);
        $this->assertDatabaseCount('deudas_clientes', 0);
        $this->assertDatabaseCount('cheques', 0);
    }

    public function test_client_queries_fail_closed_without_context_and_creation_sets_tenant(): void
    {
        [$a] = $this->tenantsAndUsers();
        $this->migration()->up();
        app(TenantContext::class)->run($a, fn () => Cliente::create(['nombre'=>'A','apellido'=>'Ejemplo','dni'=>'A-1']));
        $this->assertSame(0, Cliente::count());
        $this->expectException(LogicException::class);
        Cliente::create(['nombre'=>'Sin contexto','apellido'=>'Ejemplo','dni'=>'X-1']);
    }

    public function test_backfill_uses_existing_ownership_and_migration_can_be_reversed(): void
    {
        [$a, $b] = $this->tenantsAndUsers();
        DB::table('clientes')->insert([['id'=>1,'nombre'=>'A','apellido'=>'Ejemplo','dni'=>'A'],['id'=>2,'nombre'=>'B','apellido'=>'Ejemplo','dni'=>'B']]);
        DB::table('ventas')->insert(['tenant_id'=>$a->id,'cliente_id'=>1]);
        DB::table('turnos')->insert(['tenant_id'=>$b->id,'cliente_id'=>2]);
        $migration=$this->migration(); $migration->up();
        $this->assertDatabaseHas('clientes',['id'=>1,'tenant_id'=>$a->id]);
        $this->assertDatabaseHas('clientes',['id'=>2,'tenant_id'=>$b->id]);
        $migration->down();
        $this->assertFalse(Schema::hasColumn('clientes','tenant_id'));
        $migration->up();
        $this->assertDatabaseCount('clientes',2);
    }

    public function test_ambiguous_backfill_is_rejected_before_changing_schema(): void
    {
        [$a, $b] = $this->tenantsAndUsers();
        DB::table('clientes')->insert(['id'=>1,'nombre'=>'Compartido','apellido'=>'Ejemplo','dni'=>'X']);
        DB::table('ventas')->insert(['tenant_id'=>$a->id,'cliente_id'=>1]);
        DB::table('turnos')->insert(['tenant_id'=>$b->id,'cliente_id'=>1]);
        try { $this->migration()->up(); $this->fail('Ambiguous ownership was accepted'); }
        catch (RuntimeException $e) { $this->assertStringContainsString('varios comercios', $e->getMessage()); }
        $this->assertFalse(Schema::hasColumn('clientes','tenant_id'));
        $this->assertDatabaseCount('clientes',1);
    }

    public function test_unreferenced_client_requires_explicit_mapping_when_multiple_tenants_exist(): void
    {
        [$a] = $this->tenantsAndUsers();
        DB::table('clientes')->insert(['nombre'=>'Sin referencias','apellido'=>'Ejemplo','dni'=>'X']);
        try { $this->migration()->up(); $this->fail('Unowned client was assigned arbitrarily'); }
        catch (RuntimeException $e) { $this->assertStringContainsString('No se puede determinar', $e->getMessage()); }
        $this->assertFalse(Schema::hasColumn('clientes','tenant_id'));
        putenv('TENANCY_CLIENT_BACKFILL_TENANT_ID='.$a->id);
        $this->migration()->up();
        $this->assertDatabaseHas('clientes',['dni'=>'X','tenant_id'=>$a->id]);
    }

    public function test_database_unique_is_local_and_rollback_refuses_duplicate_dni(): void
    {
        [$a, $b] = $this->tenantsAndUsers();
        $this->migration()->up();
        foreach ([$a,$b] as $tenant) { app(TenantContext::class)->run($tenant,fn()=>Cliente::create(['nombre'=>'Ejemplo','apellido'=>'Cliente','dni'=>'COMPARTIDO'])); }
        try { $this->migration()->down(); $this->fail('Unsafe rollback was accepted'); }
        catch (RuntimeException $e) { $this->assertStringContainsString('DNI repetidos', $e->getMessage()); }
        $this->assertTrue(Schema::hasColumn('clientes','tenant_id'));
        $this->expectException(QueryException::class);
        DB::table('clientes')->insert(['nombre'=>'Duplicado','apellido'=>'Cliente','dni'=>'COMPARTIDO','tenant_id'=>$a->id]);
    }

    private function tenantsAndUsers(): array
    {
        $tenants=[]; $users=[];
        foreach (['a','b'] as $slug) {
            $tenant=Tenant::create(['slug'=>'test-'.$slug,'name'=>'Comercio '.$slug,'status'=>'active','onboarding_completed_at'=>now()]);
            $user=User::create(['name'=>'Operador '.$slug,'email'=>$slug.'@example.test','password'=>'fixture-password','role'=>'admin']);
            $user->forceFill(['email_verified_at'=>now()])->save();
            $tenant->users()->attach($user->id,['role'=>'admin','status'=>'active','is_default'=>true]);
            $tenants[]=$tenant; $users[]=$user;
        }
        return [...$tenants,...$users];
    }

    private function migration(): object
    {
        return require database_path('migrations/2026_10_12_000001_isolate_clientes_by_tenant.php');
    }

    private function dropSchema(): void
    {
        Schema::disableForeignKeyConstraints();
        foreach (self::TABLES as $name) { Schema::dropIfExists($name); }
        Schema::enableForeignKeyConstraints();
    }
}
