<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLES = [
        'cuentas_corrientes' => ['cliente_id', ['saldo', 'limite_credito']],
        'deudas_clientes' => ['cliente_id', ['monto_total', 'monto_pagado', 'monto_pendiente', 'cuotas_originales', 'cuotas_pagadas', 'cuotas_restantes']],
        'movimientos_cuenta_corriente' => ['cuenta_corriente_id', ['monto']],
    ];

    public function up(): void
    {
        // Reject missing or conflicting owners before any MySQL DDL is executed.
        $ownership = $this->resolveOwnership();
        $before = $this->totals();
        foreach (self::TABLES as $table => [$parent]) {
            Schema::table($table, function (Blueprint $schema): void {
                $schema->unsignedBigInteger('tenant_id')->nullable();
            });
        }

        DB::transaction(function () use ($ownership, $before): void {
            foreach ($ownership as $table => $owners) {
                foreach (array_chunk($owners, 500, true) as $chunk) {
                    foreach ($chunk as $id => $tenantId) {
                        DB::table($table)->where('id', $id)->update(['tenant_id' => $tenantId]);
                    }
                }
            }
            if ($this->totals() !== $before) {
                throw new RuntimeException('La reconciliación financiera detectó cambios de importes o cuotas.');
            }
        });

        foreach (self::TABLES as $table => [$parent]) {
            Schema::table($table, function (Blueprint $schema) use ($parent): void {
                $schema->unsignedBigInteger('tenant_id')->nullable(false)->change();
                $schema->foreign('tenant_id')->references('id')->on('tenants')->restrictOnDelete();
                $schema->index(['tenant_id', $parent]);
            });
        }
    }

    public function down(): void
    {
        foreach (array_reverse(self::TABLES, true) as $table => [$parent]) {
            Schema::table($table, function (Blueprint $schema) use ($parent): void {
                $schema->dropForeign(['tenant_id']);
                $schema->dropIndex(['tenant_id', $parent]);
                $schema->dropColumn('tenant_id');
            });
        }
    }

    private function resolveOwnership(): array
    {
        $tenants = DB::table('tenants')->pluck('id')->all();
        $clients = DB::table('clientes')->pluck('tenant_id', 'id')->all();
        $sales = DB::table('ventas')->pluck('tenant_id', 'id')->all();
        $ownership = [];

        foreach (self::TABLES as $table => [$parent]) {
            $parents = $parent === 'cliente_id' ? $clients : $ownership['cuentas_corrientes'];
            $ownership[$table] = [];
            foreach (DB::table($table)->orderBy('id')->cursor() as $row) {
                $tenantId = $parents[$row->{$parent}] ?? null;
                if ($tenantId === null || ! in_array($tenantId, $tenants)) {
                    throw new RuntimeException("No se puede determinar el comercio de {$table} #{$row->id}. Reconciliar su relación {$parent}.");
                }
                if (isset($row->venta_id) && (! isset($sales[$row->venta_id]) || (int) $sales[$row->venta_id] !== (int) $tenantId)) {
                    throw new RuntimeException("{$table} #{$row->id} tiene una venta inexistente o de otro comercio. Reconciliar antes de migrar.");
                }
                $ownership[$table][(int) $row->id] = (int) $tenantId;
            }
        }

        return $ownership;
    }

    private function totals(): array
    {
        $totals = [];
        foreach (self::TABLES as $table => [, $amounts]) {
            $query = DB::table($table)->selectRaw('COUNT(*) as records');
            foreach ($amounts as $column) {
                $query->selectRaw("COALESCE(SUM({$column}), 0) as {$column}");
            }
            $totals[$table] = (array) $query->first();
        }

        return $totals;
    }
};
