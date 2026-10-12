<?php

use App\Http\Controllers\AuditoriaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CajaController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ChequeController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ConfiguracionFiscalController;
use App\Http\Controllers\ConfiguracionComercioController;
use App\Http\Controllers\CustomFormFieldController;
use App\Http\Controllers\CuentaCorrienteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeudaClienteController;
use App\Http\Controllers\MovimientoCajaController;
use App\Http\Controllers\MovimientoStockController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\SupportChatController;
use App\Http\Controllers\TenantSettingsModuleController;
use App\Http\Controllers\TurnoController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VentaController;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');

// Sanctum: sesión + Bearer. prefer.web.user: Blade manda sobre token en localStorage.
Route::middleware(['auth:sanctum', 'prefer.web.user'])->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/tenant-context', function (TenantContext $context) {
        return response()->json([
            'tenant' => $context->hasTenant() ? $context->tenant() : null,
            'legacy_mode' => ! $context->hasTenant(),
        ]);
    })->middleware('tenant');

    Route::middleware(['verified', 'tenant', 'onboarding.complete'])->group(function () {
    Route::middleware(['feature:core.cash', 'permission:cajas.view'])->group(function () {
        Route::get('cajas', [CajaController::class, 'index'])->name('api.cajas.index');
        Route::get('cajas/{id}', [CajaController::class, 'show'])->name('api.cajas.show');
        Route::get('cajas/{id}/resumen-cierre', [CajaController::class, 'resumenCierre'])->name('api.cajas.resumen');
    });
    Route::post('cajas', [CajaController::class, 'store'])->middleware(['feature:core.cash', 'permission:cajas.manage'])->name('api.cajas.store');
    Route::post('cajas/{id}/cerrar', [CajaController::class, 'cerrar'])->middleware(['feature:core.cash', 'permission:cajas.manage'])->name('api.cajas.cerrar');

    // Dashboard
    Route::middleware('permission:dashboard.view')->group(function () {
        Route::get('/dashboard/estadisticas', [DashboardController::class, 'estadisticas']);
        Route::get('/dashboard/ventas-por-dia', [DashboardController::class, 'ventasPorDia']);
        Route::get('/dashboard/productos-mas-vendidos', [DashboardController::class, 'productosMasVendidos']);
        Route::get('/dashboard/resumen-cajas', [DashboardController::class, 'resumenCajas']);
        Route::get('/dashboard/ventas-por-tipo-pago', [DashboardController::class, 'ventasPorTipoPago']);
    });

    Route::middleware(['feature:core.pos', 'permission:ventas.view'])->group(function () {
        Route::get('ventas', [VentaController::class, 'index']);
        Route::get('configuracion-fiscal-comprobante', [ConfiguracionFiscalController::class, 'paraComprobante']);
    });
    Route::get('ventas/abiertas', [VentaController::class, 'abiertas'])->middleware(['feature:sales.open_orders', 'permission:ventas.view']);
    Route::get('ventas/{id}', [VentaController::class, 'show'])->middleware(['feature:core.pos', 'permission:ventas.view'])->whereNumber('id');

    Route::post('ventas', [VentaController::class, 'store'])->middleware(['feature:core.pos', 'permission:ventas.create']);
    Route::post('ventas/abrir', [VentaController::class, 'abrir'])->middleware(['feature:sales.open_orders', 'permission:ventas.create']);
    Route::post('ventas/{id}/cancelar', [VentaController::class, 'cancelar'])->middleware(['feature:sales.open_orders', 'permission:ventas.create'])->whereNumber('id');
    Route::post('ventas/{id}/items', [VentaController::class, 'agregarItems'])->middleware(['feature:sales.open_orders', 'permission:ventas.create'])->whereNumber('id');
    Route::post('ventas/{id}/descuento', [VentaController::class, 'actualizarDescuento'])->middleware(['feature:core.pos', 'permission:ventas.create']);
    Route::post('ventas/{id}/cerrar', [VentaController::class, 'cerrar'])->middleware(['feature:core.pos', 'permission:ventas.create']);
    Route::post('ventas/{id}/adjuntos', [VentaController::class, 'agregarAdjuntos'])->middleware(['feature:core.pos', 'permission:ventas.create']);
    Route::post('ventas/{id}/facturar-afip', [VentaController::class, 'facturarAfip'])->middleware(['feature:fiscal.afip', 'permission:ventas.facturar']);
    Route::get('ventas-pendientes-facturacion', [VentaController::class, 'pendientesFacturacion'])->middleware(['feature:fiscal.afip', 'permission:ventas.facturar']);
    Route::post('ventas-facturar-lote', [VentaController::class, 'facturarLote'])->middleware(['feature:fiscal.afip', 'permission:ventas.facturar']);

    Route::middleware('permission:clientes.view')->group(function () {
        Route::get('clientes', [ClienteController::class, 'index'])->name('api.clientes.index');
        Route::get('clientes/{id}', [ClienteController::class, 'show'])->whereNumber('id')->name('api.clientes.show');
    });
    Route::middleware('permission:clientes.manage')->group(function () {
        Route::post('clientes', [ClienteController::class, 'store'])->name('api.clientes.store');
        Route::put('clientes/{id}', [ClienteController::class, 'update'])->whereNumber('id')->name('api.clientes.update');
        Route::patch('clientes/{id}', [ClienteController::class, 'update'])->whereNumber('id')->name('api.clientes.patch');
        Route::delete('clientes/{id}', [ClienteController::class, 'destroy'])->whereNumber('id')->name('api.clientes.destroy');
    });

    Route::middleware(['feature:core.stock', 'permission:stock.view'])->group(function () {
        Route::get('movimientos-stock', [MovimientoStockController::class, 'index'])->name('api.movimientos-stock.index');
        Route::get('movimientos-stock/{id}', [MovimientoStockController::class, 'show'])->whereNumber('id')->name('api.movimientos-stock.show');
    });
    Route::post('movimientos-stock', [MovimientoStockController::class, 'store'])->middleware(['feature:core.stock', 'permission:stock.manage'])->name('api.movimientos-stock.store');

    // Literal antes de productos/{producto} para que no capture "siguiente-codigo" como id.
    Route::get('productos/siguiente-codigo', [ProductoController::class, 'siguienteCodigoInterno'])
        ->middleware(['feature:core.catalog', 'permission:productos.manage'])
        ->name('api.productos.siguiente-codigo');
    Route::get('productos/por-codigo', [ProductoController::class, 'buscarPorCodigo'])
        ->middleware(['feature:catalog.scanner', 'permission:productos.view'])
        ->name('api.productos.por-codigo');

    Route::middleware(['feature:core.catalog', 'permission:productos.view'])->group(function () {
        Route::get('productos', [ProductoController::class, 'index']);
        Route::get('productos/{producto}', [ProductoController::class, 'show']);
        Route::get('productos/proveedor/{proveedorId}', [ProductoController::class, 'getByProveedor']);
    });

    Route::middleware(['feature:core.catalog', 'permission:categorias.view'])->group(function () {
        Route::get('categorias', [CategoriaController::class, 'index']);
        Route::get('categorias/{id}', [CategoriaController::class, 'show']);
    });

    Route::middleware(['feature:core.catalog', 'permission:categorias.manage'])->group(function () {
        Route::post('categorias', [CategoriaController::class, 'store']);
        Route::put('categorias/{id}', [CategoriaController::class, 'update']);
        Route::patch('categorias/{id}', [CategoriaController::class, 'update']);
        Route::delete('categorias/{id}', [CategoriaController::class, 'destroy']);
    });

    Route::middleware(['feature:core.catalog', 'permission:productos.manage'])->group(function () {
        Route::post('productos', [ProductoController::class, 'store']);
        Route::put('productos/{producto}', [ProductoController::class, 'update']);
        Route::patch('productos/{producto}', [ProductoController::class, 'update']);
        Route::delete('productos/{producto}', [ProductoController::class, 'destroy']);
        Route::post('productos/aumento-masivo', [ProductoController::class, 'aumentoMasivo']);
    });

    Route::middleware('permission:admin')->group(function () {
        Route::get('auditoria/timeline', [AuditoriaController::class, 'timeline'])->middleware('feature:operations.audit')->name('api.auditoria.timeline');

        Route::apiResource('usuarios', UserController::class)->only(['index', 'store', 'update', 'destroy']);

        Route::get('configuracion-fiscal', [ConfiguracionFiscalController::class, 'show'])->middleware('feature:fiscal.afip');
        Route::post('configuracion-fiscal', [ConfiguracionFiscalController::class, 'update'])->middleware('feature:fiscal.afip');
        Route::get('configuracion-comercio', [ConfiguracionComercioController::class, 'show']);
        Route::post('configuracion-comercio', [ConfiguracionComercioController::class, 'update']);
        Route::get('modulos', [TenantSettingsModuleController::class, 'index']);
        Route::put('modulos', [TenantSettingsModuleController::class, 'update']);

        Route::apiResource('proveedores', ProveedorController::class)->middleware('feature:core.catalog')->names('api.proveedores');

        Route::get('movimientos-caja', [MovimientoCajaController::class, 'index']);
        Route::post('movimientos-caja', [MovimientoCajaController::class, 'store']);
        Route::get('movimientos-caja/{id}', [MovimientoCajaController::class, 'show']);

        Route::get('cuentas-corrientes', [CuentaCorrienteController::class, 'index'])->middleware('feature:finance.credit');
        Route::post('cuentas-corrientes', [CuentaCorrienteController::class, 'store'])->middleware('feature:finance.credit');
        Route::get('cuentas-corrientes/{id}', [CuentaCorrienteController::class, 'show'])->middleware('feature:finance.credit');
        Route::post('cuentas-corrientes/{id}/movimiento', [CuentaCorrienteController::class, 'agregarMovimiento'])->middleware('feature:finance.credit');

        Route::get('deudas-clientes', [DeudaClienteController::class, 'index'])->middleware('feature:finance.credit');
        Route::post('deudas-clientes', [DeudaClienteController::class, 'store'])->middleware('feature:finance.credit');
        Route::get('deudas-clientes/{id}', [DeudaClienteController::class, 'show'])->middleware('feature:finance.credit');
        Route::post('deudas-clientes/{id}/pago', [DeudaClienteController::class, 'registrarPago'])->middleware('feature:finance.credit');

        Route::apiResource('cheques', ChequeController::class)->middleware('feature:finance.cheques')->names('api.cheques');
        Route::get('cheques-proximos-vencer', [ChequeController::class, 'proximosAVencer'])->middleware('feature:finance.cheques');
        Route::get('cheques-por-mes', [ChequeController::class, 'porMes'])->middleware('feature:finance.cheques');
        Route::get('cheques-por-fecha', [ChequeController::class, 'porFecha'])->middleware('feature:finance.cheques');
        Route::get('cheques-estadisticas', [ChequeController::class, 'estadisticas'])->middleware('feature:finance.cheques');
        Route::post('cheques/{id}/marcar-cobrado', [ChequeController::class, 'marcarCobrado'])->middleware('feature:finance.cheques');
    });

    Route::get('formularios/{form}/campos', [CustomFormFieldController::class, 'index']);
    Route::post('formularios/{form}/campos', [CustomFormFieldController::class, 'store']);
    Route::delete('formularios/{form}/campos/{campo}', [CustomFormFieldController::class, 'destroy'])->whereNumber('campo');

    Route::middleware(['feature:services.appointments', 'permission:turnos.view'])->group(function () {
        Route::get('turnos', [TurnoController::class, 'index']);
    });
    Route::middleware(['feature:services.appointments', 'permission:turnos.manage'])->group(function () {
        Route::post('turnos', [TurnoController::class, 'store']);
        Route::put('turnos/{turno}', [TurnoController::class, 'update']);
        Route::delete('turnos/{turno}', [TurnoController::class, 'destroy']);
    });

    Route::middleware(['feature:platform.support', 'throttle:support'])->group(function () {
        Route::get('support/conversations', [SupportChatController::class, 'index']);
        Route::post('support/conversations', [SupportChatController::class, 'store']);
        Route::get('support/conversations/{conversation}', [SupportChatController::class, 'show']);
        Route::get('support/conversations/{conversation}/messages', [SupportChatController::class, 'poll']);
        Route::post('support/conversations/{conversation}/messages', [SupportChatController::class, 'storeMessage']);
        Route::post('support/conversations/{conversation}/close', [SupportChatController::class, 'close']);
    });
    });
});
