<?php

use App\Http\Controllers\PlatformAuditController;
use App\Http\Controllers\PlatformDashboardController;
use App\Http\Controllers\PlatformModuleController;
use App\Http\Controllers\PlatformOnboardingController;
use App\Http\Controllers\PlatformSupportController;
use App\Http\Controllers\PlatformTenantController;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\EmailVerificationController;
use App\Http\Controllers\Web\LandingController;
use App\Http\Controllers\Web\OnboardingController;
use App\Http\Controllers\Web\RegisterController;
use App\Http\Controllers\Web\TenantSelectionController;
use Illuminate\Support\Facades\Route;

// Misma mecánica que GET /cajas y /clientes (closure): evita depender de otra clase y usa el prefijo /cajas/* que ya te funciona.
$puntoCajaView = static function () {
    $u = auth()->user();
    abort_unless($u && $u->hasPermission('cajas.view'), 403);

    return view('pages.punto-caja');
};

Route::get('/', LandingController::class)->name('landing');
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
Route::middleware('guest')->group(function () {
    Route::get('/forgot-password', [\App\Http\Controllers\Web\PasswordResetController::class, 'requestForm'])->name('password.request');
    Route::post('/forgot-password', [\App\Http\Controllers\Web\PasswordResetController::class, 'send'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [\App\Http\Controllers\Web\PasswordResetController::class, 'resetForm'])->name('password.reset');
    Route::post('/reset-password', [\App\Http\Controllers\Web\PasswordResetController::class, 'reset'])->middleware('throttle:6,1')->name('password.update');
});
Route::get('/register', [RegisterController::class, 'create'])->middleware('guest')->name('register');
Route::post('/register', [RegisterController::class, 'store'])->middleware(['guest', 'throttle:4,1'])->name('register.store');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () use ($puntoCajaView) {
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:3,1')
        ->name('verification.send');

    Route::middleware('verified')->group(function () {
        Route::get('/onboarding', [OnboardingController::class, 'show'])->name('onboarding.show');
        Route::post('/onboarding', [OnboardingController::class, 'store'])->name('onboarding.store');

        Route::get('/select-tenant', [TenantSelectionController::class, 'show'])->name('tenant.select');
        Route::post('/select-tenant', [TenantSelectionController::class, 'store'])->name('tenant.select.store');
    });

    Route::prefix('platform')
        ->middleware(['verified', 'platform.superadmin'])
        ->name('platform.')
        ->group(function () {
            Route::get('/', [PlatformDashboardController::class, 'index'])->name('dashboard');
            Route::get('/tenants', [PlatformTenantController::class, 'index'])->name('tenants.index');
            Route::get('/tenants/{tenant}', [PlatformTenantController::class, 'show'])->name('tenants.show');
            Route::patch('/tenants/{tenant}/suspend', [PlatformTenantController::class, 'suspend'])->name('tenants.suspend');
            Route::patch('/tenants/{tenant}/reactivate', [PlatformTenantController::class, 'reactivate'])->name('tenants.reactivate');
            Route::patch('/tenants/{tenant}/delivery', [PlatformTenantController::class, 'updateDelivery'])->name('tenants.delivery');
            Route::get('/modules', [PlatformModuleController::class, 'index'])->name('modules.index');
            Route::put('/tenants/{tenant}/modules', [PlatformModuleController::class, 'update'])->name('tenants.modules');
            Route::get('/onboarding', [PlatformOnboardingController::class, 'index'])->name('onboarding.index');
            Route::get('/onboarding/create', [PlatformOnboardingController::class, 'create'])->name('onboarding.create');
            Route::post('/onboarding', [PlatformOnboardingController::class, 'store'])->name('onboarding.store');
            Route::get('/onboarding/{onboarding}', [PlatformOnboardingController::class, 'show'])->name('onboarding.show');
            Route::patch('/onboarding/{onboarding}/status', [PlatformOnboardingController::class, 'updateStatus'])->name('onboarding.status');
            Route::post('/onboarding/{onboarding}/provision', [PlatformOnboardingController::class, 'provision'])->name('onboarding.provision');
            Route::post('/onboarding/{onboarding}/notes', [PlatformOnboardingController::class, 'storeNote'])->name('onboarding.notes');
            Route::get('/support', [PlatformSupportController::class, 'inbox'])->name('support.index');
            Route::get('/support/{conversation}', [PlatformSupportController::class, 'show'])->name('support.show');
            Route::get('/support/{conversation}/messages', [PlatformSupportController::class, 'poll'])->name('support.poll');
            Route::post('/support/{conversation}/messages', [PlatformSupportController::class, 'reply'])->name('support.reply');
            Route::patch('/support/{conversation}/request', [PlatformSupportController::class, 'updateRequest'])->name('support.request');
            Route::get('/audit', [PlatformAuditController::class, 'index'])->name('audit.index');
        });

    Route::middleware(['verified', 'tenant', 'onboarding.complete'])->group(function () use ($puntoCajaView) {
    Route::get('/app', function () {
        $user = request()->user();

        if ($user && $user->hasPermission('dashboard.view')) {
            return redirect()->route('dashboard');
        }

        if ($user && $user->isVendedor()) {
            return redirect('/cajas/punto');
        }

        if ($user && $user->hasPermission('ventas.view')) {
            return redirect()->route('ventas.index');
        }

        if ($user && $user->hasPermission('cajas.view')) {
            return redirect()->route('cajas.index');
        }

        foreach (['productos.view' => 'productos.index', 'cuentas_corrientes.view' => 'cuentas-corrientes.index', 'stock.view' => 'movimientos-stock.index', 'turnos.view' => 'turnos.index'] as $permission => $route) {
            if ($user && $user->hasPermission($permission)) { return redirect()->route($route); }
        }
        abort(403, 'No tienes permisos para acceder al sistema.');
    })->name('home');

    Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('permission:dashboard.view')->name('dashboard');

    Route::get('/categorias', function () {
        return view('pages.categorias');
    })->middleware(['feature:core.catalog', 'permission:categorias.view'])->name('categorias.index');
    Route::get('/productos', function () {
        return view('pages.productos');
    })->middleware(['feature:core.catalog', 'permission:productos.view'])->name('productos.index');
    Route::get('/aumento-masivo-precios', function () {
        return view('pages.aumento-masivo');
    })->middleware(['feature:core.catalog', 'permission:productos.manage'])->name('aumento-masivo.index');
    Route::get('/proveedores', function () {
        return view('pages.proveedores');
    })->middleware(['feature:core.catalog', 'permission:proveedores.view'])->name('proveedores.index');
    Route::get('/clientes', function () {
        return view('pages.clientes');
    })->middleware('permission:clientes.view')->name('clientes.index');

    // Punto de caja: primero /cajas/punto (mismo prefijo que GET /cajas que ya responde bien).
    Route::get('/cajas/punto', $puntoCajaView)->middleware('feature:core.cash')->name('cajas.punto');
    Route::get('/caja', $puntoCajaView);
    Route::get('/mi-caja', $puntoCajaView);
    Route::get('/mcaja', $puntoCajaView);
    Route::get('/punto-caja', $puntoCajaView);
    Route::get('/cajas/mostrador', $puntoCajaView);

    Route::get('/cajas', function () {
        return view('pages.cajas');
    })->middleware(['feature:core.cash', 'permission:cajas.view'])->name('cajas.index');

    Route::get('/caja-vendedor', fn () => redirect('/cajas/punto', 301));

    // JSON de cajas: solo routes/api.php → /api/cajas (misma URL que el SPA; evita 404 "The route cajas/api could not be found").
    Route::get('/cuentas-corrientes', function () {
        return view('pages.cuentas-corrientes');
    })->middleware(['feature:finance.credit', 'permission:cuentas_corrientes.view'])->name('cuentas-corrientes.index');
    Route::get('/cuentas-corrientes/{id}', function ($id) {
        return view('pages.cuenta-corriente-detalle', ['cuentaId' => (int) $id]);
    })->middleware(['feature:finance.credit', 'permission:cuentas_corrientes.view'])->whereNumber('id')->name('cuentas-corrientes.show');
    Route::get('/deudas-clientes', function () {
        return view('pages.deudas-clientes');
    })->middleware(['feature:finance.credit', 'permission:deudas.view'])->name('deudas-clientes.index');
    Route::get('/movimientos-stock', function () {
        return view('pages.movimientos-stock');
    })->middleware(['feature:core.stock', 'permission:stock.view'])->name('movimientos-stock.index');
    Route::get('/ventas', function () {
        return view('pages.ventas');
    })->middleware(['feature:core.pos', 'permission:ventas.view'])->name('ventas.index');
    Route::get('/ventas-abiertas', function () {
        return view('pages.ventas-abiertas');
    })->middleware(['feature:sales.open_orders', 'permission:ventas.view'])->name('ventas.abiertas');
    Route::get('/ventas/{id}', function ($id) {
        return view('pages.venta-detalle', ['id' => $id]);
    })->middleware('permission:ventas.view')->name('ventas.show');
    Route::get('/cheques', function () {
        return view('pages.cheques');
    })->middleware(['feature:finance.cheques', 'permission:cheques.view'])->name('cheques.index');
    Route::get('/configuracion-comercio', function () {
        return view('pages.configuracion-comercio');
    })->middleware('permission:admin')->name('configuracion-comercio.index');
    Route::get('/configuracion-fiscal', function () {
        return view('pages.configuracion-fiscal');
    })->middleware(['feature:fiscal.afip', 'permission:admin'])->name('configuracion-fiscal.index');
    Route::get('/usuarios', function () {
        return view('pages.usuarios');
    })->middleware('permission:admin')->name('usuarios.index');
    Route::get('/auditoria', function () {
        return view('pages.auditoria');
    })->middleware(['feature:operations.audit', 'permission:audit.view'])->name('auditoria.index');
    Route::get('/turnos', function () {
        return view('pages.turnos');
    })->middleware(['feature:services.appointments', 'permission:turnos.view'])->name('turnos.index');
    Route::get('/facturacion', function () {
        return view('pages.facturacion');
    })->middleware(['feature:fiscal.afip', 'permission:ventas.facturar'])->name('facturacion.index');
    });
});
