<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\DashboardController;

// Rutas de autenticación
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Rutas protegidas
Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    
    // Placeholder routes - se implementarán después
    Route::get('/categorias', function() { return view('pages.categorias'); })->name('categorias.index');
    Route::get('/productos', function() { return view('pages.productos'); })->name('productos.index');
    Route::get('/aumento-masivo-precios', function() { return view('pages.aumento-masivo'); })->name('aumento-masivo.index');
    Route::get('/proveedores', function() { return view('pages.proveedores'); })->name('proveedores.index');
    Route::get('/clientes', function() { return view('pages.clientes'); })->name('clientes.index');
    Route::get('/cajas', function() { return view('pages.cajas'); })->name('cajas.index');
    Route::get('/cuentas-corrientes', function() { return view('pages.cuentas-corrientes'); })->name('cuentas-corrientes.index');
    Route::get('/deudas-clientes', function() { return view('pages.deudas-clientes'); })->name('deudas-clientes.index');
    Route::get('/movimientos-stock', function() { return view('pages.movimientos-stock'); })->name('movimientos-stock.index');
    Route::get('/ventas', function() { return view('pages.ventas'); })->name('ventas.index');
    Route::get('/ventas/{id}', function($id) { return view('pages.venta-detalle', ['id' => $id]); })->name('ventas.show');
    Route::get('/cheques', function() { return view('pages.cheques'); })->name('cheques.index');
});
