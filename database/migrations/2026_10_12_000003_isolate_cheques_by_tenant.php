<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $parents = [];
        foreach (['cliente_id' => 'clientes', 'proveedor_id' => 'proveedores', 'caja_id' => 'cajas'] as $column => $table) {
            $parents[$column] = DB::table($table)->pluck('tenant_id', 'id')->all();
        }
        $tenants = DB::table('tenants')->pluck('id')->all();
        $ownership = [];
        foreach (DB::table('cheques')->orderBy('id')->cursor() as $cheque) {
            $owners = [];
            foreach ($parents as $column => $map) {
                if ($cheque->{$column} !== null) {
                    $owner = $map[$cheque->{$column}] ?? null;
                    if ($owner === null || ! in_array($owner, $tenants)) {
                        throw new RuntimeException("Cheque #{$cheque->id} tiene una referencia {$column} sin comercio válido.");
                    }
                    $owners[(int) $owner] = true;
                }
            }
            if (count($owners) !== 1) {
                throw new RuntimeException("Cheque #{$cheque->id} tiene pertenencia ambigua o sin referencias. Reconciliar antes de migrar.");
            }
            $ownership[(int) $cheque->id] = (int) array_key_first($owners);
        }
        $before = $this->totals();
        Schema::table('cheques', function (Blueprint $table): void {
            $table->unsignedBigInteger('tenant_id')->nullable();
        });
        DB::transaction(function () use ($ownership, $before): void {
            foreach ($ownership as $id => $tenantId) {
                DB::table('cheques')->where('id', $id)->update(['tenant_id' => $tenantId]);
            }
            if ($this->totals() !== $before) {
                throw new RuntimeException('La reconciliación de cheques detectó cambios de importes o estados.');
            }
        });
        Schema::table('cheques', function (Blueprint $table): void {
            $table->unsignedBigInteger('tenant_id')->nullable(false)->change();
            $table->foreign('tenant_id')->references('id')->on('tenants')->restrictOnDelete();
            $table->dropUnique('cheques_numero_cheque_unique');
            $table->unique(['tenant_id', 'numero_cheque'], 'cheques_tenant_numero_unique');
            $table->index(['tenant_id', 'estado', 'fecha_vencimiento'], 'cheques_tenant_vencimiento_index');
        });
    }

    public function down(): void
    {
        if (DB::table('cheques')->select('numero_cheque')->groupBy('numero_cheque')->havingRaw('COUNT(*) > 1')->exists()) {
            throw new RuntimeException('No se puede revertir: números de cheque repetidos entre comercios. Reconciliar antes del rollback.');
        }
        Schema::table('cheques', function (Blueprint $table): void {
            $table->dropForeign(['tenant_id']);
            $table->dropUnique('cheques_tenant_numero_unique');
            $table->dropIndex('cheques_tenant_vencimiento_index');
            $table->unique('numero_cheque', 'cheques_numero_cheque_unique');
            $table->dropColumn('tenant_id');
        });
    }

    private function totals(): array
    {
        return DB::table('cheques')->select('estado')->selectRaw('COUNT(*) as records, COALESCE(SUM(monto), 0) as monto')
            ->groupBy('estado')->orderBy('estado')->get()->map(fn ($row) => (array) $row)->all();
    }
};
