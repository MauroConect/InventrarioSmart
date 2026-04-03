<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\PuntoCajaController;

// Rutas de autenticación
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Rutas protegidas
Route::middleware('auth')->group(function () {
    Route::get('/', function () {
        $user = request()->user();

        if ($user && $user->hasPermission('dashboard.view')) {
            return redirect()->route('dashboard');
        }

        if ($user && $user->hasPermission('ventas.view')) {
            return redirect()->route('ventas.index');
        }

        if ($user) {
            $k = strtolower(trim((string) $user->role));
            $esMostrador = $k === '' || ($k !== 'admin' && in_array($k, ['vendedor', 'vendedora', 'cajero', 'cajera'], true));
            if ($esMostrador) {
                return redirect('/caja');
            }

            return redirect()->route('cajas.index');
        }

        abort(403, 'No tienes permisos para acceder al sistema.');
    })->name('home');

    Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('permission:dashboard.view')->name('dashboard');
    
    Route::get('/categorias', function() { return view('pages.categorias'); })->middleware('permission:categorias.view')->name('categorias.index');
    Route::get('/productos', function() { return view('pages.productos'); })->middleware('permission:productos.view')->name('productos.index');
    Route::get('/aumento-masivo-precios', function() { return view('pages.aumento-masivo'); })->middleware('permission:productos.manage')->name('aumento-masivo.index');
    Route::get('/proveedores', function() { return view('pages.proveedores'); })->middleware('permission:proveedores.view')->name('proveedores.index');
    Route::get('/clientes', function() { return view('pages.clientes'); })->middleware('permission:clientes.view')->name('clientes.index');

    // Punto de caja: varias URLs. GET /caja antes que /cajas (misma raíz, distinto path).
    // Si ves 404 con Docker: montá ./routes (compose ya lo hace) y ejecutá `php artisan route:clear`
    // (bootstrap/cache montado puede tener routes-*.php viejo).
    Route::get('/caja', [PuntoCajaController::class, 'show'])->name('cajas.punto');
    Route::get('/mi-caja', [PuntoCajaController::class, 'show']);
    Route::get('/mcaja', [PuntoCajaController::class, 'show']);
    Route::get('/punto-caja', [PuntoCajaController::class, 'show']);
    Route::get('/cajas/mostrador', [PuntoCajaController::class, 'show']);

    Route::get('/cajas', function () {
        return view('pages.cajas');
    })->name('cajas.index');

    Route::get('/caja-vendedor', fn () => redirect('/caja', 301));

    // JSON de cajas: solo routes/api.php → /api/cajas (misma URL que el SPA; evita 404 "The route cajas/api could not be found").
    Route::get('/cuentas-corrientes', function() { return view('pages.cuentas-corrientes'); })->middleware('permission:cuentas_corrientes.view')->name('cuentas-corrientes.index');
    Route::get('/deudas-clientes', function() { return view('pages.deudas-clientes'); })->middleware('permission:deudas.view')->name('deudas-clientes.index');
    Route::get('/movimientos-stock', function() { return view('pages.movimientos-stock'); })->middleware('permission:stock.view')->name('movimientos-stock.index');
    Route::get('/ventas', function() { return view('pages.ventas'); })->middleware('permission:ventas.view')->name('ventas.index');
    Route::get('/ventas/{id}', function($id) { return view('pages.venta-detalle', ['id' => $id]); })->middleware('permission:ventas.view')->name('ventas.show');
    Route::get('/cheques', function() { return view('pages.cheques'); })->middleware('permission:cheques.view')->name('cheques.index');
    Route::get('/configuracion-fiscal', function() { return view('pages.configuracion-fiscal'); })->middleware('permission:admin')->name('configuracion-fiscal.index');
    Route::get('/facturacion', function() { return view('pages.facturacion'); })->middleware('permission:ventas.facturar')->name('facturacion.index');
});
