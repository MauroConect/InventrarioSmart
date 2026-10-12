<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
        $closedSales = $caja->ventas()->whereIn('estado', ['cerrada', 'completada']);
        $totalVentas = (clone $closedSales)->sum('total_final');
        $cantidadVentas = (clone $closedSales)->count();
        $totalIngresos = $caja->movimientos()->where('tipo', 'ingreso')->sum('monto');
        $totalEgresos = $caja->movimientos()->where('tipo', 'egreso')->sum('monto');
        $montoEsperado = $this->expectedCash($caja);

        // Obtener movimientos detallados
        $movimientos = $caja->movimientos()->with('usuario')->orderBy('created_at', 'desc')->get();
        
        // Obtener ventas detalladas
        $ventas = (clone $closedSales)->with('cliente')->orderBy('fecha', 'desc')->get();

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

        $ventasCuentaCorriente = $ventas
            ->filter(fn ($v) => $v->tipo_pago === 'cuenta_corriente')
            ->values()
            ->map(function ($v) {
                $nombreCliente = null;
                if ($v->cliente) {
                    $nombreCliente = trim($v->cliente->nombre.' '.$v->cliente->apellido);
                }

                return [
                    'id' => $v->id,
                    'numero_factura' => $v->numero_factura,
                    'total_final' => round((float) $v->total_final, 2),
                    'cliente_nombre' => $nombreCliente ?: null,
                ];
            })
            ->values()
            ->all();

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
                'ventas_cuenta_corriente' => $ventasCuentaCorriente,
            ],
            'movimientos' => $movimientos,
            'ventas' => $ventas,
        ]);
    }

    private function expectedCash(Caja $caja): float
    {
        $sales = $caja->ventas()->whereIn('estado', ['cerrada', 'completada']);
        $cash = (clone $sales)->where('tipo_pago', 'efectivo')->sum('total_final');
        $mixed = (clone $sales)->where('tipo_pago', 'mixto')->sum('monto_efectivo');
        return round((float) $caja->monto_apertura + $cash + $mixed + $caja->movimientos()->where('tipo', 'ingreso')->sum('monto') - $caja->movimientos()->where('tipo', 'egreso')->sum('monto'), 2);
    }

    public function cerrar(Request $request, $id)
    {
        $validated = $request->validate(['monto_real' => 'required|numeric|min:0', 'observaciones' => 'nullable|string']);
        $caja = DB::transaction(function () use ($request, $id, $validated) {
            $caja = Caja::lockForUpdate()->findOrFail($id);
            abort_unless($request->user()->isAdmin() || $caja->usuario_id === $request->user()->id, 403);
            if ($caja->estado === 'cerrada') { return $caja; }
            if ($caja->ventas()->where('estado', 'abierta')->exists()) { throw ValidationException::withMessages(['caja' => 'Hay comandas abiertas; cerralas o anulalas antes de cerrar la caja.']); }
            $expected = $this->expectedCash($caja);
            $caja->fill(['fecha_cierre' => now(), 'monto_real' => $validated['monto_real'], 'monto_cierre' => $validated['monto_real'], 'monto_esperado' => $expected, 'diferencia' => $validated['monto_real'] - $expected, 'estado' => 'cerrada']);
            if (isset($validated['observaciones'])) { $caja->observaciones = $validated['observaciones']; }
            $caja->save();
            return $caja;
        });
        return response()->json($caja->load('usuario'));
    }
}
