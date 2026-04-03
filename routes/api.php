<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\CajaController;
use App\Http\Controllers\MovimientoCajaController;
use App\Http\Controllers\CuentaCorrienteController;
use App\Http\Controllers\DeudaClienteController;
use App\Http\Controllers\MovimientoStockController;
use App\Http\Controllers\VentaController;
use App\Http\Controllers\ChequeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ConfiguracionFiscalController;

Route::post('/login', [AuthController::class, 'login']);

// Sanctum: sesión + Bearer. prefer.web.user: Blade manda sobre token en localStorage.
Route::middleware(['auth:sanctum', 'prefer.web.user'])->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::middleware('permission:cajas.view')->group(function () {
        Route::get('cajas', [CajaController::class, 'index'])->name('api.cajas.index');
        Route::get('cajas/{id}', [CajaController::class, 'show'])->name('api.cajas.show');
        Route::get('cajas/{id}/resumen-cierre', [CajaController::class, 'resumenCierre'])->name('api.cajas.resumen');
    });
    Route::post('cajas', [CajaController::class, 'store'])->middleware('permission:cajas.manage')->name('api.cajas.store');
    Route::post('cajas/{id}/cerrar', [CajaController::class, 'cerrar'])->middleware('permission:cajas.manage')->name('api.cajas.cerrar');

    // Dashboard
    Route::middleware('permission:dashboard.view')->group(function () {
        Route::get('/dashboard/estadisticas', [DashboardController::class, 'estadisticas']);
        Route::get('/dashboard/ventas-por-dia', [DashboardController::class, 'ventasPorDia']);
        Route::get('/dashboard/productos-mas-vendidos', [DashboardController::class, 'productosMasVendidos']);
        Route::get('/dashboard/resumen-cajas', [DashboardController::class, 'resumenCajas']);
        Route::get('/dashboard/ventas-por-tipo-pago', [DashboardController::class, 'ventasPorTipoPago']);
    });

    Route::middleware('permission:ventas.view')->group(function () {
        Route::get('ventas', [VentaController::class, 'index']);
        Route::get('ventas/{id}', [VentaController::class, 'show']);
        Route::get('configuracion-fiscal-comprobante', [ConfiguracionFiscalController::class, 'paraComprobante']);
    });

    Route::post('ventas', [VentaController::class, 'store'])->middleware('permission:ventas.create');
    Route::post('ventas/{id}/adjuntos', [VentaController::class, 'agregarAdjuntos'])->middleware('permission:ventas.create');
    Route::post('ventas/{id}/facturar-afip', [VentaController::class, 'facturarAfip'])->middleware('permission:ventas.facturar');
    Route::get('ventas-pendientes-facturacion', [VentaController::class, 'pendientesFacturacion'])->middleware('permission:ventas.facturar');
    Route::post('ventas-facturar-lote', [VentaController::class, 'facturarLote'])->middleware('permission:ventas.facturar');

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

    Route::middleware('permission:stock.view')->group(function () {
        Route::get('movimientos-stock', [MovimientoStockController::class, 'index'])->name('api.movimientos-stock.index');
        Route::get('movimientos-stock/{id}', [MovimientoStockController::class, 'show'])->whereNumber('id')->name('api.movimientos-stock.show');
    });
    Route::post('movimientos-stock', [MovimientoStockController::class, 'store'])->middleware('permission:stock.manage')->name('api.movimientos-stock.store');

    Route::middleware('permission:productos.view')->group(function () {
        Route::get('productos', [ProductoController::class, 'index']);
        Route::get('productos/{producto}', [ProductoController::class, 'show']);
        Route::get('productos/proveedor/{proveedorId}', [ProductoController::class, 'getByProveedor']);
    });

    Route::middleware('permission:categorias.view')->group(function () {
        Route::get('categorias', [CategoriaController::class, 'index']);
        Route::get('categorias/{id}', [CategoriaController::class, 'show']);
    });

    Route::middleware('permission:categorias.manage')->group(function () {
        Route::post('categorias', [CategoriaController::class, 'store']);
        Route::put('categorias/{id}', [CategoriaController::class, 'update']);
        Route::patch('categorias/{id}', [CategoriaController::class, 'update']);
        Route::delete('categorias/{id}', [CategoriaController::class, 'destroy']);
    });

    Route::middleware('permission:productos.manage')->group(function () {
        Route::post('productos', [ProductoController::class, 'store']);
        Route::put('productos/{producto}', [ProductoController::class, 'update']);
        Route::patch('productos/{producto}', [ProductoController::class, 'update']);
        Route::delete('productos/{producto}', [ProductoController::class, 'destroy']);
        Route::post('productos/aumento-masivo', [ProductoController::class, 'aumentoMasivo']);
    });

    Route::middleware('permission:admin')->group(function () {
        Route::get('configuracion-fiscal', [ConfiguracionFiscalController::class, 'show']);
        Route::post('configuracion-fiscal', [ConfiguracionFiscalController::class, 'update']);

        Route::apiResource('proveedores', ProveedorController::class)->names('api.proveedores');

        Route::get('movimientos-caja', [MovimientoCajaController::class, 'index']);
        Route::post('movimientos-caja', [MovimientoCajaController::class, 'store']);
        Route::get('movimientos-caja/{id}', [MovimientoCajaController::class, 'show']);

        Route::get('cuentas-corrientes', [CuentaCorrienteController::class, 'index']);
        Route::post('cuentas-corrientes', [CuentaCorrienteController::class, 'store']);
        Route::get('cuentas-corrientes/{id}', [CuentaCorrienteController::class, 'show']);
        Route::post('cuentas-corrientes/{id}/movimiento', [CuentaCorrienteController::class, 'agregarMovimiento']);

        Route::get('deudas-clientes', [DeudaClienteController::class, 'index']);
        Route::post('deudas-clientes', [DeudaClienteController::class, 'store']);
        Route::get('deudas-clientes/{id}', [DeudaClienteController::class, 'show']);
        Route::post('deudas-clientes/{id}/pago', [DeudaClienteController::class, 'registrarPago']);

        Route::apiResource('cheques', ChequeController::class)->names('api.cheques');
        Route::get('cheques-proximos-vencer', [ChequeController::class, 'proximosAVencer']);
        Route::get('cheques-por-mes', [ChequeController::class, 'porMes']);
        Route::get('cheques-por-fecha', [ChequeController::class, 'porFecha']);
        Route::get('cheques-estadisticas', [ChequeController::class, 'estadisticas']);
        Route::post('cheques/{id}/marcar-cobrado', [ChequeController::class, 'marcarCobrado']);
    });
});
