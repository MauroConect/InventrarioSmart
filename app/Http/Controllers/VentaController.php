<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use App\Models\ItemVenta;
use App\Models\Producto;
use App\Models\Caja;
use App\Models\Cliente;
use App\Models\VentaAdjunto;
use App\Models\CuentaCorriente;
use App\Models\MovimientoCuentaCorriente;
use App\Services\AfipFacturacionService;
use App\Services\Modules\TenantFeatureService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class VentaController extends Controller
{
    public function __construct(
        private AfipFacturacionService $afipFacturacionService,
        private TenantFeatureService $features,
    ) {
    }

    public function index(Request $request)
    {
        $query = Venta::with(['caja', 'cliente', 'usuario', 'items.producto']);

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->has('caja_id')) {
            $query->where('caja_id', $request->caja_id);
        }

        if ($request->has('cliente_id')) {
            $query->where('cliente_id', $request->cliente_id);
        }

        if ($request->filled('estado_facturacion')) {
            $query->where('estado_facturacion', $request->estado_facturacion);
        }

        return response()->json($query->orderBy('fecha', 'desc')->paginate(15));
    }

    public function abiertas()
    {
        abort_unless($this->features->enabled('sales.open_orders'), 404, 'El módulo de ventas abiertas no está habilitado.');

        $ventas = Venta::query()
            ->with(['caja', 'cliente', 'usuario', 'items.producto'])
            ->where('estado', 'abierta')
            ->orderByDesc('id')
            ->get();

        return response()->json(['data' => $ventas]);
    }

    public function abrir(Request $request)
    {
        abort_unless($this->features->enabled('sales.open_orders'), 403, 'El módulo de ventas abiertas no está habilitado.');

        $validated = $request->validate([
            'caja_id' => ['required', 'integer', Caja::tenantExistsRule()],
            'cliente_id' => ['nullable', 'integer', Cliente::tenantExistsRule()],
            'etiqueta' => 'nullable|string|max:80',
        ]);

        $caja = Caja::findOrFail($validated['caja_id']);
        if ($caja->estado === 'cerrada') {
            return response()->json(['message' => 'No se pueden abrir comandas en una caja cerrada.'], 400);
        }

        $ultimaFactura = Venta::max('id') ?? 0;
        $venta = Venta::create([
            'caja_id' => $caja->id,
            'cliente_id' => $validated['cliente_id'] ?? null,
            'usuario_id' => $request->user()->id,
            'etiqueta' => $validated['etiqueta'] ?? null,
            'numero_factura' => 'CMD-'.str_pad((string) ($ultimaFactura + 1), 8, '0', STR_PAD_LEFT),
            'fecha' => now(),
            'total' => 0,
            'descuento' => 0,
            'total_final' => 0,
            'tipo_pago' => 'efectivo',
            'estado' => 'abierta',
        ]);

        return response()->json($venta->load(['caja', 'cliente', 'usuario', 'items.producto']), 201);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'caja_id' => ['required', 'integer', Caja::tenantExistsRule()],
            'cliente_id' => ['nullable', 'integer', Cliente::tenantExistsRule()],
            'items' => 'required|array|min:1',
            'adjuntos' => 'nullable|array|max:5',
            'adjuntos.*' => 'file|mimes:pdf,jpg,jpeg,png,webp|max:10240',
            'items.*.producto_id' => ['required', 'integer', Producto::tenantExistsRule()],
            'items.*.cantidad' => 'required|numeric|min:0.001',
            'descuento' => 'nullable|numeric|min:0',
            'tipo_pago' => 'required|in:efectivo,tarjeta,transferencia,cuenta_corriente,mixto',
            'monto_tarjeta' => 'nullable|numeric|min:0',
            'monto_efectivo' => 'nullable|numeric|min:0',
            'monto_transferencia' => 'nullable|numeric|min:0',
            'cuotas' => 'nullable|integer|min:1|max:24',
            'recargo_cuotas' => 'nullable|numeric|min:0|max:100',
            'adjuntos' => 'nullable|array',
            'adjuntos.*' => 'file|max:10240',
        ]);

        $this->assertCreditPaymentAllowed($validated['tipo_pago'] ?? null);

        $caja = Caja::findOrFail($validated['caja_id']);

        if ($caja->estado === 'cerrada') {
            return response()->json([
                'message' => 'No se pueden registrar ventas en una caja cerrada'
            ], 400);
        }

        $uploadedAttachments = [];
        DB::beginTransaction();
        try {
            $total = 0;
            $items = [];

            foreach ($validated['items'] as $itemData) {
                $producto = Producto::findOrFail($itemData['producto_id']);
                $cantidad = $this->normalizeCantidad($producto, (float) $itemData['cantidad']);

                $subtotal = $producto->precio_venta * $cantidad;
                $total += $subtotal;

                $items[] = [
                    'producto' => $producto,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $producto->precio_venta,
                    'subtotal' => $subtotal,
                ];

                $producto->stock_actual -= $cantidad;
                $producto->save();
            }

            $descuento = $validated['descuento'] ?? 0;
            $totalFinal = $total - $descuento;

            $cuentaCorrienteParaVenta = null;
            $saldoActualCuentaCorriente = null;
            if ($validated['tipo_pago'] === 'cuenta_corriente') {
                if (empty($validated['cliente_id'])) {
                    DB::rollBack();

                    return response()->json([
                        'message' => 'Debe seleccionar un cliente para ventas en cuenta corriente.',
                    ], 422);
                }

                $cuentaCorrienteParaVenta = CuentaCorriente::where('cliente_id', $validated['cliente_id'])
                    ->where('activa', true)
                    ->first();

                if (! $cuentaCorrienteParaVenta) {
                    DB::rollBack();

                    return response()->json([
                        'message' => 'El cliente no tiene una cuenta corriente activa. Solicite al administrador que la cree en Cuentas corrientes.',
                    ], 422);
                }

                $saldoActualCuentaCorriente = (float) $cuentaCorrienteParaVenta->saldo;
                $limite = (float) $cuentaCorrienteParaVenta->limite_credito;
                if ($limite > 0 && ($saldoActualCuentaCorriente + $totalFinal) > $limite + 0.009) {
                    DB::rollBack();

                    return response()->json([
                        'message' => 'La venta supera el límite de crédito de la cuenta corriente del cliente.',
                    ], 422);
                }
            }

            // Generar número de factura
            $ultimaFactura = Venta::max('id') ?? 0;
            $numeroFactura = 'FAC-' . str_pad($ultimaFactura + 1, 8, '0', STR_PAD_LEFT);

            // Validar que la suma de montos mixtos sea igual al total final
            $montoTarjeta = null;
            $montoEfectivo = null;
            $montoTransferencia = null;
            $cuotas = null;
            $montoCuota = null;

            if ($validated['tipo_pago'] === 'mixto') {
                $montoTarjeta = (float) ($validated['monto_tarjeta'] ?? 0);
                $montoEfectivo = (float) ($validated['monto_efectivo'] ?? 0);
                $montoTransferencia = (float) ($validated['monto_transferencia'] ?? 0);
                $sumaMontos = $montoTarjeta + $montoEfectivo + $montoTransferencia;

                if (abs($sumaMontos - $totalFinal) > 0.01) {
                    DB::rollBack();

                    return response()->json([
                        'message' => 'La suma de efectivo, tarjeta y transferencia debe ser igual al total final de la venta.'
                    ], 400);
                }

                if (isset($validated['cuotas']) && $validated['cuotas'] > 0) {
                    $cuotas = $validated['cuotas'];
                    $montoCuota = $montoTarjeta > 0 ? $montoTarjeta / $cuotas : null;
                }
            } elseif ($validated['tipo_pago'] === 'tarjeta') {
                // Para pago con tarjeta, las cuotas se aplican al total final
                if (isset($validated['cuotas']) && $validated['cuotas'] > 0) {
                    $cuotas = $validated['cuotas'];
                    $montoCuota = $totalFinal / $cuotas;
                }
            } elseif ($validated['tipo_pago'] === 'transferencia') {
                $montoTransferencia = $totalFinal;
            }

            $venta = Venta::create([
                'caja_id' => $validated['caja_id'],
                'cliente_id' => $validated['cliente_id'] ?? null,
                'usuario_id' => $request->user()->id,
                'numero_factura' => $numeroFactura,
                'fecha' => now(),
                'total' => $total,
                'descuento' => $descuento,
                'total_final' => $totalFinal,
                'tipo_pago' => $validated['tipo_pago'],
                'monto_tarjeta' => $montoTarjeta,
                'monto_efectivo' => $montoEfectivo,
                'monto_transferencia' => $montoTransferencia,
                'cuotas' => $cuotas,
                'monto_cuota' => $montoCuota,
                'recargo_cuotas' => $validated['recargo_cuotas'] ?? null,
                'estado' => 'cerrada',
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

            if ($cuentaCorrienteParaVenta && $saldoActualCuentaCorriente !== null) {
                MovimientoCuentaCorriente::create([
                    'cuenta_corriente_id' => $cuentaCorrienteParaVenta->id,
                    'tipo' => 'debe',
                    'monto' => $totalFinal,
                    'concepto' => 'Venta '.$numeroFactura,
                    'venta_id' => $venta->id,
                ]);
                $cuentaCorrienteParaVenta->saldo = $saldoActualCuentaCorriente + $totalFinal;
                $cuentaCorrienteParaVenta->save();
            }

            if ($request->hasFile('adjuntos')) {
                $uploadedAttachments = app(\App\Services\Tenancy\SaleAttachmentService::class)->upload($venta, $request->file('adjuntos'));
            }

            DB::commit();

            return response()->json($venta->load(['caja', 'cliente', 'usuario', 'items.producto', 'adjuntos']), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            foreach ($uploadedAttachments as $attachment) { Storage::disk('private')->delete($attachment->ruta); }
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function show($id)
    {
        $venta = Venta::with(['caja', 'cliente', 'usuario', 'items.producto', 'adjuntos'])->findOrFail($id);
        return response()->json($venta);
    }

    /**
     * Agrega líneas a una venta existente (caja abierta, venta no facturada ni cancelada).
     */
    public function agregarItems(Request $request, $id)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.producto_id' => ['required', 'integer', Producto::tenantExistsRule()],
            'items.*.cantidad' => 'required|numeric|min:0.001',
        ]);

        $venta = Venta::with(['caja', 'items'])->findOrFail($id);

        if (! $this->features->enabled('sales.open_orders')) {
            return response()->json([
                'message' => 'El módulo de ventas abiertas no está habilitado para este comercio.',
            ], 403);
        }

        if ($venta->estado !== 'abierta') {
            return response()->json(['message' => 'Solo se pueden agregar productos a una venta abierta.'], 400);
        }

        if (($venta->estado_facturacion ?? 'pendiente') === 'facturada') {
            return response()->json(['message' => 'No se pueden agregar productos a una venta ya facturada.'], 400);
        }

        DB::beginTransaction();
        try {
            foreach ($validated['items'] as $itemData) {
                $producto = Producto::findOrFail($itemData['producto_id']);
                $cantidad = $this->normalizeCantidad($producto, (float) $itemData['cantidad']);
                $subtotal = (float) $producto->precio_venta * $cantidad;

                ItemVenta::create([
                    'venta_id' => $venta->id,
                    'producto_id' => $producto->id,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $producto->precio_venta,
                    'subtotal' => $subtotal,
                ]);

                $producto->stock_actual -= $cantidad;
                $producto->save();
            }

            $this->recalcularTotalesVenta($venta->fresh(['items']));

            DB::commit();

            return response()->json(
                $venta->fresh()->load(['caja', 'cliente', 'usuario', 'items.producto', 'adjuntos']),
                200
            );
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function actualizarDescuento(Request $request, $id)
    {
        $validated = $request->validate([
            'descuento' => 'required|numeric|min:0',
        ]);

        $venta = Venta::with(['items'])->findOrFail($id);

        if ($venta->estado === 'cancelada') {
            return response()->json(['message' => 'No se puede actualizar el descuento de una venta cancelada.'], 400);
        }

        if ($venta->estado === 'cerrada') {
            return response()->json(['message' => 'No se puede actualizar el descuento de una venta cerrada.'], 400);
        }

        if (($venta->estado_facturacion ?? 'pendiente') === 'facturada') {
            return response()->json(['message' => 'No se puede actualizar el descuento de una venta facturada.'], 400);
        }

        DB::beginTransaction();
        try {
            $venta->descuento = (float) $validated['descuento'];
            $this->recalcularTotalesVenta($venta);

            DB::commit();

            return response()->json(
                $venta->fresh()->load(['caja', 'cliente', 'usuario', 'items.producto', 'adjuntos']),
                200
            );
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    private function recalcularTotalesVenta(Venta $venta): void
    {
        $venta->load('items');
        $total = (float) $venta->items->sum(fn ($i) => (float) $i->subtotal);
        $descuento = (float) ($venta->descuento ?? 0);
        $venta->total = $total;
        $venta->total_final = $total - $descuento;

        switch ($venta->tipo_pago) {
            case 'transferencia':
                $venta->monto_transferencia = $venta->total_final;
                break;
            case 'tarjeta':
                $venta->monto_tarjeta = $venta->total_final;
                if ($venta->cuotas && (int) $venta->cuotas > 0) {
                    $venta->monto_cuota = $venta->total_final / (int) $venta->cuotas;
                }
                break;
            case 'efectivo':
                $venta->monto_efectivo = null;
                $venta->monto_tarjeta = null;
                $venta->monto_transferencia = null;
                break;
            case 'cuenta_corriente':
                break;
            case 'mixto':
                break;
        }

        $venta->save();
    }

    public function agregarAdjuntos(Request $request, $id)
    {
        $venta = Venta::findOrFail($id);

        $request->validate(['adjuntos' => 'required|array|min:1|max:5', 'adjuntos.*' => 'file|mimes:pdf,jpg,jpeg,png,webp|max:10240']);
        $adjuntosCreados = app(\App\Services\Tenancy\SaleAttachmentService::class)->upload($venta, $request->file('adjuntos'));

        return response()->json([
            'message' => 'Adjuntos cargados correctamente',
            'venta' => $venta->load('adjuntos'),
            'adjuntos' => $adjuntosCreados,
        ], 201);
    }

    public function descargarAdjunto($id, $adjunto)
    {
        $attachment = Venta::findOrFail($id)->adjuntos()->findOrFail($adjunto);
        try { app(\App\Services\Tenancy\SaleAttachmentService::class)->assertPrivatePath($attachment); }
        catch (\RuntimeException) { abort(404); }
        abort_unless(Storage::disk('private')->exists($attachment->ruta), 404);
        return Storage::disk('private')->download($attachment->ruta, $attachment->nombre_original, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function eliminarAdjunto($id, $adjunto)
    {
        $attachment = Venta::findOrFail($id)->adjuntos()->findOrFail($adjunto);
        app(\App\Services\Tenancy\SaleAttachmentService::class)->assertPrivatePath($attachment);
        Storage::disk('private')->delete($attachment->ruta);
        $attachment->delete();
        return response()->noContent();
    }

    public function cerrar(Request $request, $id)
    {
        $venta = Venta::with(['items', 'cliente'])->findOrFail($id);

        if ($venta->estado === 'cancelada') {
            return response()->json(['message' => 'No se puede cerrar una venta cancelada.'], 400);
        }

        if ($venta->estado === 'cerrada') {
            return response()->json(['message' => 'La venta ya está cerrada.'], 400);
        }

        if ($venta->items->isEmpty()) {
            return response()->json(['message' => 'No se puede cerrar una comanda sin productos.'], 400);
        }

        $validated = $request->validate([
            'tipo_pago' => 'nullable|in:efectivo,tarjeta,transferencia,cuenta_corriente,mixto',
            'cliente_id' => ['nullable', 'integer', Cliente::tenantExistsRule()],
            'monto_tarjeta' => 'nullable|numeric|min:0',
            'monto_efectivo' => 'nullable|numeric|min:0',
            'monto_transferencia' => 'nullable|numeric|min:0',
            'descuento' => 'nullable|numeric|min:0',
        ]);

        $this->assertCreditPaymentAllowed($validated['tipo_pago'] ?? $venta->tipo_pago);

        DB::beginTransaction();
        try {
            if (array_key_exists('cliente_id', $validated)) {
                $venta->cliente_id = $validated['cliente_id'];
            }
            if (isset($validated['descuento'])) {
                $venta->descuento = (float) $validated['descuento'];
            }
            $this->recalcularTotalesVenta($venta);

            $tipoPago = $validated['tipo_pago'] ?? $venta->tipo_pago;
            $totalFinal = (float) $venta->total_final;
            $venta->tipo_pago = $tipoPago;
            $venta->monto_tarjeta = null;
            $venta->monto_efectivo = null;
            $venta->monto_transferencia = null;

            if ($tipoPago === 'mixto') {
                $montoTarjeta = (float) ($validated['monto_tarjeta'] ?? 0);
                $montoEfectivo = (float) ($validated['monto_efectivo'] ?? 0);
                $montoTransferencia = (float) ($validated['monto_transferencia'] ?? 0);
                if (abs(($montoTarjeta + $montoEfectivo + $montoTransferencia) - $totalFinal) > 0.01) {
                    DB::rollBack();

                    return response()->json(['message' => 'La suma de los montos mixtos debe igualar el total.'], 400);
                }
                $venta->monto_tarjeta = $montoTarjeta;
                $venta->monto_efectivo = $montoEfectivo;
                $venta->monto_transferencia = $montoTransferencia;
            } elseif ($tipoPago === 'transferencia') {
                $venta->monto_transferencia = $totalFinal;
            } elseif ($tipoPago === 'tarjeta') {
                $venta->monto_tarjeta = $totalFinal;
            } elseif ($tipoPago === 'efectivo') {
                $venta->monto_efectivo = $totalFinal;
            } elseif ($tipoPago === 'cuenta_corriente') {
                if (! $venta->cliente_id) {
                    DB::rollBack();

                    return response()->json(['message' => 'La cuenta corriente requiere un cliente.'], 422);
                }
                $cuenta = CuentaCorriente::where('cliente_id', $venta->cliente_id)->where('activa', true)->first();
                if (! $cuenta) {
                    DB::rollBack();

                    return response()->json(['message' => 'El cliente no tiene cuenta corriente activa.'], 422);
                }
                $limite = (float) $cuenta->limite_credito;
                if ($limite > 0 && ((float) $cuenta->saldo + $totalFinal) > $limite + 0.009) {
                    DB::rollBack();

                    return response()->json(['message' => 'La venta supera el límite de crédito.'], 422);
                }
                MovimientoCuentaCorriente::create([
                    'cuenta_corriente_id' => $cuenta->id,
                    'tipo' => 'debe',
                    'monto' => $totalFinal,
                    'concepto' => 'Venta '.$venta->numero_factura,
                    'venta_id' => $venta->id,
                ]);
                $cuenta->saldo = (float) $cuenta->saldo + $totalFinal;
                $cuenta->save();
            }

            $venta->estado = 'cerrada';
            $venta->save();
            DB::commit();

            return response()->json([
                'message' => 'Venta cerrada correctamente.',
                'venta' => $venta->fresh(['caja', 'cliente', 'usuario', 'items.producto', 'adjuntos']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function cancelar($id)
    {
        abort_unless($this->features->enabled('sales.open_orders'), 403, 'El módulo de ventas abiertas no está habilitado.');

        $venta = Venta::with('items.producto')->findOrFail($id);
        if ($venta->estado !== 'abierta') {
            return response()->json(['message' => 'Solo se puede anular una comanda abierta.'], 400);
        }

        DB::beginTransaction();
        try {
            foreach ($venta->items as $item) {
                $producto = $item->producto;
                if ($producto) {
                    $producto->stock_actual = (float) $producto->stock_actual + (float) $item->cantidad;
                    $producto->save();
                }
            }
            $venta->estado = 'cancelada';
            $venta->save();
            DB::commit();

            return response()->json([
                'message' => 'Comanda anulada.',
                'venta' => $venta->fresh(['caja', 'cliente', 'usuario', 'items.producto']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function facturarAfip($id)
    {
        $venta = Venta::with(['cliente', 'items.producto'])->findOrFail($id);

        if ($venta->estado_facturacion === 'facturada') {
            return response()->json([
                'message' => 'La venta ya se encuentra facturada.',
                'venta' => $venta,
            ]);
        }

        try {
            $resultado = $this->afipFacturacionService->facturarVenta($venta);

            $venta->update([
                'estado_facturacion' => 'facturada',
                'comprobante_tipo' => $resultado['comprobante_tipo'],
                'comprobante_numero' => $resultado['comprobante_numero'],
                'cae' => $resultado['cae'],
                'cae_vencimiento' => $resultado['cae_vencimiento'],
                'afip_observaciones' => null,
                'facturada_at' => now(),
            ]);

            return response()->json([
                'message' => 'Venta facturada correctamente en AFIP/ARCA.',
                'venta' => $venta->fresh(['cliente', 'usuario', 'items.producto']),
                'afip' => $resultado['afip_response'],
            ]);
        } catch (\Throwable $e) {
            $venta->update([
                'estado_facturacion' => 'error',
                'afip_observaciones' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'No se pudo facturar la venta en AFIP/ARCA.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    public function pendientesFacturacion(Request $request)
    {
        $estado = $request->query('estado_facturacion', 'todos');

        $query = Venta::with(['cliente']);

        if ($estado === 'pendiente') {
            $query->where('estado_facturacion', 'pendiente');
        } elseif ($estado === 'error') {
            $query->where('estado_facturacion', 'error');
        } else {
            $query->whereIn('estado_facturacion', ['pendiente', 'error']);
        }

        return response()->json($query->orderBy('fecha', 'desc')->paginate(30));
    }

    public function facturarLote(Request $request)
    {
        $validated = $request->validate([
            'venta_ids' => 'required|array|min:1',
            'venta_ids.*' => ['integer', 'integer', Venta::tenantExistsRule()],
        ]);

        $resultados = [];

        foreach ($validated['venta_ids'] as $ventaId) {
            $venta = Venta::with(['cliente', 'items.producto'])->findOrFail($ventaId);

            if ($venta->estado_facturacion === 'facturada') {
                $resultados[] = [
                    'venta_id' => $ventaId,
                    'ok' => true,
                    'message' => 'Ya estaba facturada.',
                ];
                continue;
            }

            try {
                $resultado = $this->afipFacturacionService->facturarVenta($venta);

                $venta->update([
                    'estado_facturacion' => 'facturada',
                    'comprobante_tipo' => $resultado['comprobante_tipo'],
                    'comprobante_numero' => $resultado['comprobante_numero'],
                    'cae' => $resultado['cae'],
                    'cae_vencimiento' => $resultado['cae_vencimiento'],
                    'afip_observaciones' => null,
                    'facturada_at' => now(),
                ]);

                $resultados[] = [
                    'venta_id' => $ventaId,
                    'ok' => true,
                    'message' => 'Facturada correctamente.',
                ];
            } catch (\Throwable $e) {
                $venta->update([
                    'estado_facturacion' => 'error',
                    'afip_observaciones' => $e->getMessage(),
                ]);

                $resultados[] = [
                    'venta_id' => $ventaId,
                    'ok' => false,
                    'message' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'message' => 'Proceso de facturacion por lote finalizado.',
            'resultados' => $resultados,
        ]);
    }

    private function assertCreditPaymentAllowed(?string $tipoPago): void
    {
        if ($tipoPago === 'cuenta_corriente' && ! $this->features->enabled('finance.credit')) {
            throw ValidationException::withMessages([
                'tipo_pago' => 'El módulo de cuenta corriente no está habilitado.',
            ]);
        }
    }

    private function normalizeCantidad(Producto $producto, float $cantidad): float
    {
        $pesable = $this->features->enabled('catalog.weighted') && $producto->esPesable();

        if ($pesable) {
            if ($cantidad < 0.001) {
                throw ValidationException::withMessages([
                    'items' => 'La cantidad de un producto pesable debe ser al menos 0.001.',
                ]);
            }

            return round($cantidad, 3);
        }

        $enteros = (int) round($cantidad);
        if ($enteros < 1) {
            throw ValidationException::withMessages([
                'items' => 'La cantidad debe ser al menos 1.',
            ]);
        }

        return (float) $enteros;
    }
}
