<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PuntoCajaController extends Controller
{
    /**
     * Punto de caja: Blade + Alpine. Misma capacidad de API que /cajas (abrir/cerrar).
     * Autorización: cajas.view (vendedor/admin pasan vía User::hasPermission).
     */
    public function show(Request $request): View
    {
        $u = $request->user();
        abort_unless($u && $u->hasPermission('cajas.view'), 403);

        return view('pages.punto-caja');
    }
}
