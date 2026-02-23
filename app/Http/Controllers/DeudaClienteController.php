<?php

namespace App\Http\Controllers;

use App\Models\DeudaCliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeudaClienteController extends Controller
{
    public function index(Request $request)
    {
        $query = DeudaCliente::with(['cliente', 'venta']);

        if ($request->has('cliente_id')) {
            $query->where('cliente_id', $request->cliente_id);
        }

        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        return response()->json($query->orderBy('created_at', 'desc')->paginate(15));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'venta_id' => 'nullable|exists:ventas,id',
            'monto_total' => 'required|numeric|min:0.01',
            'cuotas_originales' => 'nullable|integer|min:1',
            'cuotas_pagadas' => 'nullable|integer|min:0',
            'cuotas_restantes' => 'nullable|integer|min:0',
            'fecha_vencimiento' => 'nullable|date',
            'observaciones' => 'nullable|string',
        ]);

        $validated['monto_pagado'] = 0;
        $validated['monto_pendiente'] = $validated['monto_total'];
        $validated['estado'] = 'pendiente';
        
        // Si hay cuotas pagadas, calcular monto pagado y pendiente
        if (isset($validated['cuotas_originales']) && isset($validated['cuotas_pagadas'])) {
            $cuotasOriginales = $validated['cuotas_originales'];
            $cuotasPagadas = $validated['cuotas_pagadas'] ?? 0;
            $montoTotal = $validated['monto_total'];
            
            if ($cuotasOriginales > 0) {
                $montoPorCuota = $montoTotal / $cuotasOriginales;
                $validated['monto_pagado'] = $montoPorCuota * $cuotasPagadas;
                $validated['monto_pendiente'] = $montoTotal - $validated['monto_pagado'];
                
                if ($validated['monto_pendiente'] <= 0) {
                    $validated['estado'] = 'pagada';
                } elseif ($validated['monto_pagado'] > 0) {
                    $validated['estado'] = 'parcial';
                }
            }
            
            // Calcular cuotas restantes si no se proporcionó
            if (!isset($validated['cuotas_restantes'])) {
                $validated['cuotas_restantes'] = $cuotasOriginales - $cuotasPagadas;
            }
        }

        $deuda = DeudaCliente::create($validated);
        return response()->json($deuda->load(['cliente', 'venta']), 201);
    }

    public function show($id)
    {
        $deuda = DeudaCliente::with(['cliente', 'venta'])->findOrFail($id);
        return response()->json($deuda);
    }

    public function registrarPago(Request $request, $id)
    {
        $deuda = DeudaCliente::findOrFail($id);

        $validated = $request->validate([
            'monto' => 'required|numeric|min:0.01|max:' . $deuda->monto_pendiente,
        ]);

        DB::transaction(function() use ($deuda, $validated) {
            $deuda->monto_pagado += $validated['monto'];
            $deuda->monto_pendiente -= $validated['monto'];

            if ($deuda->monto_pendiente <= 0) {
                $deuda->estado = 'pagada';
            } elseif ($deuda->monto_pagado > 0) {
                $deuda->estado = 'parcial';
            }

            // Verificar si está vencida
            if ($deuda->fecha_vencimiento && $deuda->fecha_vencimiento < now() && $deuda->monto_pendiente > 0) {
                $deuda->estado = 'vencida';
            }

            $deuda->save();
        });

        return response()->json($deuda->load(['cliente', 'venta']));
    }
}
