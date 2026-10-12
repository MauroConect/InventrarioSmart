<?php

namespace Tests\Feature;

use App\Models\{Caja, Categoria, Cliente, ItemVenta, MovimientoStock, Producto, Proveedor, TenantModule, Turno, Venta};
use App\Support\Tenancy\TenantContext;
use LogicException;
use Tests\MySqlTenantTestCase;

class OperationalRelationsTest extends MySqlTenantTestCase
{
    public function test_foreign_references_are_rejected_before_operational_writes(): void
    {
        [$a, $operator] = $this->commerce();
        [$b, $other] = $this->commerce('Comercio B');
        $context = app(TenantContext::class);
        [$category, $supplier, $product, $cash, $client] = $context->run($b, function () use ($other) {
            $category = Categoria::create(['nombre' => 'B']);
            $supplier = Proveedor::create(['nombre' => 'B']);
            $product = Producto::create(['codigo' => 'B', 'nombre' => 'B', 'categoria_id' => $category->id, 'precio_compra' => 1, 'precio_venta' => 10, 'stock_actual' => 5]);
            $cash = Caja::create(['usuario_id' => $other->id, 'fecha_apertura' => now(), 'monto_apertura' => 0, 'estado' => 'abierta']);
            $client = Cliente::create(['nombre' => 'Cliente B', 'apellido' => 'Ficticio', 'dni' => 'FICTICIO-B']);
            return [$category, $supplier, $product, $cash, $client];
        });
        $this->signIn($a, $operator);
        $payload = ['codigo' => 'A', 'nombre' => 'A', 'precio_compra' => 1, 'precio_venta' => 10, 'stock_minimo' => 0, 'stock_actual' => 5, 'categoria_id' => $category->id, 'proveedor_id' => $supplier->id];
        $this->postJson('/api/productos', $payload)->assertUnprocessable()->assertJsonValidationErrors(['categoria_id', 'proveedor_id']);
        $this->postJson('/api/ventas', ['caja_id' => $cash->id, 'cliente_id' => $client->id, 'items' => [['producto_id' => $product->id, 'cantidad' => 1]], 'tipo_pago' => 'efectivo'])->assertUnprocessable()->assertJsonValidationErrors(['caja_id', 'cliente_id', 'items.0.producto_id']);
        $this->postJson('/api/movimientos-stock', ['producto_id' => $product->id, 'tipo' => 'entrada', 'cantidad' => 1, 'motivo' => 'test'])->assertUnprocessable();
        $this->postJson('/api/productos/aumento-masivo', ['producto_ids' => [$product->id], 'porcentaje_aumento' => 10, 'aplicar_a_venta' => true])->assertUnprocessable();
        TenantModule::updateOrCreate(['tenant_id' => $a->id, 'module_key' => 'services.appointments'], ['enabled' => true]);
        $this->postJson('/api/turnos', ['cliente_id' => $client->id, 'nombre' => 'A', 'starts_at' => now()->addDay()->toDateTimeString()])->assertUnprocessable();
        $context->set($a);
        $this->assertSame(0, Producto::count());
        $this->assertSame(0, Venta::count());
        $this->assertSame(0, MovimientoStock::count());
        $this->assertSame(0, Turno::count());
        $this->assertSame('5.000', $context->run($b, fn () => $product->fresh()->stock_actual));
    }

    public function test_model_guards_reject_foreign_operator_and_related_records(): void
    {
        [$a, $operator] = $this->commerce();
        [$b, $other] = $this->commerce('Comercio B');
        $category = app(TenantContext::class)->run($b, fn () => Categoria::create(['nombre' => 'B']));
        app(TenantContext::class)->set($a);
        foreach ([
            fn () => Producto::create(['codigo' => 'A', 'nombre' => 'A', 'categoria_id' => $category->id]),
            fn () => Caja::create(['usuario_id' => $other->id, 'fecha_apertura' => now()]),
            fn () => Turno::create(['user_id' => $other->id, 'nombre' => 'A', 'starts_at' => now(), 'ends_at' => now()->addHour()]),
        ] as $write) {
            try { $write(); $this->fail('Foreign relation accepted'); } catch (LogicException $e) { $this->assertStringContainsString('no pertenece', $e->getMessage()); }
        }
    }

    public function test_product_codes_are_unique_only_inside_the_active_tenant(): void
    {
        [$a, $operator] = $this->commerce();
        [$b, $other] = $this->commerce('Comercio B');
        foreach ([[$a, $operator], [$b, $other]] as [$tenant, $user]) {
            $category = app(TenantContext::class)->run($tenant, fn () => Categoria::create(['nombre' => 'General']));
            $this->signIn($tenant, $user);
            $payload = ['codigo' => 'SHARED', 'nombre' => 'Producto', 'categoria_id' => $category->id, 'precio_compra' => 1, 'precio_venta' => 2, 'stock_actual' => 0, 'stock_minimo' => 0];
            $this->postJson('/api/productos', $payload)->assertCreated();
            $this->postJson('/api/productos', $payload)->assertUnprocessable()->assertJsonValidationErrors('codigo');
        }
    }
}
