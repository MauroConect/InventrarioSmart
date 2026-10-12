<?php

use App\Models\Cliente;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Resolve ownership before DDL: a rejected backfill leaves the old schema intact.
        $ownership = $this->resolveOwnership();

        Schema::table('clientes', function (Blueprint $table): void {
            $table->unsignedBigInteger('tenant_id')->nullable();
        });

        DB::transaction(function () use ($ownership): void {
            foreach ($ownership as $clientId => $tenantId) {
                DB::table('clientes')->where('id', $clientId)->update(['tenant_id' => $tenantId]);
            }
        });

        Schema::table('clientes', function (Blueprint $table): void {
            $table->unsignedBigInteger('tenant_id')->nullable(false)->change();
        });

        Schema::table('clientes', function (Blueprint $table): void {
            $table->foreign('tenant_id')->references('id')->on('tenants')->restrictOnDelete();
            $table->dropUnique('clientes_dni_unique');
            $table->unique(['tenant_id', 'dni'], 'clientes_tenant_dni_unique');
        });
    }

    public function down(): void
    {
        if (DB::table('clientes')->select('dni')->groupBy('dni')->havingRaw('COUNT(*) > 1')->exists()) {
            throw new RuntimeException('No se puede revertir: hay DNI repetidos entre comercios. Reconciliar antes del rollback.');
        }

        Schema::table('clientes', function (Blueprint $table): void {
            $table->dropForeign(['tenant_id']);
            $table->dropUnique('clientes_tenant_dni_unique');
            $table->unique('dni', 'clientes_dni_unique');
            $table->dropColumn('tenant_id');
        });
    }

    /** @return array<int, int> */
    private function resolveOwnership(): array
    {
        $tenantIds = DB::table('tenants')->pluck('id')->map(fn ($id) => (int) $id)->all();
        $explicit = env('TENANCY_CLIENT_BACKFILL_TENANT_ID');
        if ($explicit !== null && (! ctype_digit((string) $explicit) || ! in_array((int) $explicit, $tenantIds, true))) {
            throw new RuntimeException('TENANCY_CLIENT_BACKFILL_TENANT_ID debe identificar un comercio existente.');
        }
        $fallback = $explicit !== null ? (int) $explicit : (count($tenantIds) === 1 ? $tenantIds[0] : null);
        $ownership = [];

        DB::table('clientes')->select('id')->orderBy('id')->chunkById(500, function ($clients) use (&$ownership, $fallback, $tenantIds): void {
            $ids = $clients->pluck('id');
            $references = [];
            foreach (['ventas', 'turnos'] as $table) {
                if (Schema::hasTable($table) && Schema::hasColumn($table, 'tenant_id') && Schema::hasColumn($table, 'cliente_id')) {
                    foreach (DB::table($table)->whereIn('cliente_id', $ids)->select('cliente_id', 'tenant_id')->distinct()->get() as $row) {
                        if ($row->tenant_id === null) {
                            throw new RuntimeException("Cliente {$row->cliente_id} tiene referencias sin comercio en {$table}.");
                        }
                        $references[(int) $row->cliente_id][(int) $row->tenant_id] = true;
                    }
                }
            }
            if (Schema::hasTable('custom_form_values')) {
                foreach (DB::table('custom_form_values')->where('entity_type', Cliente::class)->whereIn('entity_id', $ids)->select('entity_id', 'tenant_id')->distinct()->get() as $row) {
                    $references[(int) $row->entity_id][(int) $row->tenant_id] = true;
                }
            }
            foreach ($clients as $client) {
                $owners = array_keys($references[(int) $client->id] ?? []);
                if (count($owners) > 1) {
                    throw new RuntimeException("Cliente {$client->id} está vinculado a varios comercios. Reconciliar sus referencias antes de migrar.");
                }
                $tenantId = $owners[0] ?? $fallback;
                if ($tenantId === null || ! in_array($tenantId, $tenantIds, true)) {
                    throw new RuntimeException("No se puede determinar el comercio del cliente {$client->id}. Resolver sus referencias o definir TENANCY_CLIENT_BACKFILL_TENANT_ID explícitamente.");
                }
                $ownership[(int) $client->id] = $tenantId;
            }
        });

        return $ownership;
    }
};
