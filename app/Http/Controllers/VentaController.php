<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use App\Models\ItemVenta;
use App\Models\Producto;
use App\Models\Caja;
use App\Models\VentaAdjunto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class VentaController extends Controller
{
    public function index(Request $request)
    {
        $query = Venta::with(['caja', 'cliente', 'items.producto']);

        if ($request->has('caja_id')) {
            $query->where('caja_id', $request->caja_id);
        }

        if ($request->has('cliente_id')) {
            $query->where('cliente_id', $request->cliente_id);
        }

        return response()->json($query->orderBy('fecha', 'desc')->paginate(15));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'caja_id' => 'required|exists:cajas,id',
            'cliente_id' => 'nullable|exists:clientes,id',
            'items' => 'required|array|min:1',
            'items.*.producto_id' => 'required|exists:productos,id',
            'items.*.cantidad' => 'required|integer|min:1',
            'descuento' => 'nullable|numeric|min:0',
            'tipo_pago' => 'required|in:efectivo,tarjeta,cuenta_corriente,mixto',
            'monto_tarjeta' => 'nullable|numeric|min:0|required_if:tipo_pago,mixto',
            'monto_efectivo' => 'nullable|numeric|min:0|required_if:tipo_pago,mixto',
            'cuotas' => 'nullable|integer|min:1|max:24',
            'recargo_cuotas' => 'nullable|numeric|min:0|max:100',
            'adjuntos' => 'nullable|array',
            'adjuntos.*' => 'file|max:10240',
        ]);

        $caja = Caja::findOrFail($validated['caja_id']);

        if ($caja->estado === 'cerrada') {
            return response()->json([
                'message' => 'No se pueden registrar ventas en una caja cerrada'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $total = 0;
            $items = [];

            foreach ($validated['items'] as $itemData) {
                $producto = Producto::findOrFail($itemData['producto_id']);

                if ($producto->stock_actual < $itemData['cantidad']) {
                                        throw new \Exception("Stock insuficiente para el producto: {$producto->nombre}. Stock disponible: {$producto->stock_actual}");
                }

                $subtotal = $producto->precio_venta * $itemData['cantidad'];
                $total += $subtotal;

                $items[] = [
                    'producto' => $producto,
                    'cantidad' => $itemData['cantidad'],
                    'precio_unitario' => $producto->precio_venta,
                    'subtotal' => $subtotal,
                ];

                // Actualizar stock
                $producto->stock_actual -= $itemData['cantidad'];
                $producto->save();
            }

            $descuento = $validated['descuento'] ?? 0;
            $totalFinal = $total - $descuento;

            // Generar número de factura
            $ultimaFactura = Venta::max('id') ?? 0;
            $numeroFactura = 'FAC-' . str_pad($ultimaFactura + 1, 8, '0', STR_PAD_LEFT);

            // Validar que la suma de montos mixtos sea igual al total final
            $montoTarjeta = null;
            $montoEfectivo = null;
            $cuotas = null;
            $montoCuota = null;

            if ($validated['tipo_pago'] === 'mixto') {
                $montoTarjeta = $validated['monto_tarjeta'] ?? 0;
                $montoEfectivo = $validated['monto_efectivo'] ?? 0;
                $sumaMontos = $montoTarjeta + $montoEfectivo;
                
                if (abs($sumaMontos - $totalFinal) > 0.01) {
                    return response()->json([
                        'message' => 'La suma de monto en tarjeta y monto en efectivo debe ser igual al total final de la venta.'
                    ], 400);
                }

                // Calcular monto por cuota si hay cuotas (solo para el monto de tarjeta)
                if (isset($validated['cuotas']) && $validated['cuotas'] > 0) {
                    $cuotas = $validated['cuotas'];
                    $montoCuota = $montoTarjeta / $cuotas;
                }
            } elseif ($validated['tipo_pago'] === 'tarjeta') {
                // Para pago con tarjeta, las cuotas se aplican al total final
                if (isset($validated['cuotas']) && $validated['cuotas'] > 0) {
                    $cuotas = $validated['cuotas'];
                    $montoCuota = $totalFinal / $cuotas;
                }
            }

            $venta = Venta::create([
                'caja_id' => $validated['caja_id'],
                'cliente_id' => $validated['cliente_id'] ?? null,
                'numero_factura' => $numeroFactura,
                'fecha' => now(),
                'total' => $total,
                'descuento' => $descuento,
                'total_final' => $totalFinal,
                'tipo_pago' => $validated['tipo_pago'],
                'monto_tarjeta' => $montoTarjeta,
                'monto_efectivo' => $montoEfectivo,
                'cuotas' => $cuotas,
                'monto_cuota' => $montoCuota,
                'recargo_cuotas' => $validated['recargo_cuotas'] ?? null,
                'estado' => 'completada',
            ]);

            foreach ($items as $item) {
                ItemVenta::create([
                    'venta_id' => $venta->id,
                    'producto_id' => $item['producto']->id,
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $item['precio_unitario'],
                    'subtotal' => $item['subtotal'],
                ]);
            }

            // Guardar adjuntos si vienen en el mismo request
            if ($request->hasFile('adjuntos')) {
                foreach ($request->file('adjuntos') as $file) {
                    $ruta = $file->store('ventas_adjuntos', 'public');

                    VentaAdjunto::create([
                        'venta_id' => $venta->id,
                        'ruta' => $ruta,
                        'nombre_original' => $file->getClientOriginalName(),
                        'mime' => $file->getClientMimeType(),
                        'size' => $file->getSize(),
                    ]);
                }
            }

            DB::commit();

            return response()->json($venta->load(['caja', 'cliente', 'items.producto', 'adjuntos']), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function show($id)
    {
        $venta = Venta::with(['caja', 'cliente', 'items.producto', 'adjuntos'])->findOrFail($id);
        return response()->json($venta);
    }

    public function agregarAdjuntos(Request $request, $id)
    {
        $venta = Venta::findOrFail($id);

        $validated = $request->validate([
            'adjuntos' => 'required|array',
            'adjuntos.*' => 'file|max:10240',
        ]);

        $adjuntosCreados = [];

        foreach ($request->file('adjuntos') as $file) {
            $ruta = $file->store('ventas_adjuntos', 'public');

            $adjunto = VentaAdjunto::create([
                'venta_id' => $venta->id,
                'ruta' => $ruta,
                'nombre_original' => $file->getClientOriginalName(),
                'mime' => $file->getClientMimeType(),
                'size' => $file->getSize(),
            ]);

            $adjuntosCreados[] = $adjunto;
        }

        return response()->json([
            'message' => 'Adjuntos cargados correctamente',
            'venta' => $venta->load('adjuntos'),
            'adjuntos' => $adjuntosCreados,
        ], 201);
    }
}
