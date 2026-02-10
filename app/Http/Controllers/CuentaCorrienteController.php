<?php

namespace App\Http\Controllers;

use App\Models\CuentaCorriente;
use App\Models\Cliente;
use App\Models\MovimientoCuentaCorriente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CuentaCorrienteController extends Controller
{
    public function index(Request $request)
    {
        $query = CuentaCorriente::with(['cliente']);

        if ($request->has('cliente_id')) {
            $query->where('cliente_id', $request->cliente_id);
        }

        return response()->json($query->paginate(15));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id|unique:cuentas_corrientes,cliente_id',
            'limite_credito' => 'required|numeric|min:0',
            'activa' => 'boolean',
        ]);

        $validated['saldo'] = 0;

        $cuenta = CuentaCorriente::create($validated);
        return response()->json($cuenta->load('cliente'), 201);
    }

    public function show($id)
    {
        $cuenta = CuentaCorriente::with(['cliente', 'movimientos'])->findOrFail($id);
        return response()->json($cuenta);
    }

    public function agregarMovimiento(Request $request, $id)
    {
        $cuenta = CuentaCorriente::findOrFail($id);

        $validated = $request->validate([
            'tipo' => 'required|in:debe,haber',
            'monto' => 'required|numeric|min:0.01',
            'concepto' => 'required|string|max:255',
            'observaciones' => 'nullable|string',
            'venta_id' => 'nullable|exists:ventas,id',
        ]);

        DB::transaction(function() use ($cuenta, $validated) {
            $movimiento = MovimientoCuentaCorriente::create([
                'cuenta_corriente_id' => $cuenta->id,
                ...$validated
            ]);

            // Actualizar saldo
            if ($validated['tipo'] === 'debe') {
                $cuenta->saldo += $validated['monto'];
            } else {
                $cuenta->saldo -= $validated['monto'];
            }
            $cuenta->save();
        });

        return response()->json($cuenta->load(['cliente', 'movimientos']));
    }
}
