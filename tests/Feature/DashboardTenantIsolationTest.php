<?php

namespace Tests\Feature;

use App\Http\Controllers\DashboardController;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DashboardTenantIsolationTest extends TestCase
{
    private const TABLES = ['cheques', 'deudas_clientes', 'items_venta', 'ventas', 'movimientos_caja', 'productos', 'proveedores', 'clientes', 'cajas', 'tenants'];

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite', ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true]);
        DB::purge();
        DB::reconnect('sqlite');
        Schema::create('tenants', function (Blueprint $t): void {
            $t->id();
            $t->uuid('uuid');
            $t->string('slug');
            $t->string('name');
            $t->timestamps();
        });
        Schema::create('clientes', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('tenant_id');
            $t->boolean('activo')->default(true);
        });
        Schema::create('proveedores', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('tenant_id');
        });
        Schema::create('cajas', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('tenant_id');
            $t->unsignedBigInteger('usuario_id');
            $t->string('estado');
            $t->dateTime('fecha_apertura');
            $t->decimal('monto_apertura')->default(0);
            $t->decimal('monto_cierre')->default(0);
            $t->decimal('diferencia')->default(0);
        });
        Schema::create('movimientos_caja', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('tenant_id');
            $t->unsignedBigInteger('caja_id');
            $t->string('tipo');
            $t->decimal('monto');
        });
        Schema::create('productos', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('tenant_id');
            $t->string('nombre');
            $t->string('codigo');
            $t->boolean('activo')->default(true);
            $t->decimal('stock_actual')->default(20);
            $t->decimal('stock_minimo')->default(5);
        });
        Schema::create('ventas', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('tenant_id');
            $t->unsignedBigInteger('caja_id');
            $t->unsignedBigInteger('cliente_id')->nullable();
            $t->dateTime('fecha');
            $t->string('estado')->default('cerrada');
            $t->string('tipo_pago')->default('efectivo');
            $t->decimal('total_final');
        });
        Schema::create('items_venta', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('tenant_id');
            $t->unsignedBigInteger('venta_id');
            $t->unsignedBigInteger('producto_id');
            $t->decimal('cantidad');
            $t->decimal('subtotal');
        });
        Schema::create('deudas_clientes', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('tenant_id');
            $t->unsignedBigInteger('cliente_id');
            $t->unsignedBigInteger('venta_id')->nullable();
            $t->string('estado')->default('pendiente');
            $t->decimal('monto_pendiente');
        });
        Schema::create('cheques', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('tenant_id');
            $t->unsignedBigInteger('cliente_id')->nullable();
            $t->unsignedBigInteger('proveedor_id')->nullable();
            $t->unsignedBigInteger('caja_id')->nullable();
            $t->string('estado')->default('pendiente');
            $t->date('fecha_vencimiento');
        });
        foreach ([1, 2] as $id) {
            Tenant::create(['id' => $id, 'slug' => 'tenant-'.$id, 'name' => 'Comercio '.$id]);
            DB::table('clientes')->insert(['id' => $id, 'tenant_id' => $id]);
            DB::table('proveedores')->insert(['id' => $id, 'tenant_id' => $id]);
            DB::table('cajas')->insert(['id' => $id, 'tenant_id' => $id, 'usuario_id' => $id, 'estado' => 'cerrada', 'fecha_apertura' => now(), 'monto_cierre' => $id * 100]);
            DB::table('productos')->insert(['id' => $id, 'tenant_id' => $id, 'nombre' => 'Producto '.$id, 'codigo' => 'P'.$id]);
            DB::table('ventas')->insert(['id' => $id, 'tenant_id' => $id, 'caja_id' => $id, 'cliente_id' => $id, 'fecha' => now(), 'total_final' => $id * 100]);
            DB::table('items_venta')->insert(['tenant_id' => $id, 'venta_id' => $id, 'producto_id' => $id, 'cantidad' => $id, 'subtotal' => $id * 100]);
            DB::table('deudas_clientes')->insert(['tenant_id' => $id, 'cliente_id' => $id, 'venta_id' => $id, 'monto_pendiente' => $id * 10]);
            DB::table('cheques')->insert(['tenant_id' => $id, 'cliente_id' => $id, 'fecha_vencimiento' => now()->addDay()->toDateString()]);
        }
    }

    protected function tearDown(): void
    {
        app(TenantContext::class)->clear();
        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }
        parent::tearDown();
    }

    public function test_statistics_rankings_and_aggregates_only_include_the_active_tenant(): void
    {
        foreach ([1, 2] as $id) {
            $tenant = Tenant::findOrFail($id);
            app(TenantContext::class)->run($tenant, function () use ($id): void {
                $controller = app(DashboardController::class);
                $request = $this->request($id);
                $stats = $controller->estadisticas($request)->getData(true);
                $this->assertSame(1, $stats['total_productos']);
                $this->assertSame(1, $stats['total_clientes']);
                $this->assertSame(1, $stats['ventas_hoy']);
                $this->assertEquals($id * 100, $stats['monto_ventas_hoy']);
                $this->assertEquals($id * 10, $stats['deudas_pendientes']);
                $this->assertSame(1, $stats['cheques_proximos']);
                $ranking = $controller->productosMasVendidos($request)->getData(true);
                $this->assertCount(1, $ranking);
                $this->assertSame($id, $ranking[0]['id']);
                $this->assertEquals($id * 100, $controller->ventasPorDia($request)->getData(true)[0]['total']);
                $this->assertEquals($id * 100, $controller->resumenCajas($request)->getData(true)['total_cierre']);
                $this->assertEquals($id * 100, $controller->ventasPorTipoPago($request)->getData(true)[0]['value']);
            });
        }
    }

    public function test_invalid_cross_tenant_relations_are_excluded_from_metrics(): void
    {
        DB::table('items_venta')->insert(['tenant_id' => 1, 'venta_id' => 2, 'producto_id' => 1, 'cantidad' => 999, 'subtotal' => 99999]);
        DB::table('items_venta')->insert(['tenant_id' => 1, 'venta_id' => 1, 'producto_id' => 2, 'cantidad' => 999, 'subtotal' => 99999]);
        DB::table('deudas_clientes')->insert(['tenant_id' => 1, 'cliente_id' => 1, 'venta_id' => 2, 'monto_pendiente' => 99999]);
        DB::table('cheques')->insert(['tenant_id' => 1, 'cliente_id' => 1, 'proveedor_id' => 2, 'fecha_vencimiento' => now()->addDay()->toDateString()]);
        DB::table('cheques')->insert(['tenant_id' => 1, 'cliente_id' => null, 'proveedor_id' => null, 'caja_id' => null, 'fecha_vencimiento' => now()->addDay()->toDateString()]);
        app(TenantContext::class)->run(Tenant::findOrFail(1), function (): void {
            $controller = app(DashboardController::class);
            $request = $this->request(1);
            $stats = $controller->estadisticas($request)->getData(true);
            $this->assertEquals(10, $stats['deudas_pendientes']);
            // Explicit ownership survives nullable references (e.g. a deleted client).
            $this->assertSame(2, $stats['cheques_proximos']);
            $ranking = $controller->productosMasVendidos($request)->getData(true);
            $this->assertCount(1, $ranking);
            $this->assertEquals(1, $ranking[0]['cantidad_vendida']);
        });
    }

    public function test_metrics_fail_closed_without_tenant_context(): void
    {
        $controller = app(DashboardController::class);
        $request = $this->request(1);
        $stats = $controller->estadisticas($request)->getData(true);
        foreach (['total_productos', 'total_clientes', 'ventas_hoy', 'monto_ventas_hoy', 'deudas_pendientes', 'cheques_proximos'] as $key) {
            $this->assertEquals(0, $stats[$key]);
        }
        $this->assertSame([], $controller->productosMasVendidos($request)->getData(true));
        $this->assertSame([], $controller->ventasPorDia($request)->getData(true));
        $this->assertEquals(0, $controller->resumenCajas($request)->getData(true)['total_cierre']);
        $this->assertSame([], $controller->ventasPorTipoPago($request)->getData(true));
    }

    public function test_ranking_includes_the_whole_end_date_and_excludes_the_next_day(): void
    {
        DB::table('ventas')->where('id', 1)->update(['fecha' => now()->endOfDay()->format('Y-m-d H:i:s')]);
        DB::table('ventas')->insert(['id' => 3, 'tenant_id' => 1, 'caja_id' => 1, 'fecha' => now()->addDay()->startOfDay(), 'total_final' => 900]);
        DB::table('items_venta')->insert(['tenant_id' => 1, 'venta_id' => 3, 'producto_id' => 1, 'cantidad' => 9, 'subtotal' => 900]);

        app(TenantContext::class)->run(Tenant::findOrFail(1), function (): void {
            $request = $this->request(1);
            $request->merge(['fecha_inicio' => now()->toDateString(), 'fecha_fin' => now()->toDateString()]);
            $ranking = app(DashboardController::class)->productosMasVendidos($request)->getData(true);
            $this->assertCount(1, $ranking);
            $this->assertEquals(1, $ranking[0]['cantidad_vendida']);
            $this->assertEquals(100, $ranking[0]['total_vendido']);
        });
    }

    private function request(int $id): Request
    {
        $request = Request::create('/api/dashboard/estadisticas', 'GET');
        $user = (new User)->forceFill(['id' => $id, 'role' => 'admin']);
        $request->setUserResolver(fn () => $user);

        return $request;
    }
}
