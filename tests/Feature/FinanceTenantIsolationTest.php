<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureTenantFeature;
use App\Models\CuentaCorriente;
use App\Models\DeudaCliente;
use App\Models\MovimientoCaja;
use App\Models\MovimientoCuentaCorriente;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use LogicException;
use RuntimeException;
use Tests\TestCase;

class FinanceTenantIsolationTest extends TestCase
{
    private const TABLES = ['movimientos_cuenta_corriente', 'deudas_clientes', 'cuentas_corrientes', 'movimientos_caja', 'ventas', 'cajas', 'clientes', 'tenant_user', 'users', 'tenants'];

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
        Schema::create('clientes', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('tenant_id');
            $t->string('nombre');
        });
        Schema::create('ventas', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('tenant_id');
            $t->unsignedBigInteger('cliente_id')->nullable();
        });
        Schema::create('cajas', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('tenant_id');
            $t->string('estado')->default('abierta');
        });
        Schema::create('movimientos_caja', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('tenant_id');
            $t->unsignedBigInteger('caja_id');
            $t->unsignedBigInteger('usuario_id');
            $t->string('tipo');
            $t->decimal('monto', 10, 2);
            $t->string('concepto');
            $t->text('observaciones')->nullable();
            $t->timestamps();
        });
        foreach (['2024_01_01_000007_create_cuentas_corrientes_table.php', '2024_01_01_000011_create_deudas_clientes_table.php', '2024_01_01_000014_create_movimientos_cuenta_corriente_table.php', '2026_01_22_000000_add_cuotas_fields_to_deudas_clientes_table.php'] as $file) {
            (require database_path('migrations/'.$file))->up();
        }
        foreach ([1, 2] as $id) {
            $tenant = Tenant::create(['id' => $id, 'slug' => 'finance-'.$id, 'name' => 'Comercio '.$id, 'onboarding_completed_at' => now()]);
            $user = User::create(['id' => $id, 'name' => 'Operador ficticio '.$id, 'email' => 'finance'.$id.'@example.test', 'password' => 'fixture-password', 'role' => 'admin']);
            $user->forceFill(['email_verified_at' => now()])->save();
            $tenant->users()->attach($user->id, ['role' => 'admin', 'status' => 'active', 'is_default' => true]);
            DB::table('clientes')->insert(['id' => $id, 'tenant_id' => $id, 'nombre' => 'Cliente '.$id]);
            DB::table('ventas')->insert(['id' => $id, 'tenant_id' => $id, 'cliente_id' => $id]);
            DB::table('cajas')->insert(['id' => $id, 'tenant_id' => $id]);
            DB::table('cuentas_corrientes')->insert(['id' => $id, 'cliente_id' => $id, 'saldo' => $id * 100, 'limite_credito' => 1000]);
            DB::table('movimientos_cuenta_corriente')->insert(['id' => $id, 'cuenta_corriente_id' => $id, 'venta_id' => $id, 'tipo' => 'debe', 'monto' => $id * 100, 'concepto' => 'Venta ficticia']);
            DB::table('deudas_clientes')->insert(['id' => $id, 'cliente_id' => $id, 'venta_id' => $id, 'monto_total' => $id * 100, 'monto_pendiente' => $id * 100, 'cuotas_originales' => 4, 'cuotas_pagadas' => 0, 'cuotas_restantes' => 4]);
            DB::table('movimientos_caja')->insert(['id' => $id, 'tenant_id' => $id, 'caja_id' => $id, 'usuario_id' => $user->id, 'tipo' => 'ingreso', 'monto' => $id * 10, 'concepto' => 'Ingreso ficticio']);
        }
        config()->set('tenancy.legacy_mode', false);
        $this->withoutMiddleware(EnsureTenantFeature::class);
    }

    protected function tearDown(): void
    {
        app(TenantContext::class)->clear();
        $this->dropSchema();
        parent::tearDown();
    }

    public function test_lists_details_and_mutations_are_isolated_in_both_directions(): void
    {
        $this->migration()->up();
        foreach ([1, 2] as $own) {
            $other = 3 - $own;
            $this->login($own);
            $this->getJson('/api/cuentas-corrientes')->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.id', $own);
            $this->getJson('/api/cuentas-corrientes/'.$own)->assertOk()->assertJsonCount(1, 'movimientos')->assertJsonPath('movimientos.0.tenant_id', $own);
            $this->getJson('/api/cuentas-corrientes/'.$other)->assertNotFound();
            $this->postJson('/api/cuentas-corrientes/'.$other.'/movimiento', ['tipo' => 'haber', 'monto' => 10, 'concepto' => 'Prohibido'])->assertNotFound();
            $this->getJson('/api/deudas-clientes')->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.id', $own);
            $this->getJson('/api/deudas-clientes/'.$other)->assertNotFound();
            $this->postJson('/api/deudas-clientes/'.$other.'/pago', ['monto' => 10])->assertNotFound();
            $this->getJson('/api/movimientos-caja')->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.id', $own);
            $this->getJson('/api/movimientos-caja/'.$other)->assertNotFound();
            $this->assertDatabaseHas('cuentas_corrientes', ['id' => $other, 'saldo' => $other * 100]);
            $this->assertDatabaseHas('deudas_clientes', ['id' => $other, 'monto_pendiente' => $other * 100]);
        }
    }

    public function test_foreign_sales_clients_and_cash_registers_cannot_be_related(): void
    {
        $this->migration()->up();
        $this->login(1);
        $this->postJson('/api/cuentas-corrientes', ['cliente_id' => 2, 'limite_credito' => 10])->assertUnprocessable()->assertJsonValidationErrors('cliente_id');
        $this->postJson('/api/cuentas-corrientes/1/movimiento', ['tipo' => 'debe', 'monto' => 10, 'concepto' => 'Cruce', 'venta_id' => 2])->assertUnprocessable()->assertJsonValidationErrors('venta_id');
        $this->postJson('/api/deudas-clientes', ['cliente_id' => 1, 'venta_id' => 2, 'monto_total' => 10])->assertUnprocessable()->assertJsonValidationErrors('venta_id');
        $this->postJson('/api/deudas-clientes', ['cliente_id' => 2, 'monto_total' => 10])->assertUnprocessable()->assertJsonValidationErrors('cliente_id');
        $this->postJson('/api/movimientos-caja', ['caja_id' => 2, 'tipo' => 'ingreso', 'monto' => 10, 'concepto' => 'Cruce'])->assertUnprocessable()->assertJsonValidationErrors('caja_id');
        $this->assertDatabaseCount('movimientos_cuenta_corriente', 2);
        $this->assertDatabaseCount('deudas_clientes', 2);
        $this->assertDatabaseCount('movimientos_caja', 2);
    }

    public function test_own_movements_and_payments_preserve_balances_and_reject_overpayment(): void
    {
        $this->migration()->up();
        $this->login(1);
        $this->postJson('/api/cuentas-corrientes/1/movimiento', ['tipo' => 'debe', 'monto' => '20.25', 'concepto' => 'Cargo ficticio', 'venta_id' => 1])->assertOk()->assertJsonPath('saldo', '120.25');
        $this->postJson('/api/cuentas-corrientes/1/movimiento', ['tipo' => 'haber', 'monto' => '5.25', 'concepto' => 'Abono ficticio'])->assertOk()->assertJsonPath('saldo', '115.00');
        $this->postJson('/api/deudas-clientes/1/pago', ['monto' => 25])->assertOk()->assertJsonPath('monto_pendiente', '75.00')->assertJsonPath('estado', 'parcial');
        $this->postJson('/api/deudas-clientes/1/pago', ['monto' => 76])->assertUnprocessable()->assertJsonValidationErrors('monto');
        $this->postJson('/api/deudas-clientes/1/pago', ['monto' => '0.001'])->assertUnprocessable();
        $this->postJson('/api/deudas-clientes/1/pago', ['monto' => 75])->assertOk()->assertJsonPath('monto_pendiente', '0.00')->assertJsonPath('monto_pagado', '100.00')->assertJsonPath('estado', 'pagada');
        $this->postJson('/api/deudas-clientes/1/pago', ['monto' => 1])->assertUnprocessable();
        $this->assertDatabaseHas('cuentas_corrientes', ['id' => 2, 'saldo' => 200]);
        $this->assertDatabaseHas('movimientos_cuenta_corriente', ['concepto' => 'Abono ficticio', 'tenant_id' => 1]);
        $this->assertDatabaseHas('deudas_clientes', ['id' => 2, 'monto_pendiente' => 200]);
    }

    public function test_backfill_assigns_owners_without_changing_amounts_or_installments(): void
    {
        $before = $this->financialRows();
        $migration = $this->migration();
        $migration->up();
        foreach (['cuentas_corrientes', 'deudas_clientes', 'movimientos_cuenta_corriente'] as $table) {
            foreach ([1, 2] as $id) {
                $this->assertDatabaseHas($table, ['id' => $id, 'tenant_id' => $id]);
            }
        }
        $migration->down();
        $this->assertSame($before, $this->financialRows());
        $migration->up();
        $this->assertDatabaseHas('deudas_clientes', ['id' => 2, 'monto_total' => 200, 'cuotas_restantes' => 4]);
    }

    public function test_new_accounts_and_installment_debts_take_the_active_tenant(): void
    {
        $this->migration()->up();
        DB::table('clientes')->insert(['id' => 3, 'tenant_id' => 1, 'nombre' => 'Cliente nuevo ficticio']);
        $this->login(1);
        $this->postJson('/api/cuentas-corrientes', ['cliente_id' => 3, 'limite_credito' => 250])
            ->assertCreated()->assertJsonPath('tenant_id', 1)->assertJsonPath('saldo', '0.00');
        $this->postJson('/api/deudas-clientes', ['cliente_id' => 3, 'monto_total' => 100, 'cuotas_originales' => 4, 'cuotas_pagadas' => 1])
            ->assertCreated()->assertJsonPath('tenant_id', 1)->assertJsonPath('monto_pagado', '25.00')
            ->assertJsonPath('monto_pendiente', '75.00')->assertJsonPath('cuotas_restantes', 3);
        $this->login(2);
        $this->getJson('/api/cuentas-corrientes?cliente_id=3')->assertOk()->assertJsonPath('total', 0);
        $this->getJson('/api/deudas-clientes?cliente_id=3')->assertOk()->assertJsonPath('total', 0);
    }

    public function test_conflicting_sale_ownership_rejects_backfill_before_schema_changes(): void
    {
        foreach (['deudas_clientes', 'movimientos_cuenta_corriente'] as $table) {
            DB::table($table)->where('id', 1)->update(['venta_id' => 2]);
            try {
                $this->migration()->up();
                $this->fail('Conflicting ownership accepted');
            } catch (RuntimeException $e) {
                $this->assertStringContainsString('otro comercio', $e->getMessage());
            }
            foreach (['cuentas_corrientes', 'deudas_clientes', 'movimientos_cuenta_corriente'] as $name) {
                $this->assertFalse(Schema::hasColumn($name, 'tenant_id'));
            }
            DB::table($table)->where('id', 1)->update(['venta_id' => 1]);
        }
    }

    public function test_missing_owner_rejects_backfill_before_schema_changes(): void
    {
        DB::table('clientes')->where('id', 1)->update(['tenant_id' => 999]);
        $before = $this->financialRows();
        try {
            $this->migration()->up();
            $this->fail('Missing owner accepted');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('No se puede determinar', $e->getMessage());
        }
        $this->assertFalse(Schema::hasColumn('cuentas_corrientes', 'tenant_id'));
        $this->assertSame($before, $this->financialRows());
    }

    public function test_queries_fail_closed_and_models_reject_cross_tenant_writes(): void
    {
        $this->migration()->up();
        foreach ([CuentaCorriente::class, DeudaCliente::class, MovimientoCuentaCorriente::class, MovimientoCaja::class] as $model) {
            $this->assertSame(0, $model::count());
        }
        $context = app(TenantContext::class);
        $context->set(Tenant::findOrFail(1));
        foreach ([
            fn () => DeudaCliente::create(['cliente_id' => 2, 'monto_total' => 10, 'monto_pendiente' => 10]),
            fn () => MovimientoCuentaCorriente::create(['cuenta_corriente_id' => 2, 'tipo' => 'debe', 'monto' => 10, 'concepto' => 'Cruce']),
            fn () => MovimientoCaja::create(['caja_id' => 2, 'tipo' => 'ingreso', 'monto' => 10, 'concepto' => 'Cruce', 'usuario_id' => 1]),
        ] as $write) {
            try {
                $write();
                $this->fail('Cross-tenant write accepted');
            } catch (LogicException $e) {
                $this->assertStringContainsString('no pertenece', $e->getMessage());
            }
        }
        $foreign = CuentaCorriente::withoutGlobalScopes()->findOrFail(2);
        try {
            $foreign->update(['saldo' => 0]);
            $this->fail('Foreign loaded model modified');
        } catch (LogicException $e) {
            $this->assertStringContainsString('contexto', $e->getMessage());
        }
        $this->assertDatabaseHas('cuentas_corrientes', ['id' => 2, 'saldo' => 200]);
        $context->clear();
        $this->expectException(LogicException::class);
        DeudaCliente::create(['cliente_id' => 1, 'monto_total' => 10, 'monto_pendiente' => 10]);
    }

    private function login(int $id): void
    {
        $this->actingAs(User::findOrFail($id), 'web');
        $this->withHeader('X-Tenant-Id', (string) $id);
    }

    private function migration(): object
    {
        return require database_path('migrations/2026_10_12_000002_isolate_finance_by_tenant.php');
    }

    private function financialRows(): array
    {
        $rows = [];
        foreach (['cuentas_corrientes', 'deudas_clientes', 'movimientos_cuenta_corriente'] as $table) {
            $rows[$table] = DB::table($table)->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
        }

        return $rows;
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
