<?php

namespace App\Http\Controllers;

use App\Models\Caja;
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
