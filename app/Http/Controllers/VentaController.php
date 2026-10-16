<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use App\Models\ItemVenta;
use App\Models\Producto;
use App\Models\Caja;
use App\Models\Cliente;
use App\Models\VentaAdjunto;
use App\Models\CuentaCorriente;
use App\Models\MovimientoStock;
use App\Services\Tenancy\TenantReceiptNumber;
use App\Models\MovimientoCuentaCorriente;
use App\Services\AfipFacturacionService;
use App\Services\Modules\TenantFeatureService;
use App\Services\PersistentUploadStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        abort_unless($this->features->enabled('sales.open_orders'), 403);
        $validated = $request->validate(['caja_id' => ['required', 'integer', Caja::tenantExistsRule()], 'cliente_id' => ['nullable', 'integer', Cliente::tenantExistsRule()], 'etiqueta' => 'nullable|string|max:80']);
        $venta = DB::transaction(function () use ($validated, $request) {
            $caja = Caja::lockForUpdate()->findOrFail($validated['caja_id']);
            if ($caja->estado !== 'abierta') { throw ValidationException::withMessages(['caja_id' => 'La caja está cerrada.']); }
            return Venta::create(['caja_id' => $caja->id, 'cliente_id' => $validated['cliente_id'] ?? null, 'usuario_id' => $request->user()->id, 'etiqueta' => $validated['etiqueta'] ?? null, 'numero_factura' => app(TenantReceiptNumber::class)->next('CMD'), 'fecha' => now(), 'total' => 0, 'descuento' => 0, 'total_final' => 0, 'tipo_pago' => 'efectivo', 'estado' => 'abierta']);
        });
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
            $caja = Caja::lockForUpdate()->findOrFail($validated['caja_id']);
            if ($caja->estado === 'cerrada') { throw ValidationException::withMessages(['caja_id' => 'La caja está cerrada.']); }
            $total = 0;
            $items = [];
            $lockedProducts = Producto::whereIn('id', array_column($validated['items'], 'producto_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');

            foreach ($validated['items'] as $itemData) {
                $producto = $lockedProducts->get($itemData['producto_id']);
                if (! $producto) { throw ValidationException::withMessages(['items' => 'Producto no disponible.']); }
                $cantidad = $this->normalizeCantidad($producto, (float) $itemData['cantidad']);

                $before = (float) $producto->stock_actual;
                if ($cantidad > $before) { throw ValidationException::withMessages(['items' => 'Stock insuficiente.']); }
                $subtotal = round($producto->precio_venta * $cantidad, 2);
                $total += $subtotal;

                $items[] = [
                    'producto' => $producto,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $producto->precio_venta,
                    'subtotal' => $subtotal,
                    'stock_before' => $before,
                    'stock_after' => $before - $cantidad,
                ];

                $producto->stock_actual -= $cantidad;
                $producto->save();
            }

            $descuento = $validated['descuento'] ?? 0;
            if ($descuento > $total) { throw ValidationException::withMessages(['descuento' => 'El descuento supera el total.']); }
            $totalFinal = round($total - $descuento, 2);

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
                    ->lockForUpdate()->first();

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
            $numeroFactura = app(TenantReceiptNumber::class)->next('FAC');

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
                MovimientoStock::create(['producto_id' => $item['producto']->id, 'tipo' => 'salida', 'cantidad' => $item['cantidad'], 'cantidad_anterior' => $item['stock_before'], 'cantidad_actual' => $item['stock_after'], 'motivo' => 'Venta '.$numeroFactura, 'usuario_id' => $request->user()->id]);
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
            foreach ($uploadedAttachments as $attachment) { app(PersistentUploadStore::class)->delete($attachment->ruta, 'private'); }
            if ($e instanceof ValidationException) { throw $e; }
            return response()->json(['message' => 'No se pudo registrar la venta.'], 400);
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
            Caja::lockForUpdate()->findOrFail($venta->caja_id);
            $venta = Venta::lockForUpdate()->findOrFail($id);
            if ($venta->estado !== 'abierta') { throw ValidationException::withMessages(['venta' => 'La comanda ya no está abierta.']); }
            $lockedProducts = Producto::whereIn('id', array_column($validated['items'], 'producto_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            foreach ($validated['items'] as $itemData) {
                $producto = $lockedProducts->get($itemData['producto_id']);
                $cantidad = $this->normalizeCantidad($producto, (float) $itemData['cantidad']);
                $before = (float) $producto->stock_actual;
                if ($cantidad > $before) { throw ValidationException::withMessages(['items' => 'Stock insuficiente.']); }
                $subtotal = round((float) $producto->precio_venta * $cantidad, 2);

                ItemVenta::create([
                    'venta_id' => $venta->id,
                    'producto_id' => $producto->id,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $producto->precio_venta,
                    'subtotal' => $subtotal,
                ]);

                $producto->stock_actual -= $cantidad;
                $producto->save();
                MovimientoStock::create(['producto_id' => $producto->id, 'tipo' => 'salida', 'cantidad' => $cantidad, 'cantidad_anterior' => $before, 'cantidad_actual' => $producto->stock_actual, 'motivo' => 'Comanda '.$venta->numero_factura, 'usuario_id' => $request->user()->id]);
            }

            $this->recalcularTotalesVenta($venta->fresh(['items']));

            DB::commit();

            return response()->json(
                $venta->fresh()->load(['caja', 'cliente', 'usuario', 'items.producto', 'adjuntos']),
                200
            );
        } catch (\Exception $e) {
            DB::rollBack();

            if ($e instanceof ValidationException) { throw $e; }
            return response()->json(['message' => 'No se pudo actualizar la comanda.'], 400);
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
            Caja::lockForUpdate()->findOrFail($venta->caja_id);
            $venta = Venta::lockForUpdate()->findOrFail($id);
            if ($venta->estado !== 'abierta') { throw ValidationException::withMessages(['venta' => 'La comanda ya no está abierta.']); }
            $venta->descuento = (float) $validated['descuento'];
            $this->recalcularTotalesVenta($venta);

            DB::commit();

            return response()->json(
                $venta->fresh()->load(['caja', 'cliente', 'usuario', 'items.producto', 'adjuntos']),
                200
            );
        } catch (\Exception $e) {
            DB::rollBack();

            if ($e instanceof ValidationException) { throw $e; }
            return response()->json(['message' => 'No se pudo actualizar el descuento.'], 400);
        }
    }

    private function recalcularTotalesVenta(Venta $venta): void
    {
        $venta->load('items');
        $total = (float) $venta->items->sum(fn ($i) => (float) $i->subtotal);
        $descuento = (float) ($venta->descuento ?? 0);
        $venta->total = $total;
        if ($descuento > $total) { throw ValidationException::withMessages(['descuento' => 'El descuento supera el total.']); }
        $venta->total_final = round($total - $descuento, 2);

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
        return app(PersistentUploadStore::class)->download($attachment->ruta, 'private', $attachment->nombre_original, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function eliminarAdjunto($id, $adjunto)
    {
        $attachment = Venta::findOrFail($id)->adjuntos()->findOrFail($adjunto);
        app(\App\Services\Tenancy\SaleAttachmentService::class)->assertPrivatePath($attachment);
        app(PersistentUploadStore::class)->delete($attachment->ruta, 'private');
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
            return response()->json(['message' => 'La venta ya está cerrada.', 'venta' => $venta]);
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
            $cash = Caja::lockForUpdate()->findOrFail($venta->caja_id);
            $venta = Venta::lockForUpdate()->findOrFail($id);
            if ($venta->estado === 'cerrada') { DB::commit(); return response()->json(['message' => 'La venta ya está cerrada.', 'venta' => $venta]); }
            if ($venta->estado !== 'abierta' || $cash->estado === 'cerrada') { throw ValidationException::withMessages(['venta' => 'La comanda o caja no está abierta.']); }
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
                $cuenta = CuentaCorriente::where('cliente_id', $venta->cliente_id)->where('activa', true)->lockForUpdate()->first();
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

            if ($e instanceof ValidationException) { throw $e; }
            return response()->json(['message' => 'No se pudo cerrar la venta.'], 400);
        }
    }

    public function cancelar($id)
    {
        abort_unless($this->features->enabled('sales.open_orders'), 403, 'El módulo de ventas abiertas no está habilitado.');

        $venta = Venta::with('items.producto')->findOrFail($id);
        if ($venta->estado === 'cancelada') { return response()->json(['message' => 'Comanda ya anulada.', 'venta' => $venta]); }
        if ($venta->estado !== 'abierta') {
            return response()->json(['message' => 'Solo se puede anular una comanda abierta.'], 400);
        }

        DB::beginTransaction();
        try {
            Caja::lockForUpdate()->findOrFail($venta->caja_id);
            $venta = Venta::lockForUpdate()->findOrFail($id);
            if ($venta->estado === 'cancelada') { DB::commit(); return response()->json(['message' => 'Comanda ya anulada.', 'venta' => $venta]); }
            if ($venta->estado !== 'abierta') { throw ValidationException::withMessages(['venta' => 'La comanda ya no está abierta.']); }
            $venta->load('items');
            foreach ($venta->items as $item) {
                $producto = Producto::lockForUpdate()->find($item->producto_id);
                if ($producto) {
                    $before = (float) $producto->stock_actual;
                    $producto->stock_actual = (float) $producto->stock_actual + (float) $item->cantidad;
                    $producto->save();
                    MovimientoStock::create(['producto_id' => $producto->id, 'tipo' => 'entrada', 'cantidad' => $item->cantidad, 'cantidad_anterior' => $before, 'cantidad_actual' => $producto->stock_actual, 'motivo' => 'Anulación '.$venta->numero_factura, 'usuario_id' => request()->user()->id]);
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
            'venta_ids.*' => ['integer', Venta::tenantExistsRule()],
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
        if ($producto->esPesable() && ! $this->features->enabled('catalog.weighted')) { throw ValidationException::withMessages(['items' => 'El módulo de productos pesables está deshabilitado.']); }
        $pesable = $producto->esPesable();

        if ($pesable) {
            if ($cantidad < 0.001) {
                throw ValidationException::withMessages([
                    'items' => 'La cantidad de un producto pesable debe ser al menos 0.001.',
                ]);
            }

            return round($cantidad, 3);
        }

        if (abs($cantidad - round($cantidad)) > 0.000001) { throw ValidationException::withMessages(['items' => 'Un producto por unidad requiere cantidad entera.']); }
        $enteros = (int) round($cantidad);
        if ($enteros < 1) {
            throw ValidationException::withMessages([
                'items' => 'La cantidad debe ser al menos 1.',
            ]);
        }

        return (float) $enteros;
    }
}
