<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PuntoCajaController extends Controller
{
    /**
     * Punto de caja (mostrador): Blade + Alpine, no React.
     * Registrado en /cajas/mostrador y /punto-caja por si el hosting trata mal el prefijo anidado.
     */
    public function show(Request $request): View
    {
        $u = $request->user();
        $k = $u ? strtolower(trim((string) $u->role)) : '';
        $esMostrador = $u && ($k === '' || ($k !== 'admin' && in_array($k, ['vendedor', 'vendedora', 'cajero', 'cajera'], true)));
        abort_unless($esMostrador, 403);

        return view('pages.punto-caja');
    }
}
