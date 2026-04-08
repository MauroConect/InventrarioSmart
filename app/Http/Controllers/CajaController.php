<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CajaController extends Controller
{
    public function index(Request $request)
    {
        $query = Caja::with(['usuario']);

        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->has('usuario_id')) {
            $query->where('usuario_id', $request->usuario_id);
        }

        return response()->json($query->orderBy('fecha_apertura', 'desc')->paginate(15));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'nullable|string|max:255',
            'monto_apertura' => 'required|numeric|min:0',
            'observaciones' => 'nullable|string',
        ]);

        $validated['usuario_id'] = $request->user()->id;
        $validated['fecha_apertura'] = now();
        $validated['estado'] = 'abierta';

        // Si no se proporciona nombre, generar uno automático
        if (empty($validated['nombre'])) {
            $ultimaCaja = Caja::where('usuario_id', $request->user()->id)
                ->orderBy('id', 'desc')
                ->first();
            $numeroCaja = $ultimaCaja ? ($ultimaCaja->id + 1) : 1;
            $validated['nombre'] = 'Caja ' . $numeroCaja . ' - ' . now()->format('d/m/Y H:i');
        }

        $caja = Caja::create($validated);
        return response()->json($caja->load('usuario'), 201);
    }

    public function show($id)
    {
        $caja = Caja::with(['usuario', 'movimientos', 'ventas'])->findOrFail($id);
        return response()->json($caja);
    }

    public function resumenCierre(Request $request, $id)
    {
        $caja = Caja::with(['usuario', 'movimientos.usuario', 'ventas.cliente'])->findOrFail($id);

        if ($caja->estado === 'cerrada') {
            return response()->json([
                'message' => 'La caja ya está cerrada'
            ], 400);
        }

        // Calcular montos
        $totalVentas = $caja->ventas()->sum('total_final');
        $cantidadVentas = $caja->ventas()->count();
        $totalIngresos = $caja->movimientos()->where('tipo', 'ingreso')->sum('monto');
        $totalEgresos = $caja->movimientos()->where('tipo', 'egreso')->sum('monto');
        $montoEsperado = $caja->monto_apertura + $totalVentas + $totalIngresos - $totalEgresos;

        // Obtener movimientos detallados
        $movimientos = $caja->movimientos()->with('usuario')->orderBy('created_at', 'desc')->get();
        
        // Obtener ventas detalladas
        $ventas = $caja->ventas()->with('cliente')->orderBy('fecha', 'desc')->get();

        $porMedioPago = [
            'efectivo' => 0.0,
            'tarjeta' => 0.0,
            'transferencia' => 0.0,
            'cuenta_corriente' => 0.0,
        ];
        foreach ($ventas as $v) {
            $tf = (float) $v->total_final;
            switch ($v->tipo_pago) {
                case 'efectivo':
                    $porMedioPago['efectivo'] += $tf;
                    break;
                case 'tarjeta':
                    $porMedioPago['tarjeta'] += $tf;
                    break;
                case 'transferencia':
                    $porMedioPago['transferencia'] += $tf;
                    break;
                case 'cuenta_corriente':
                    $porMedioPago['cuenta_corriente'] += $tf;
                    break;
                case 'mixto':
                    $porMedioPago['efectivo'] += (float) ($v->monto_efectivo ?? 0);
                    $porMedioPago['tarjeta'] += (float) ($v->monto_tarjeta ?? 0);
                    $porMedioPago['transferencia'] += (float) ($v->monto_transferencia ?? 0);
                    break;
            }
        }
        foreach ($porMedioPago as $k => $val) {
            $porMedioPago[$k] = round($val, 2);
        }

        return response()->json([
            'caja' => $caja,
            'resumen' => [
                'monto_apertura' => (float) $caja->monto_apertura,
                'total_ventas' => (float) $totalVentas,
                'cantidad_ventas' => $cantidadVentas,
                'total_ingresos' => (float) $totalIngresos,
                'cantidad_ingresos' => $caja->movimientos()->where('tipo', 'ingreso')->count(),
                'total_egresos' => (float) $totalEgresos,
                'cantidad_egresos' => $caja->movimientos()->where('tipo', 'egreso')->count(),
                'monto_esperado' => round($montoEsperado, 2),
                'por_medio_pago' => $porMedioPago,
            ],
            'movimientos' => $movimientos,
            'ventas' => $ventas,
        ]);
    }

    public function cerrar(Request $request, $id)
    {
        $caja = Caja::findOrFail($id);

        if ($caja->estado === 'cerrada') {
            return response()->json([
                'message' => 'La caja ya está cerrada'
            ], 400);
        }

        if ($caja->usuario_id !== $request->user()->id) {
            return response()->json([
                'message' => 'No tiene permiso para cerrar esta caja'
            ], 403);
        }

        $ventasAbiertas = Venta::where('caja_id', $caja->id)
            ->whereIn('estado', ['abierta', 'abierto'])
            ->count();

        if ($ventasAbiertas > 0) {
            return response()->json([
                'message' => 'No se puede cerrar la caja porque existen ventas con estado abierto.'
            ], 400);
        }

        // Calcular monto esperado
        $montoEsperado = $caja->monto_apertura + $caja->ventas()->sum('total_final');
        $ingresos = $caja->movimientos()->where('tipo', 'ingreso')->sum('monto');
        $egresos = $caja->movimientos()->where('tipo', 'egreso')->sum('monto');
        $montoEsperado = $montoEsperado + $ingresos - $egresos;

        $validated = $request->validate([
            'monto_real' => 'required|numeric|min:0',
            'observaciones' => 'nullable|string',
        ]);

        $caja->fecha_cierre = now();
        $caja->monto_real = $validated['monto_real'];
        $caja->monto_cierre = $validated['monto_real']; // Mantener compatibilidad
        $caja->monto_esperado = $montoEsperado;
        $caja->diferencia = $validated['monto_real'] - $montoEsperado;
        $caja->estado = 'cerrada';
        if (isset($validated['observaciones'])) {
            $caja->observaciones = $validated['observaciones'];
        }

        $caja->save();
        return response()->json($caja->load('usuario'));
    }
}
