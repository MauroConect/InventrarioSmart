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

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:web')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Dashboard
    Route::get('/dashboard/estadisticas', [DashboardController::class, 'estadisticas']);
    Route::get('/dashboard/ventas-por-dia', [DashboardController::class, 'ventasPorDia']);
    Route::get('/dashboard/productos-mas-vendidos', [DashboardController::class, 'productosMasVendidos']);
    Route::get('/dashboard/resumen-cajas', [DashboardController::class, 'resumenCajas']);
    Route::get('/dashboard/ventas-por-tipo-pago', [DashboardController::class, 'ventasPorTipoPago']);

    // Categorías
    Route::apiResource('categorias', CategoriaController::class);

    // Productos
    Route::apiResource('productos', ProductoController::class);
    Route::get('productos/proveedor/{proveedorId}', [ProductoController::class, 'getByProveedor']);
    Route::post('productos/aumento-masivo', [ProductoController::class, 'aumentoMasivo']);

    // Proveedores
    Route::apiResource('proveedores', ProveedorController::class);

    // Clientes
    Route::apiResource('clientes', ClienteController::class);

    // Cajas
    Route::get('cajas', [CajaController::class, 'index']);
    Route::post('cajas', [CajaController::class, 'store']);
    Route::get('cajas/{id}', [CajaController::class, 'show']);
    Route::get('cajas/{id}/resumen-cierre', [CajaController::class, 'resumenCierre']);
    Route::post('cajas/{id}/cerrar', [CajaController::class, 'cerrar']);

    // Movimientos de Caja
    Route::get('movimientos-caja', [MovimientoCajaController::class, 'index']);
    Route::post('movimientos-caja', [MovimientoCajaController::class, 'store']);
    Route::get('movimientos-caja/{id}', [MovimientoCajaController::class, 'show']);

    // Cuentas Corrientes
    Route::get('cuentas-corrientes', [CuentaCorrienteController::class, 'index']);
    Route::post('cuentas-corrientes', [CuentaCorrienteController::class, 'store']);
    Route::get('cuentas-corrientes/{id}', [CuentaCorrienteController::class, 'show']);
    Route::post('cuentas-corrientes/{id}/movimiento', [CuentaCorrienteController::class, 'agregarMovimiento']);

    // Deudas de Clientes
    Route::get('deudas-clientes', [DeudaClienteController::class, 'index']);
    Route::post('deudas-clientes', [DeudaClienteController::class, 'store']);
    Route::get('deudas-clientes/{id}', [DeudaClienteController::class, 'show']);
    Route::post('deudas-clientes/{id}/pago', [DeudaClienteController::class, 'registrarPago']);

    // Movimientos de Stock
    Route::get('movimientos-stock', [MovimientoStockController::class, 'index']);
    Route::post('movimientos-stock', [MovimientoStockController::class, 'store']);
    Route::get('movimientos-stock/{id}', [MovimientoStockController::class, 'show']);

    // Ventas
    Route::get('ventas', [VentaController::class, 'index']);
    Route::post('ventas', [VentaController::class, 'store']);
    Route::get('ventas/{id}', [VentaController::class, 'show']);
    Route::post('ventas/{id}/adjuntos', [VentaController::class, 'agregarAdjuntos']);

    // Cheques
    Route::apiResource('cheques', ChequeController::class);
    Route::get('cheques-proximos-vencer', [ChequeController::class, 'proximosAVencer']);
    Route::get('cheques-por-mes', [ChequeController::class, 'porMes']);
    Route::get('cheques-por-fecha', [ChequeController::class, 'porFecha']);
    Route::get('cheques-estadisticas', [ChequeController::class, 'estadisticas']);
    Route::post('cheques/{id}/marcar-cobrado', [ChequeController::class, 'marcarCobrado']);
});
