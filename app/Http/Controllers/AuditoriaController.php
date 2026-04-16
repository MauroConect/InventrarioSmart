<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Models\Cheque;
use App\Models\MovimientoCaja;
use App\Models\MovimientoCuentaCorriente;
use App\Models\MovimientoStock;
use App\Models\Venta;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class AuditoriaController extends Controller
{
    private const TIPO_PAGO_LABELS = [
        'efectivo' => 'Efectivo',
        'tarjeta' => 'Tarjeta',
        'transferencia' => 'Transferencia',
        'cuenta_corriente' => 'Cuenta corriente',
        'mixto' => 'Mixto',
    ];

    public function timeline(Request $request)
    {
        $validated = $request->validate([
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:10|max:100',
            'categoria' => 'sometimes|string|in:todas,caja,venta,stock,cuenta_corriente,cheque',
            'desde' => 'nullable|date',
            'hasta' => 'nullable|date',
        ]);

        $perPage = (int) ($validated['per_page'] ?? 40);
        $page = (int) ($validated['page'] ?? 1);
        $categoria = $validated['categoria'] ?? 'todas';
        $desde = isset($validated['desde']) ? Carbon::parse($validated['desde'])->startOfDay() : null;
        $hasta = isset($validated['hasta']) ? Carbon::parse($validated['hasta'])->endOfDay() : null;

        $inRange = function (Carbon $dt) use ($desde, $hasta): bool {
            if ($desde !== null && $dt->lt($desde)) {
                return false;
            }
            if ($hasta !== null && $dt->gt($hasta)) {
                return false;
            }

            return true;
        };

        $events = collect();

        if ($categoria === 'todas' || $categoria === 'caja') {
            $cajas = Caja::with('usuario')->orderByDesc('id')->take(500)->get();
            foreach ($cajas as $caja) {
                if ($caja->fecha_apertura) {
                    $dt = Carbon::parse($caja->fecha_apertura);
                    if ($inRange($dt)) {
                        $events->push([
                            'occurred_at' => $dt->toIso8601String(),
                            'categoria' => 'caja',
                            'tipo' => 'apertura',
                            'titulo' => 'Apertura de caja',
                            'detalle' => sprintf(
                                '%s — Apertura $%s%s',
                                $caja->nombre ?: ('Caja #'.$caja->id),
                                number_format((float) $caja->monto_apertura, 2, ',', '.'),
                                $caja->observaciones ? ' — '.mb_substr($caja->observaciones, 0, 120) : ''
                            ),
                            'usuario' => $caja->usuario?->name,
                            'meta' => ['caja_id' => $caja->id],
                        ]);
                    }
                }

                if ($caja->fecha_cierre) {
                    $dt = Carbon::parse($caja->fecha_cierre);
                    if ($inRange($dt)) {
                        $diff = $caja->diferencia !== null ? number_format((float) $caja->diferencia, 2, ',', '.') : '—';
                        $events->push([
                            'occurred_at' => $dt->toIso8601String(),
                            'categoria' => 'caja',
                            'tipo' => 'cierre',
                            'titulo' => 'Cierre de caja',
                            'detalle' => sprintf(
                                '%s — Real $%s — Esperado $%s — Diferencia $%s',
                                $caja->nombre ?: ('Caja #'.$caja->id),
                                number_format((float) ($caja->monto_real ?? $caja->monto_cierre ?? 0), 2, ',', '.'),
                                number_format((float) ($caja->monto_esperado ?? 0), 2, ',', '.'),
                                $diff
                            ),
                            'usuario' => $caja->usuario?->name,
                            'meta' => ['caja_id' => $caja->id],
                        ]);
                    }
                }
            }

            $movs = MovimientoCaja::with(['caja', 'usuario'])->orderByDesc('created_at')->take(600)->get();
            foreach ($movs as $m) {
                $dt = Carbon::parse($m->created_at);
                if (! $inRange($dt)) {
                    continue;
                }
                $tipoM = $m->tipo === 'ingreso' ? 'Ingreso caja' : 'Egreso caja';
                $events->push([
                    'occurred_at' => $dt->toIso8601String(),
                    'categoria' => 'caja',
                    'tipo' => 'movimiento_'.$m->tipo,
                    'titulo' => $tipoM,
                    'detalle' => sprintf(
                        '%s — $%s — %s',
                        $m->caja?->nombre ?: ('Caja #'.$m->caja_id),
                        number_format((float) $m->monto, 2, ',', '.'),
                        $m->concepto
                    ).($m->observaciones ? ' — '.mb_substr($m->observaciones, 0, 100) : ''),
                    'usuario' => $m->usuario?->name,
                    'meta' => ['caja_id' => $m->caja_id, 'movimiento_caja_id' => $m->id],
                ]);
            }
        }

        if ($categoria === 'todas' || $categoria === 'venta') {
            $ventas = Venta::with(['usuario', 'caja', 'cliente'])->orderByDesc('id')->take(700)->get();
            foreach ($ventas as $v) {
                $created = $v->created_at ? Carbon::parse($v->created_at) : Carbon::parse($v->fecha);
                if ($inRange($created)) {
                    $tp = self::TIPO_PAGO_LABELS[$v->tipo_pago] ?? $v->tipo_pago;
                    $cli = $v->cliente ? trim($v->cliente->nombre.' '.$v->cliente->apellido) : 'Consumidor final';
                    $events->push([
                        'occurred_at' => $created->toIso8601String(),
                        'categoria' => 'venta',
                        'tipo' => 'venta_registrada',
                        'titulo' => 'Venta registrada',
                        'detalle' => sprintf(
                            '%s — %s — Pago: %s — Total $%s — Estado venta: %s',
                            $v->numero_factura,
                            $cli,
                            $tp,
                            number_format((float) $v->total_final, 2, ',', '.'),
                            $v->estado ?? '—'
                        ),
                        'usuario' => $v->usuario?->name,
                        'meta' => ['venta_id' => $v->id, 'caja_id' => $v->caja_id],
                    ]);
                }

                if ($v->updated_at && $v->created_at) {
                    $upd = Carbon::parse($v->updated_at);
                    $crt = Carbon::parse($v->created_at);
                    if ($upd->gt($crt->copy()->addSeconds(2)) && $inRange($upd)) {
                        $tp = self::TIPO_PAGO_LABELS[$v->tipo_pago] ?? $v->tipo_pago;
                        $events->push([
                            'occurred_at' => $upd->toIso8601String(),
                            'categoria' => 'venta',
                            'tipo' => 'venta_actualizada',
                            'titulo' => 'Venta actualizada en sistema',
                            'detalle' => sprintf(
                                '%s — Estado: %s — Pago: %s — Facturación: %s — Total $%s (snapshot al último cambio; no hay historial campo a campo).',
                                $v->numero_factura,
                                $v->estado ?? '—',
                                $tp,
                                $v->estado_facturacion ?? 'pendiente',
                                number_format((float) $v->total_final, 2, ',', '.')
                            ),
                            'usuario' => $v->usuario?->name,
                            'meta' => ['venta_id' => $v->id, 'caja_id' => $v->caja_id],
                        ]);
                    }
                }

                if ($v->estado_facturacion === 'facturada' && $v->facturada_at) {
                    $dt = Carbon::parse($v->facturada_at);
                    if ($inRange($dt)) {
                        $events->push([
                            'occurred_at' => $dt->toIso8601String(),
                            'categoria' => 'venta',
                            'tipo' => 'facturacion_afip',
                            'titulo' => 'Facturación electrónica (AFIP/ARCA)',
                            'detalle' => sprintf(
                                '%s — Comprobante %s — CAE %s',
                                $v->numero_factura,
                                trim(($v->comprobante_tipo ?? '').' '.$v->comprobante_numero),
                                $v->cae ?: '—'
                            ),
                            'usuario' => $v->usuario?->name,
                            'meta' => ['venta_id' => $v->id],
                        ]);
                    }
                }
            }
        }

        if ($categoria === 'todas' || $categoria === 'stock') {
            $stocks = MovimientoStock::with(['producto', 'usuario'])->orderByDesc('created_at')->take(400)->get();
            foreach ($stocks as $s) {
                $dt = Carbon::parse($s->created_at);
                if (! $inRange($dt)) {
                    continue;
                }
                $nom = $s->producto?->nombre ?? ('Producto #'.$s->producto_id);
                $events->push([
                    'occurred_at' => $dt->toIso8601String(),
                    'categoria' => 'stock',
                    'tipo' => $s->tipo,
                    'titulo' => 'Movimiento de stock',
                    'detalle' => sprintf(
                        '%s — %s — Cant. %s (antes %s → ahora %s)%s',
                        $nom,
                        $s->tipo,
                        $s->cantidad,
                        $s->cantidad_anterior,
                        $s->cantidad_actual,
                        $s->motivo ? ' — '.$s->motivo : ''
                    ),
                    'usuario' => $s->usuario?->name,
                    'meta' => ['producto_id' => $s->producto_id],
                ]);
            }
        }

        if ($categoria === 'todas' || $categoria === 'cuenta_corriente') {
            $ccMovs = MovimientoCuentaCorriente::with(['cuentaCorriente.cliente', 'venta'])
                ->orderByDesc('created_at')
                ->take(400)
                ->get();
            foreach ($ccMovs as $m) {
                $dt = Carbon::parse($m->created_at);
                if (! $inRange($dt)) {
                    continue;
                }
                $cliente = $m->cuentaCorriente?->cliente
                    ? trim($m->cuentaCorriente->cliente->nombre.' '.$m->cuentaCorriente->cliente->apellido)
                    : 'Cuenta #'.$m->cuenta_corriente_id;
                $events->push([
                    'occurred_at' => $dt->toIso8601String(),
                    'categoria' => 'cuenta_corriente',
                    'tipo' => $m->tipo,
                    'titulo' => $m->tipo === 'debe' ? 'Cargo en cuenta corriente' : 'Crédito en cuenta corriente',
                    'detalle' => sprintf(
                        '%s — $%s — %s%s',
                        $cliente,
                        number_format((float) $m->monto, 2, ',', '.'),
                        $m->concepto,
                        $m->venta_id ? ' (venta #'.$m->venta_id.')' : ''
                    ),
                    'usuario' => null,
                    'meta' => [
                        'cuenta_corriente_id' => $m->cuenta_corriente_id,
                        'venta_id' => $m->venta_id,
                    ],
                ]);
            }
        }

        if ($categoria === 'todas' || $categoria === 'cheque') {
            $cheques = Cheque::with(['cliente', 'proveedor', 'usuario'])->orderByDesc('id')->take(300)->get();
            foreach ($cheques as $ch) {
                $dt = Carbon::parse($ch->created_at);
                if (! $inRange($dt)) {
                    continue;
                }
                $tercero = $ch->cliente
                    ? trim($ch->cliente->nombre.' '.$ch->cliente->apellido)
                    : ($ch->proveedor?->nombre ?? '—');
                $events->push([
                    'occurred_at' => $dt->toIso8601String(),
                    'categoria' => 'cheque',
                    'tipo' => 'cheque_registrado',
                    'titulo' => 'Cheque registrado',
                    'detalle' => sprintf(
                        '%s %s — $%s — Estado: %s — %s',
                        $ch->banco ?? 'Banco',
                        $ch->numero_cheque ?? 's/n',
                        number_format((float) $ch->monto, 2, ',', '.'),
                        $ch->estado ?? '—',
                        $tercero
                    ),
                    'usuario' => $ch->usuario?->name,
                    'meta' => ['cheque_id' => $ch->id],
                ]);
            }
        }

        /** @var Collection<int, array<string, mixed>> $sorted */
        $sorted = $events->sortByDesc(fn (array $e) => $e['occurred_at'])->values();

        $total = $sorted->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min($page, $lastPage);
        $slice = $sorted->forPage($page, $perPage)->values();

        $from = $total > 0 ? (($page - 1) * $perPage) + 1 : 0;
        $to = $total > 0 ? min($page * $perPage, $total) : 0;

        return response()->json([
            'data' => $slice,
            'current_page' => $page,
            'last_page' => $lastPage,
            'per_page' => $perPage,
            'total' => $total,
            'from' => $from,
            'to' => $to,
        ]);
    }
}
