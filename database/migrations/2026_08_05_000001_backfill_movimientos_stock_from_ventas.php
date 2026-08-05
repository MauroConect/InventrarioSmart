<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('items_venta') || ! Schema::hasTable('movimientos_stock')) {
            return;
        }

        $fallbackUserId = DB::table('users')->orderBy('id')->value('id');
        if (! $fallbackUserId) {
            return;
        }

        $hasVentaUsuario = Schema::hasColumn('ventas', 'usuario_id');

        $items = DB::table('items_venta as iv')
            ->join('ventas as v', 'v.id', '=', 'iv.venta_id')
            ->leftJoin('cajas as c', 'c.id', '=', 'v.caja_id')
            ->where('v.estado', '!=', 'cancelada')
            ->select([
                'iv.id as item_id',
                'iv.producto_id',
                'iv.cantidad',
                'iv.created_at',
                'iv.updated_at',
                'v.numero_factura',
                'c.usuario_id as caja_usuario_id',
            ])
            ->when($hasVentaUsuario, function ($query) {
                $query->addSelect('v.usuario_id as venta_usuario_id');
            })
            ->orderBy('iv.id')
            ->get();

        foreach ($items as $item) {
            $motivo = 'Venta '.$item->numero_factura;

            $exists = DB::table('movimientos_stock')
                ->where('producto_id', $item->producto_id)
                ->where('tipo', 'salida')
                ->where('motivo', $motivo)
                ->where('cantidad', (int) $item->cantidad)
                ->exists();

            if ($exists) {
                continue;
            }

            $usuarioId = $fallbackUserId;
            if ($hasVentaUsuario && ! empty($item->venta_usuario_id)) {
                $usuarioId = $item->venta_usuario_id;
            } elseif (! empty($item->caja_usuario_id)) {
                $usuarioId = $item->caja_usuario_id;
            }

            $cantidad = (int) $item->cantidad;

            DB::table('movimientos_stock')->insert([
                'producto_id' => $item->producto_id,
                'tipo' => 'salida',
                'cantidad' => $cantidad,
                'cantidad_anterior' => $cantidad,
                'cantidad_actual' => 0,
                'motivo' => $motivo,
                'usuario_id' => $usuarioId,
                'observaciones' => 'Salida por venta (histórico)',
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at ?? $item->created_at,
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('movimientos_stock')) {
            return;
        }

        DB::table('movimientos_stock')
            ->where('observaciones', 'Salida por venta (histórico)')
            ->delete();
    }
};
