<?php

namespace Tests\Feature;

use App\Models\Cheque;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use LogicException;
use RuntimeException;
use Tests\TestCase;

class ChequeTenantIsolationTest extends TestCase
{
    private const TABLES = ['cheques', 'tenant_modules', 'proveedores', 'cajas', 'clientes', 'tenant_user', 'users', 'tenants'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertStringContainsString('testing', DB::connection()->getDatabaseName(), 'Use a dedicated testing database.');
        $this->dropSchema();
        Schema::create('tenants', function (Blueprint $t): void {
            $t->id();
            $t->uuid('uuid')->unique();
            $t->string('slug')->unique();
            $t->string('name');
            $t->string('status')->default('active');
            $t->timestamp('onboarding_completed_at')->nullable();
            $t->timestamps();
        });
        Schema::create('users', function (Blueprint $t): void {
            $t->id();
            $t->string('name');
            $t->string('email')->unique();
            $t->string('password');
            $t->string('role')->default('admin');
            $t->boolean('is_platform_superadmin')->default(false);
            $t->timestamp('email_verified_at')->nullable();
            $t->rememberToken();
            $t->timestamps();
        });
        Schema::create('tenant_user', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('tenant_id')->constrained();
            $t->foreignId('user_id')->constrained();
            $t->string('role')->default('admin');
            $t->boolean('is_default')->default(true);
            $t->string('status')->default('active');
            $t->timestamps();
        });
        Schema::create('tenant_modules', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('tenant_id');
            $t->string('module_key');
            $t->boolean('enabled');
        });
        Schema::create('clientes', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('tenant_id');
            $t->string('nombre');
            $t->string('apellido');
        });
        Schema::create('proveedores', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('tenant_id');
            $t->string('nombre');
        });
        Schema::create('cajas', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('tenant_id');
        });
        (require database_path('migrations/2026_01_10_183050_create_cheques_table.php'))->up();
        foreach ([1, 2] as $id) {
            $tenant = Tenant::create(['slug' => 'cheques-'.$id, 'name' => 'Comercio '.$id, 'onboarding_completed_at' => now()]);
            $user = User::create(['name' => 'Operador ficticio '.$id, 'email' => 'cheques'.$id.'@example.test', 'password' => 'fixture-password', 'role' => 'admin']);
            $user->forceFill(['email_verified_at' => now()])->save();
            $tenant->users()->attach($user->id, ['role' => 'admin', 'status' => 'active', 'is_default' => true]);
            DB::table('tenant_modules')->insert(['tenant_id' => $id, 'module_key' => 'finance.cheques', 'enabled' => true]);
            DB::table('clientes')->insert(['id' => $id, 'tenant_id' => $id, 'nombre' => 'Cliente '.$id, 'apellido' => 'Ejemplo']);
            DB::table('proveedores')->insert(['id' => $id, 'tenant_id' => $id, 'nombre' => 'Proveedor '.$id]);
            DB::table('cajas')->insert(['id' => $id, 'tenant_id' => $id]);
            DB::table('cheques')->insert($this->payload($id, 'BASE-'.$id) + ['id' => $id, 'usuario_id' => $user->id]);
        }
        config()->set('tenancy.legacy_mode', false);
    }

    protected function tearDown(): void
    {
        app(TenantContext::class)->clear();
        $this->dropSchema();
        parent::tearDown();
    }

    public function test_lists_search_details_dates_and_statistics_are_isolated(): void
    {
        $this->migration()->up();
        foreach ([1, 2] as $id) {
            $other = 3 - $id;
            $this->login($id);
            $this->getJson('/api/cheques')->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.id', $id);
            $this->getJson('/api/cheques?search=BASE-'.$other)->assertOk()->assertJsonPath('total', 0);
            $this->getJson('/api/cheques/'.$id)->assertOk()->assertJsonPath('tenant_id', $id);
            $this->getJson('/api/cheques/'.$other)->assertNotFound();
            $this->getJson('/api/cheques-proximos-vencer')->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $id);
            $this->getJson('/api/cheques-por-fecha?fecha='.today()->toDateString())->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $id);
            $this->getJson('/api/cheques-por-mes')->assertOk()->assertJsonPath(today()->toDateString().'.0.id', $id);
            $stats = $this->getJson('/api/cheques-estadisticas')->assertOk()->assertJsonPath('total', 1)->assertJsonPath('proximos_vencer', 1)->json();
            $this->assertEquals($id * 100, $stats['monto_total_pendiente']);
        }
    }

    public function test_numbers_are_unique_per_tenant_and_foreign_mutations_are_rejected(): void
    {
        $this->migration()->up();
        foreach ([1, 2] as $id) {
            $this->login($id);
            $this->postJson('/api/cheques', $this->payload($id, 'COMPARTIDO'))->assertCreated()->assertJsonPath('tenant_id', $id);
            $this->postJson('/api/cheques', $this->payload($id, 'COMPARTIDO'))->assertUnprocessable()->assertJsonValidationErrors('numero_cheque');
            $other = 3 - $id;
            $this->putJson('/api/cheques/'.$other, $this->payload($id, 'CAMBIO'))->assertNotFound();
            $this->postJson('/api/cheques/'.$other.'/marcar-cobrado', [])->assertNotFound();
            $this->deleteJson('/api/cheques/'.$other)->assertNotFound();
            $this->assertDatabaseHas('cheques', ['id' => $other, 'estado' => 'pendiente']);
        }
        $this->login(1);
        $this->putJson('/api/cheques/1', $this->payload(1, 'BASE-1'))->assertOk();
        $this->postJson('/api/cheques/1/marcar-cobrado', [])->assertOk()->assertJsonPath('estado', 'cobrado');
        $this->deleteJson('/api/cheques/1')->assertNoContent();
        $this->assertDatabaseHas('cheques', ['id' => 2, 'estado' => 'pendiente']);
    }

    public function test_foreign_client_provider_or_cash_register_are_rejected(): void
    {
        $this->migration()->up();
        $this->login(1);
        foreach (['cliente_id', 'proveedor_id', 'caja_id'] as $column) {
            $payload = $this->payload(1, 'INVALIDO');
            if ($column === 'proveedor_id') {
                unset($payload['cliente_id']);
            }
            $payload[$column] = 2;
            $this->postJson('/api/cheques', $payload)->assertUnprocessable()->assertJsonValidationErrors($column);
            $this->putJson('/api/cheques/1', $payload)->assertUnprocessable()->assertJsonValidationErrors($column);
        }
        $this->assertDatabaseCount('cheques', 2);
        $this->assertDatabaseHas('cheques', ['id' => 1, 'numero_cheque' => 'BASE-1']);
    }

    public function test_backfill_preserves_amounts_and_states_and_supports_rollback(): void
    {
        $before = DB::table('cheques')->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
        $migration = $this->migration();
        $migration->up();
        foreach ([1, 2] as $id) {
            $this->assertDatabaseHas('cheques', ['id' => $id, 'tenant_id' => $id, 'monto' => $id * 100]);
        }
        $migration->down();
        $after = DB::table('cheques')->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
        $this->assertSame($before, $after);
        $migration->up();
        $this->assertDatabaseCount('cheques', 2);
    }

    public function test_ambiguous_and_unowned_backfills_are_rejected_before_ddl(): void
    {
        DB::table('cheques')->where('id', 1)->update(['caja_id' => 2]);
        try {
            $this->migration()->up();
            $this->fail('Mixed owners accepted');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('ambigua', $e->getMessage());
        }
        $this->assertFalse(Schema::hasColumn('cheques', 'tenant_id'));
        DB::table('cheques')->where('id', 1)->update(['caja_id' => null, 'cliente_id' => null]);
        try {
            $this->migration()->up();
            $this->fail('No owner accepted');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('sin referencias', $e->getMessage());
        }
        $this->assertFalse(Schema::hasColumn('cheques', 'tenant_id'));
        $this->assertDatabaseHas('cheques', ['id' => 1, 'monto' => 100]);
    }

    public function test_queries_and_model_writes_fail_closed_and_unsafe_rollback_is_rejected(): void
    {
        $this->migration()->up();
        $this->assertSame(0, Cheque::count());
        $context = app(TenantContext::class);
        foreach ([1, 2] as $id) {
            $context->run(Tenant::findOrFail($id), fn () => Cheque::create($this->payload($id, 'MISMO') + ['usuario_id' => $id]));
        }
        try {
            $this->migration()->down();
            $this->fail('Unsafe rollback accepted');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('repetidos', $e->getMessage());
        }
        $this->assertTrue(Schema::hasColumn('cheques', 'tenant_id'));
        $context->set(Tenant::findOrFail(1));
        try {
            Cheque::create($this->payload(2, 'CRUCE') + ['usuario_id' => 1]);
            $this->fail('Foreign relation accepted');
        } catch (LogicException $e) {
            $this->assertStringContainsString('no pertenece', $e->getMessage());
        }
        $context->clear();
        $this->expectException(LogicException::class);
        Cheque::create($this->payload(1, 'SIN-CONTEXTO') + ['usuario_id' => 1]);
    }

    public function test_disabled_module_and_unprivileged_membership_remain_blocked(): void
    {
        $this->migration()->up();
        $this->login(1);
        DB::table('tenant_modules')->where('tenant_id', 1)->update(['enabled' => false]);
        $this->getJson('/api/cheques')->assertNotFound();
        DB::table('tenant_modules')->where('tenant_id', 1)->update(['enabled' => true]);
        DB::table('tenant_user')->where('tenant_id', 1)->update(['role' => 'vendedor']);
        $this->login(1);
        $this->getJson('/api/cheques')->assertForbidden();
    }

    public function test_a_cheque_due_today_is_upcoming_and_is_not_overdue(): void
    {
        $this->migration()->up();
        DB::table('cheques')->insert($this->payload(1, 'VENCIDO') + ['tenant_id' => 1, 'usuario_id' => 1]);
        DB::table('cheques')->where('numero_cheque', 'VENCIDO')->update(['fecha_vencimiento' => today()->subDay()->toDateString()]);
        $this->login(1);
        $this->getJson('/api/cheques?proximos_vencer=1')->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.id', 1);
        $this->getJson('/api/cheques?proximos_vencer=1&vencidos=1')->assertOk()->assertJsonPath('total', 0);
        $this->getJson('/api/cheques-estadisticas')->assertOk()->assertJsonPath('vencidos', 1)->assertJsonPath('proximos_vencer', 1);
        app(TenantContext::class)->run(Tenant::findOrFail(1), function (): void {
            $this->assertFalse(Cheque::findOrFail(1)->estaVencido());
            $this->assertTrue(Cheque::findOrFail(1)->estaProximoAVencer());
        });
    }

    private function payload(int $tenant, string $number): array
    {
        return ['numero_cheque' => $number, 'banco' => 'Banco ficticio', 'fecha_ingreso' => today()->toDateString(), 'fecha_vencimiento' => today()->toDateString(), 'monto' => $tenant * 100, 'tipo' => 'recibido', 'cliente_id' => $tenant];
    }

    private function login(int $id): void
    {
        $this->actingAs(User::findOrFail($id), 'web');
        $this->withHeader('X-Tenant-Id', (string) $id);
    }

    private function migration(): object
    {
        return require database_path('migrations/2026_10_12_000003_isolate_cheques_by_tenant.php');
    }

    private function dropSchema(): void
    {
        Schema::disableForeignKeyConstraints();
        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }
        Schema::enableForeignKeyConstraints();
    }
}
