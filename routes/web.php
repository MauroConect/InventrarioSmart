<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\MenuQrController;

// Misma mecánica que GET /cajas y /clientes (closure): evita depender de otra clase y usa el prefijo /cajas/* que ya te funciona.
$puntoCajaView = static function () {
    $u = auth()->user();
    abort_unless($u && $u->hasPermission('cajas.view'), 403);

    return view('pages.punto-caja');
};

// Rutas de autenticación
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Menú público para acceso por QR (sin autenticación)
Route::get('/menu', [MenuQrController::class, 'index'])->name('menu.qr');
Route::get('/menu/imagenes/{filename}', [MenuQrController::class, 'imagenProducto'])
    ->where('filename', '.*')
    ->name('menu.qr.imagen');
Route::any('/cajas/{any?}', fn () => abort(404))->where('any', '.*');
Route::any('/ventas/{any?}', fn () => abort(404))->where('any', '.*');
Route::any('/facturacion/{any?}', fn () => abort(404))->where('any', '.*');

// Rutas protegidas
Route::middleware('auth')->group(function () use ($puntoCajaView) {
    Route::get('/', function () {
        $user = request()->user();

        if ($user && $user->hasPermission('categorias.view')) {
            return redirect()->route('categorias.index');
        }

        if ($user && $user->hasPermission('productos.view')) {
            return redirect()->route('productos.index');
        }

        abort(403, 'No tienes permisos para acceder al sistema.');
    })->name('home');

    Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('permission:dashboard.view')->name('dashboard');
    
    Route::get('/categorias', function() { return view('pages.categorias'); })->middleware('permission:categorias.view')->name('categorias.index');
    Route::get('/productos', function() { return view('pages.productos'); })->middleware('permission:productos.view')->name('productos.index');
    Route::get('/aumento-masivo-precios', function() { return view('pages.aumento-masivo'); })->middleware('permission:productos.manage')->name('aumento-masivo.index');
    Route::get('/proveedores', function() { return view('pages.proveedores'); })->middleware('permission:proveedores.view')->name('proveedores.index');
    Route::get('/clientes', function() { return view('pages.clientes'); })->middleware('permission:clientes.view')->name('clientes.index');

    // Punto de caja: primero /cajas/punto (mismo prefijo que GET /cajas que ya responde bien).
    Route::get('/cajas/punto', $puntoCajaView)->name('cajas.punto');
    Route::get('/caja', $puntoCajaView);
    Route::get('/mi-caja', $puntoCajaView);
    Route::get('/mcaja', $puntoCajaView);
    Route::get('/punto-caja', $puntoCajaView);
    Route::get('/cajas/mostrador', $puntoCajaView);

    Route::get('/cajas', function () {
        return view('pages.cajas');
    })->middleware('permission:cajas.view')->name('cajas.index');

    Route::get('/caja-vendedor', fn () => redirect('/cajas/punto', 301));

    // JSON de cajas: solo routes/api.php → /api/cajas (misma URL que el SPA; evita 404 "The route cajas/api could not be found").
    Route::get('/cuentas-corrientes', function() { return view('pages.cuentas-corrientes'); })->middleware('permission:cuentas_corrientes.view')->name('cuentas-corrientes.index');
    Route::get('/cuentas-corrientes/{id}', function ($id) {
        return view('pages.cuenta-corriente-detalle', ['cuentaId' => (int) $id]);
    })->middleware('permission:cuentas_corrientes.view')->whereNumber('id')->name('cuentas-corrientes.show');
    Route::get('/deudas-clientes', function() { return view('pages.deudas-clientes'); })->middleware('permission:deudas.view')->name('deudas-clientes.index');
    Route::get('/movimientos-stock', function() { return view('pages.movimientos-stock'); })->middleware('permission:stock.view')->name('movimientos-stock.index');
    Route::get('/ventas', function() { return view('pages.ventas'); })->middleware('permission:ventas.view')->name('ventas.index');
    Route::get('/ventas/{id}', function($id) { return view('pages.venta-detalle', ['id' => $id]); })->middleware('permission:ventas.view')->name('ventas.show');
    Route::get('/cheques', function() { return view('pages.cheques'); })->middleware('permission:cheques.view')->name('cheques.index');
    Route::get('/configuracion-fiscal', function() { return view('pages.configuracion-fiscal'); })->middleware('permission:admin')->name('configuracion-fiscal.index');
    Route::get('/usuarios', function () { return view('pages.usuarios'); })->middleware('permission:admin')->name('usuarios.index');
    Route::get('/auditoria', function () { return view('pages.auditoria'); })->middleware('permission:admin')->name('auditoria.index');
    Route::get('/ranking-ventas', function () { return view('pages.ranking-ventas'); })->middleware('permission:admin')->name('ranking-ventas.index');
    Route::get('/facturacion', function() { return view('pages.facturacion'); })->middleware('permission:ventas.facturar')->name('facturacion.index');
    Route::get('/configuracion-negocio', function() { return view('pages.configuracion-negocio'); })->middleware('permission:admin')->name('configuracion-negocio.index');
});
