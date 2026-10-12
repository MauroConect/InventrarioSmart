<?php

namespace Tests\Feature;

use App\Models\{Caja, Categoria, Cliente, CuentaCorriente, MovimientoCuentaCorriente, MovimientoStock, Producto, TenantModule, Venta};
use App\Support\Tenancy\TenantContext;
use Tests\MySqlTenantTestCase;

class SaleCashCycleTest extends MySqlTenantTestCase
{
    private function fixture(): array
    {
        [$tenant, $operator] = $this->commerce();
        foreach (['catalog.scanner', 'catalog.weighted', 'sales.open_orders', 'finance.credit'] as $module) { TenantModule::updateOrCreate(['tenant_id' => $tenant->id, 'module_key' => $module], ['enabled' => true]); }
        [$cash, $unit, $weight] = app(TenantContext::class)->run($tenant, function () use ($operator) {
            $category = Categoria::create(['nombre' => 'Ficticia']);
            $unit = Producto::create(['codigo' => 'UNIT', 'nombre' => 'Unidad', 'categoria_id' => $category->id, 'precio_venta' => 100, 'stock_actual' => 3, 'tipo_venta' => 'unidad']);
            $weight = Producto::create(['codigo' => 'WEIGHT', 'nombre' => 'Pesable', 'categoria_id' => $category->id, 'precio_venta' => 1000, 'stock_actual' => 2, 'tipo_venta' => 'peso', 'unidad_medida' => 'kg']);
            $cash = Caja::create(['usuario_id' => $operator->id, 'fecha_apertura' => now(), 'monto_apertura' => 50, 'estado' => 'abierta']);
            return [$cash, $unit, $weight];
        });
        $this->signIn($tenant, $operator);
        return [$tenant, $operator, $cash, $unit, $weight];
    }

    public function test_scanner_weighted_mixed_sale_replay_and_physical_cash_close(): void
    {
        [$tenant, $operator, $cash, $unit, $weight] = $this->fixture();
        $this->getJson('/api/productos/por-codigo?codigo=WEIGHT')->assertOk()->assertJsonPath('id', $weight->id);
        $payload = ['caja_id' => $cash->id, 'items' => [['producto_id' => $unit->id, 'cantidad' => 1], ['producto_id' => $weight->id, 'cantidad' => .125]], 'tipo_pago' => 'mixto', 'monto_efectivo' => 50, 'monto_tarjeta' => 75, 'monto_transferencia' => 100];
        $first = $this->withHeader('Idempotency-Key', 'sale-cycle-mixed')->postJson('/api/ventas', $payload)->assertCreated();
        $this->postJson('/api/ventas', $payload)->assertCreated()->assertHeader('Idempotency-Replayed', 'true')->assertJsonPath('id', $first->json('id'));
        $this->postJson('/api/ventas', array_replace($payload, ['descuento' => 1]))->assertConflict();
        $this->flushHeaders();
        app(TenantContext::class)->run($tenant, function () use ($unit, $weight) { $this->assertSame('2.000', $unit->fresh()->stock_actual); $this->assertSame('1.875', $weight->fresh()->stock_actual); $this->assertSame(1, Venta::count()); $this->assertSame(2, MovimientoStock::count()); });
        $this->getJson('/api/cajas/'.$cash->id.'/resumen-cierre')->assertOk()->assertJsonPath('resumen.monto_esperado', 100)->assertJsonPath('resumen.total_ventas', 225);
        $this->postJson('/api/cajas/'.$cash->id.'/cerrar', ['monto_real' => 100])->assertOk()->assertJsonPath('diferencia', '0.00');
        $this->postJson('/api/cajas/'.$cash->id.'/cerrar', ['monto_real' => 0])->assertOk()->assertJsonPath('monto_real', '100.00');
    }

