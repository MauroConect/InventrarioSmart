<?php

namespace App\Http\Controllers;

use App\Models\MovimientoCaja;
use App\Models\Caja;
use Illuminate\Http\Request;

class MovimientoCajaController extends Controller
{
    public function index(Request $request)
    {
        $query = MovimientoCaja::with(['caja', 'usuario']);

        if ($request->has('caja_id')) {
            $query->where('caja_id', $request->caja_id);
        }

        return response()->json($query->orderBy('created_at', 'desc')->paginate(15));
    }

    public function store(Request $request)
    {
        $caja = Caja::findOrFail($request->caja_id);

        if ($caja->estado === 'cerrada') {
            return response()->json([
                'message' => 'No se pueden agregar movimientos a una caja cerrada'
            ], 400);
        }

        $validated = $request->validate([
            'caja_id' => 'required|exists:cajas,id',
            'tipo' => 'required|in:ingreso,egreso',
            'monto' => 'required|numeric|min:0.01',
            'concepto' => 'required|string|max:255',
            'observaciones' => 'nullable|string',
        ]);

        $validated['usuario_id'] = $request->user()->id;

        $movimiento = MovimientoCaja::create($validated);
        return response()->json($movimiento->load(['caja', 'usuario']), 201);
    }

    public function show($id)
    {
        $movimiento = MovimientoCaja::with(['caja', 'usuario'])->findOrFail($id);
        return response()->json($movimiento);
    }
}
