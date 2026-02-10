<?php

namespace App\Http\Controllers;

use App\Models\Cheque;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ChequeController extends Controller
{
    public function index(Request $request)
    {
        $query = Cheque::with(['cliente', 'proveedor', 'caja', 'usuario']);

        // Filtros
        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->has('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->has('fecha_vencimiento')) {
            $query->whereDate('fecha_vencimiento', $request->fecha_vencimiento);
        }

        if ($request->has('mes') && $request->has('ano')) {
            $query->whereYear('fecha_vencimiento', $request->ano)
                  ->whereMonth('fecha_vencimiento', $request->mes);
        }

        if ($request->has('proximos_vencer')) {
            $dias = $request->input('dias', 30);
            $fecha = Carbon::now()->addDays($dias);
            $query->where('estado', 'pendiente')
                  ->where('fecha_vencimiento', '<=', $fecha)
                  ->where('fecha_vencimiento', '>=', Carbon::now())
                  ->orderBy('fecha_vencimiento', 'asc');
        }

        if ($request->has('vencidos')) {
            $query->where('estado', 'pendiente')
                  ->where('fecha_vencimiento', '<', Carbon::now())
                  ->orderBy('fecha_vencimiento', 'asc');
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('numero_cheque', 'like', "%{$search}%")
                  ->orWhere('banco', 'like', "%{$search}%")
                  ->orWhereHas('cliente', function($q) use ($search) {
                      $q->where('nombre', 'like', "%{$search}%")
                        ->orWhere('apellido', 'like', "%{$search}%");
                  })
                  ->orWhereHas('proveedor', function($q) use ($search) {
                      $q->where('nombre', 'like', "%{$search}%");
                  });
            });
        }

        // Ordenamiento por defecto
        if (!$request->has('proximos_vencer') && !$request->has('vencidos')) {
            $query->orderBy('fecha_vencimiento', 'asc');
        }

        return response()->json($query->paginate($request->input('per_page', 15)));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'numero_cheque' => 'required|string|unique:cheques,numero_cheque',
            'banco' => 'required|string|max:255',
            'fecha_ingreso' => 'required|date',
            'fecha_vencimiento' => 'required|date|after_or_equal:fecha_ingreso',
            'monto' => 'required|numeric|min:0',
            'tipo' => 'required|in:recibido,emitido',
            'estado' => 'nullable|in:pendiente,cobrado,vencido,rechazado,depositado',
            'cliente_id' => 'nullable|exists:clientes,id',
            'proveedor_id' => 'nullable|exists:proveedores,id',
            'caja_id' => 'nullable|exists:cajas,id',
            'fecha_cobro' => 'nullable|date',
            'observaciones' => 'nullable|string',
        ]);

        // Verificar que solo uno de cliente o proveedor esté presente
        if (empty($validated['cliente_id']) && empty($validated['proveedor_id'])) {
            return response()->json([
                'message' => 'Debe especificar un cliente o un proveedor'
            ], 422);
        }

        if (!empty($validated['cliente_id']) && !empty($validated['proveedor_id'])) {
            return response()->json([
                'message' => 'Solo puede especificar un cliente o un proveedor, no ambos'
            ], 422);
        }

        $validated['usuario_id'] = $request->user()->id;
        $validated['estado'] = $validated['estado'] ?? 'pendiente';

        // Si el estado es cobrado, establecer fecha_cobro
        if ($validated['estado'] === 'cobrado' && empty($validated['fecha_cobro'])) {
            $validated['fecha_cobro'] = Carbon::now();
        }

        $cheque = Cheque::create($validated);
        return response()->json($cheque->load(['cliente', 'proveedor', 'caja', 'usuario']), 201);
    }

    public function show($id)
    {
        $cheque = Cheque::with(['cliente', 'proveedor', 'caja', 'usuario'])->findOrFail($id);
        return response()->json($cheque);
    }

    public function update(Request $request, $id)
    {
        $cheque = Cheque::findOrFail($id);

        $validated = $request->validate([
            'numero_cheque' => 'required|string|unique:cheques,numero_cheque,' . $id,
            'banco' => 'required|string|max:255',
            'fecha_ingreso' => 'required|date',
            'fecha_vencimiento' => 'required|date|after_or_equal:fecha_ingreso',
            'monto' => 'required|numeric|min:0',
            'tipo' => 'required|in:recibido,emitido',
            'estado' => 'nullable|in:pendiente,cobrado,vencido,rechazado,depositado',
            'cliente_id' => 'nullable|exists:clientes,id',
            'proveedor_id' => 'nullable|exists:proveedores,id',
            'caja_id' => 'nullable|exists:cajas,id',
            'fecha_cobro' => 'nullable|date',
            'observaciones' => 'nullable|string',
        ]);

        // Verificar que solo uno de cliente o proveedor esté presente
        if (empty($validated['cliente_id']) && empty($validated['proveedor_id'])) {
            return response()->json([
                'message' => 'Debe especificar un cliente o un proveedor'
            ], 422);
        }

        if (!empty($validated['cliente_id']) && !empty($validated['proveedor_id'])) {
            return response()->json([
                'message' => 'Solo puede especificar un cliente o un proveedor, no ambos'
            ], 422);
        }

        // Si el estado cambia a cobrado, establecer fecha_cobro
        if (isset($validated['estado']) && $validated['estado'] === 'cobrado' && empty($validated['fecha_cobro'])) {
            $validated['fecha_cobro'] = Carbon::now();
        }

        $cheque->update($validated);
        return response()->json($cheque->load(['cliente', 'proveedor', 'caja', 'usuario']));
    }

    public function destroy($id)
    {
        $cheque = Cheque::findOrFail($id);
        $cheque->delete();
        return response()->json(null, 204);
    }

    // Método para obtener cheques próximos a vencer para el dashboard
    public function proximosAVencer(Request $request)
    {
        $dias = $request->input('dias', 30);
        $cheques = Cheque::with(['cliente', 'proveedor'])
            ->proximosAVencer($dias)
            ->orderBy('fecha_vencimiento', 'asc')
            ->get();

        return response()->json($cheques);
    }

    // Método para obtener cheques por mes (calendario)
    public function porMes(Request $request)
    {
        $mes = $request->input('mes', Carbon::now()->month);
        $ano = $request->input('ano', Carbon::now()->year);

        $cheques = Cheque::with(['cliente', 'proveedor'])
            ->porMes($mes, $ano)
            ->orderBy('fecha_vencimiento', 'asc')
            ->get();

        // Agrupar por fecha
        $agrupados = $cheques->groupBy(function($cheque) {
            return Carbon::parse($cheque->fecha_vencimiento)->format('Y-m-d');
        });

        return response()->json($agrupados);
    }

    // Método para obtener cheques por fecha específica
    public function porFecha(Request $request)
    {
        $fecha = $request->input('fecha', Carbon::now()->format('Y-m-d'));

        $cheques = Cheque::with(['cliente', 'proveedor'])
            ->porFecha($fecha)
            ->orderBy('fecha_vencimiento', 'asc')
            ->get();

        return response()->json($cheques);
    }

    // Método para obtener estadísticas
    public function estadisticas(Request $request)
    {
        $hoy = Carbon::now();
        
        $estadisticas = [
            'total' => Cheque::count(),
            'pendientes' => Cheque::where('estado', 'pendiente')->count(),
            'cobrados' => Cheque::where('estado', 'cobrado')->count(),
            'vencidos' => Cheque::vencidos()->count(),
            'proximos_vencer' => Cheque::proximosAVencer(30)->count(),
            'monto_total_pendiente' => (float) Cheque::where('estado', 'pendiente')->sum('monto'),
            'monto_proximos_vencer' => (float) Cheque::proximosAVencer(30)->sum('monto'),
            'monto_vencidos' => (float) Cheque::vencidos()->sum('monto'),
        ];

        return response()->json($estadisticas);
    }

    // Método para marcar cheque como cobrado
    public function marcarCobrado(Request $request, $id)
    {
        $cheque = Cheque::findOrFail($id);

        $validated = $request->validate([
            'fecha_cobro' => 'nullable|date',
        ]);

        $cheque->estado = 'cobrado';
        $cheque->fecha_cobro = $validated['fecha_cobro'] ?? Carbon::now();
        $cheque->save();

        return response()->json($cheque->load(['cliente', 'proveedor', 'caja', 'usuario']));
    }
}