    public function test_failures_roll_back_stock_items_receipts_and_sales(): void
    {
        [$tenant, , $cash, $unit, $weight] = $this->fixture();
        $base = ['caja_id' => $cash->id, 'tipo_pago' => 'efectivo'];
        foreach ([
            ['items' => [['producto_id' => $unit->id, 'cantidad' => 1], ['producto_id' => $weight->id, 'cantidad' => 9]]],
            ['items' => [['producto_id' => $unit->id, 'cantidad' => 2], ['producto_id' => $unit->id, 'cantidad' => 2]]],
            ['items' => [['producto_id' => $unit->id, 'cantidad' => 1.5]]],
            ['items' => [['producto_id' => $unit->id, 'cantidad' => 1]], 'descuento' => 101],
        ] as $case) { $this->postJson('/api/ventas', array_replace($base, $case))->assertUnprocessable(); }
        app(TenantContext::class)->run($tenant, function () use ($unit, $weight) { $this->assertSame('3.000', $unit->fresh()->stock_actual); $this->assertSame('2.000', $weight->fresh()->stock_actual); $this->assertSame(0, Venta::count()); $this->assertSame(0, MovimientoStock::count()); });
    }

    public function test_open_order_reservation_cancellation_and_cash_gate_are_consistent(): void
    {
        [$tenant, , $cash, $unit] = $this->fixture();
        $sale = $this->postJson('/api/ventas/abrir', ['caja_id' => $cash->id, 'etiqueta' => 'Mesa ficticia'])->assertCreated()->json('id');
        $this->postJson('/api/ventas/'.$sale.'/items', ['items' => [['producto_id' => $unit->id, 'cantidad' => 2]]])->assertOk();
        $this->getJson('/api/cajas/'.$cash->id.'/resumen-cierre')->assertJsonPath('resumen.total_ventas', 0)->assertJsonPath('resumen.monto_esperado', 50);
        $this->postJson('/api/cajas/'.$cash->id.'/cerrar', ['monto_real' => 50])->assertUnprocessable();
        $this->postJson('/api/ventas/'.$sale.'/cancelar')->assertOk();
        $this->postJson('/api/ventas/'.$sale.'/cancelar')->assertOk();
        app(TenantContext::class)->run($tenant, function () use ($unit) { $this->assertSame('3.000', $unit->fresh()->stock_actual); $this->assertSame(2, MovimientoStock::count()); });
        $this->postJson('/api/cajas/'.$cash->id.'/cerrar', ['monto_real' => 50])->assertOk()->assertJsonPath('diferencia', '0.00');
    }

    public function test_credit_close_is_once_and_does_not_increase_physical_cash(): void
    {
        [$tenant, , $cash, $unit] = $this->fixture();
        [$client, $account] = app(TenantContext::class)->run($tenant, function () {
            $client = Cliente::create(['nombre' => 'Ficticio', 'apellido' => 'Credito', 'dni' => 'FICTICIO-CREDIT']);
            return [$client, CuentaCorriente::create(['cliente_id' => $client->id, 'saldo' => 0, 'limite_credito' => 1000, 'activa' => true])];
        });
        $sale = $this->postJson('/api/ventas/abrir', ['caja_id' => $cash->id])->json('id');
        $this->postJson('/api/ventas/'.$sale.'/items', ['items' => [['producto_id' => $unit->id, 'cantidad' => 1]]])->assertOk();
        $payload = ['tipo_pago' => 'cuenta_corriente', 'cliente_id' => $client->id];
        $this->postJson('/api/ventas/'.$sale.'/cerrar', $payload)->assertOk();
        $this->postJson('/api/ventas/'.$sale.'/cerrar', $payload)->assertOk();
        app(TenantContext::class)->run($tenant, function () use ($account) { $this->assertSame('100.00', $account->fresh()->saldo); $this->assertSame(1, MovimientoCuentaCorriente::count()); });
        $this->getJson('/api/cajas/'.$cash->id.'/resumen-cierre')->assertJsonPath('resumen.monto_esperado', 50)->assertJsonPath('resumen.total_ventas', 100);
    }

    public function test_replayed_mutation_rechecks_membership_permissions(): void
    {
        [$tenant, $operator, $cash, $unit] = $this->fixture();
        $payload = ['caja_id' => $cash->id, 'tipo_pago' => 'efectivo', 'items' => [['producto_id' => $unit->id, 'cantidad' => 1]]];
        $this->withHeader('Idempotency-Key', 'permission-recheck')->postJson('/api/ventas', $payload)->assertCreated();
        $tenant->users()->updateExistingPivot($operator->id, ['role' => 'stock']);
        $this->postJson('/api/ventas', $payload)->assertForbidden();
    }
}
