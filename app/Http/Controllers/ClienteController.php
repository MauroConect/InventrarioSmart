<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\DeudaCliente;
use App\Support\CuitValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClienteController extends Controller
{
    /**
     * Normaliza CUIT (solo dígitos o null) antes de validar.
     */
    protected function normalizeClienteRequest(Request $request): void
    {
        if (! $request->has('cuit')) {
            return;
        }

        $raw = $request->input('cuit');
        if ($raw === null || $raw === '') {
            $request->merge(['cuit' => null]);

            return;
        }

        $digits = CuitValidator::normalize((string) $raw);

        $request->merge(['cuit' => $digits === '' ? null : $digits]);
    }

    /**
     * @return array<int, \Closure|string>
     */
    protected function cuitRules(): array
    {
        return [
            'nullable',
            'string',
            'max:11',
            function (string $attribute, mixed $value, callable $fail): void {
                if ($value === null || $value === '') {
                    return;
                }
                if (strlen((string) $value) !== 11 || ! ctype_digit((string) $value)) {
                    $fail('El CUIT debe tener 11 digitos.');

                    return;
                }
                if (! CuitValidator::isValid((string) $value)) {
                    $fail('El CUIT del cliente no es valido.');
                }
            },
        ];
    }

    public function index(Request $request)
    {
        $query = Cliente::with('cuentaCorriente');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('apellido', 'like', "%{$search}%")
                  ->orWhere('dni', 'like', "%{$search}%")
                  ->orWhere('cuit', 'like', "%{$search}%");
            });
        }

        if ($request->boolean('all')) {
            return response()->json($query->orderBy('apellido')->orderBy('nombre')->get());
        }

        return response()->json($query->paginate(15));
    }

    public function store(Request $request)
    {
        $this->normalizeClienteRequest($request);

        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            'dni' => 'required|string|unique:clientes,dni|max:20',
            'cuit' => $this->cuitRules(),
            'telefono' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'direccion' => 'nullable|string',
            'activo' => 'boolean',
            'tiene_deuda' => 'boolean',
            'monto_deuda' => 'nullable|numeric|min:0.01|required_if:tiene_deuda,true',
            'cuotas_originales' => 'nullable|integer|min:1|required_if:tiene_deuda,true',
            'cuotas_pagadas' => 'nullable|integer|min:0',
            'cuotas_restantes' => 'nullable|integer|min:0',
        ]);

        DB::beginTransaction();
        try {
            $cuitCliente = isset($validated['cuit']) && $validated['cuit'] !== ''
                ? CuitValidator::normalize($validated['cuit'])
                : null;

            // Crear cliente
            $clienteData = [
                'nombre' => $validated['nombre'],
                'apellido' => $validated['apellido'],
                'dni' => $validated['dni'],
                'cuit' => $cuitCliente,
                'telefono' => $validated['telefono'] ?? null,
                'email' => $validated['email'] ?? null,
                'direccion' => $validated['direccion'] ?? null,
                'activo' => $validated['activo'] ?? true,
            ];
            
            $cliente = Cliente::create($clienteData);
            
            // Si tiene deuda, crear registro de deuda
            if (isset($validated['tiene_deuda']) && $validated['tiene_deuda'] && isset($validated['monto_deuda'])) {
                $cuotasOriginales = $validated['cuotas_originales'] ?? null;
                $cuotasPagadas = $validated['cuotas_pagadas'] ?? 0;
                $montoTotal = $validated['monto_deuda'];
                
                // Calcular monto pagado y pendiente
                $montoPagado = 0;
                $montoPendiente = $montoTotal;
                
                if ($cuotasOriginales && $cuotasOriginales > 0) {
                    $montoPorCuota = $montoTotal / $cuotasOriginales;
                    $montoPagado = $montoPorCuota * $cuotasPagadas;
                    $montoPendiente = $montoTotal - $montoPagado;
                }
                
                // Determinar estado
                $estado = 'pendiente';
                if ($montoPendiente <= 0) {
                    $estado = 'pagada';
                } elseif ($montoPagado > 0) {
                    $estado = 'parcial';
                }
                
                // Calcular cuotas restantes
                $cuotasRestantes = $cuotasOriginales ? ($cuotasOriginales - $cuotasPagadas) : null;
                
                DeudaCliente::create([
                    'cliente_id' => $cliente->id,
                    'venta_id' => null, // Deuda existente, no relacionada con una venta
                    'monto_total' => $montoTotal,
                    'monto_pagado' => $montoPagado,
                    'monto_pendiente' => $montoPendiente,
                    'cuotas_originales' => $cuotasOriginales,
                    'cuotas_pagadas' => $cuotasPagadas,
                    'cuotas_restantes' => $cuotasRestantes,
                    'estado' => $estado,
                    'observaciones' => 'Deuda existente registrada al crear el cliente',
                ]);
            }
            
            DB::commit();
            return response()->json($cliente->load('deudas'), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al crear cliente: ' . $e->getMessage()
            ], 400);
        }
    }

    public function show($id)
    {
        $cliente = Cliente::with(['cuentaCorriente', 'deudas'])->findOrFail($id);
        return response()->json($cliente);
    }

    public function update(Request $request, $id)
    {
        $cliente = Cliente::findOrFail($id);

        $this->normalizeClienteRequest($request);

        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            'dni' => 'required|string|unique:clientes,dni,' . $id . '|max:20',
            'cuit' => $this->cuitRules(),
            'telefono' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'direccion' => 'nullable|string',
            'activo' => 'sometimes|boolean',
        ]);

        if (isset($validated['cuit'])) {
            $validated['cuit'] = ($validated['cuit'] !== null && $validated['cuit'] !== '')
                ? CuitValidator::normalize($validated['cuit'])
                : null;
        }

        $cliente->update($validated);
        return response()->json($cliente);
    }

    public function destroy($id)
    {
        $cliente = Cliente::findOrFail($id);
        $cliente->delete();
        return response()->json(null, 204);
    }
}
