<?php

namespace App\Http\Controllers;

use App\Models\MovimientoCaja;
use App\Models\Caja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
        $validated = $request->validate(['caja_id' => ['required', 'integer', Caja::tenantExistsRule()], 'tipo' => 'required|in:ingreso,egreso', 'monto' => 'required|numeric|min:0.01', 'concepto' => 'required|string|max:255', 'observaciones' => 'nullable|string']);
        $movimiento = DB::transaction(function () use ($request, $validated) {
            $caja = Caja::lockForUpdate()->findOrFail($validated['caja_id']);
            if ($caja->estado !== 'abierta') { throw ValidationException::withMessages(['caja_id' => 'La caja está cerrada.']); }
            return MovimientoCaja::create($validated + ['usuario_id' => $request->user()->id]);
        });
        return response()->json($movimiento->load(['caja', 'usuario']), 201);
    }

    public function show($id)
    {
        $movimiento = MovimientoCaja::with(['caja', 'usuario'])->findOrFail($id);
        return response()->json($movimiento);
    }
}
