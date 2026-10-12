<?php

namespace App\Http\Controllers;

use App\Models\MovimientoStock;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MovimientoStockController extends Controller
{
    public function index(Request $request)
    {
        $query = MovimientoStock::with(['producto', 'usuario']);

        if ($request->has('producto_id')) {
            $query->where('producto_id', $request->producto_id);
        }

        if ($request->has('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        return response()->json($query->orderBy('created_at', 'desc')->paginate(15));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'producto_id' => ['required', 'integer', Producto::tenantExistsRule()],
            'tipo' => 'required|in:entrada,salida,ajuste',
            'cantidad' => ['required', 'numeric', $request->input('tipo') === 'ajuste' ? 'min:0' : 'min:0.001'],
            'motivo' => 'required|string|max:255',
            'observaciones' => 'nullable|string',
        ]);

        $producto = Producto::findOrFail($validated['producto_id']);

        $movimiento = DB::transaction(function() use ($producto, $validated, $request) {
            $producto = Producto::lockForUpdate()->findOrFail($validated['producto_id']);
            $cantidadAnterior = $producto->stock_actual;
            if (! $producto->esPesable() && abs($validated['cantidad'] - round($validated['cantidad'])) > 0.000001) { throw \Illuminate\Validation\ValidationException::withMessages(['cantidad' => 'Un producto por unidad requiere cantidad entera.']); }
            $cantidad = $validated['cantidad'];

            if ($validated['tipo'] === 'entrada') {
                $cantidadActual = $cantidadAnterior + $cantidad;
            } elseif ($validated['tipo'] === 'salida') {
                if ($cantidadAnterior < $cantidad) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['cantidad' => 'Stock insuficiente']);
                }
                $cantidadActual = $cantidadAnterior - $cantidad;
            } else { // ajuste
                $cantidadActual = $cantidad;
                $cantidad = abs($cantidadActual - $cantidadAnterior);
            }

            $producto->stock_actual = $cantidadActual;
            $producto->save();

            $movimiento = MovimientoStock::create([
                'producto_id' => $producto->id,
                'tipo' => $validated['tipo'],
                'cantidad' => $cantidad,
                'cantidad_anterior' => $cantidadAnterior,
                'cantidad_actual' => $cantidadActual,
                'motivo' => $validated['motivo'],
                'observaciones' => $validated['observaciones'] ?? null,
                'usuario_id' => $request->user()->id,
            ]);

            return $movimiento;
        });

        return response()->json($movimiento->load(['producto', 'usuario']), 201);
    }

    public function show($id)
    {
        $movimiento = MovimientoStock::with(['producto', 'usuario'])->findOrFail($id);
        return response()->json($movimiento);
    }
}
