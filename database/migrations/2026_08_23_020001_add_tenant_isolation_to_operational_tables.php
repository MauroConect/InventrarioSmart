<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    private const OPERATIONAL_TABLES = [
        'categorias',
        'proveedores',
        'productos',
        'cajas',
        'movimientos_caja',
        'ventas',
        'items_venta',
        'movimientos_stock',
    ];

    private const LEGACY_TENANT_SLUG = 'legacy-default';

    private const LEGACY_TENANT_MARKER = 'operational-isolation-2026-08-23';

    public function up(): void
    {
        foreach (self::OPERATIONAL_TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                $table->unsignedBigInteger('tenant_id')->nullable();
                $table->index('tenant_id', $tableName.'_tenant_id_index');
            });
        }

        if ($this->hasLegacyOperationalData()) {
            $tenantId = $this->resolveLegacyTenantId();

            foreach (self::OPERATIONAL_TABLES as $tableName) {
                $this->backfillTable($tableName, $tenantId);
            }

            $this->createLegacyMemberships($tenantId);
        }

        $this->assertBackfillComplete();

        foreach (self::OPERATIONAL_TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->unsignedBigInteger('tenant_id')->nullable(false)->change();
            });

            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                $table->foreign('tenant_id', $tableName.'_tenant_id_foreign')
                    ->references('id')
                    ->on('tenants')
                    ->onDelete('restrict');
            });
        }

        Schema::table('productos', function (Blueprint $table): void {
            $table->dropUnique('productos_codigo_unique');
            $table->unique(
                ['tenant_id', 'codigo'],
                'productos_tenant_codigo_unique'
            );
        });

        Schema::table('ventas', function (Blueprint $table): void {
            $table->dropUnique('ventas_numero_factura_unique');
            $table->unique(
                ['tenant_id', 'numero_factura'],
                'ventas_tenant_numero_factura_unique'
            );
            $table->index(['tenant_id', 'fecha'], 'ventas_tenant_fecha_index');
        });

        Schema::table('items_venta', function (Blueprint $table): void {
            $table->index(['tenant_id', 'venta_id'], 'items_venta_tenant_venta_index');
        });

        Schema::table('movimientos_stock', function (Blueprint $table): void {
            $table->index(
                ['tenant_id', 'producto_id'],
                'mov_stock_tenant_producto_index'
            );
        });

        Schema::table('movimientos_caja', function (Blueprint $table): void {
            $table->index(['tenant_id', 'caja_id'], 'mov_caja_tenant_caja_index');
        });
    }

    public function down(): void
    {
        $this->assertGlobalUniquesCanBeRestored();

        Schema::table('movimientos_caja', function (Blueprint $table): void {
            $table->dropIndex('mov_caja_tenant_caja_index');
        });

        Schema::table('movimientos_stock', function (Blueprint $table): void {
            $table->dropIndex('mov_stock_tenant_producto_index');
        });

        Schema::table('items_venta', function (Blueprint $table): void {
            $table->dropIndex('items_venta_tenant_venta_index');
        });

        Schema::table('ventas', function (Blueprint $table): void {
            $table->dropIndex('ventas_tenant_fecha_index');
            $table->dropUnique('ventas_tenant_numero_factura_unique');
            $table->unique('numero_factura', 'ventas_numero_factura_unique');
        });

        Schema::table('productos', function (Blueprint $table): void {
            $table->dropUnique('productos_tenant_codigo_unique');
            $table->unique('codigo', 'productos_codigo_unique');
        });

        foreach (array_reverse(self::OPERATIONAL_TABLES) as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                $table->dropForeign($tableName.'_tenant_id_foreign');
                $table->dropIndex($tableName.'_tenant_id_index');
                $table->dropColumn('tenant_id');
            });
        }

        $this->deleteMigrationCreatedLegacyTenant();
    }

    private function hasLegacyOperationalData(): bool
    {
        foreach (self::OPERATIONAL_TABLES as $tableName) {
            if (DB::table($tableName)->exists()) {
                return true;
            }
        }

        return false;
    }

    private function resolveLegacyTenantId(): int
    {
        $existingTenantId = DB::table('tenants')
            ->where('slug', self::LEGACY_TENANT_SLUG)
            ->value('id');

        if ($existingTenantId !== null) {
            return (int) $existingTenantId;
        }

        $now = now();

        return (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'slug' => self::LEGACY_TENANT_SLUG,
            'name' => 'Comercio legacy',
            'rubro_profile' => 'base',
            'features' => json_encode([
                'created_by_migration' => self::LEGACY_TENANT_MARKER,
            ], JSON_THROW_ON_ERROR),
            'status' => 'active',
            'timezone' => config('app.timezone', 'UTC'),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function backfillTable(string $tableName, int $tenantId): void
    {
        DB::table($tableName)
            ->select('id')
            ->whereNull('tenant_id')
            ->orderBy('id')
            ->chunkById(1000, function ($rows) use ($tableName, $tenantId): void {
                DB::table($tableName)
                    ->whereIn('id', $rows->pluck('id'))
                    ->whereNull('tenant_id')
                    ->update(['tenant_id' => $tenantId]);
            });
    }

    private function createLegacyMemberships(int $tenantId): void
    {
        DB::table('users')
            ->select(['id', 'role'])
            ->orderBy('id')
            ->chunkById(1000, function ($users) use ($tenantId): void {
                $now = now();
                $memberships = $users->map(fn ($user): array => [
                    'tenant_id' => $tenantId,
                    'user_id' => $user->id,
                    'role' => filled($user->role) ? $user->role : 'vendedor',
                    'is_default' => true,
                    'status' => 'active',
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all();

                if ($memberships !== []) {
                    DB::table('tenant_user')->insertOrIgnore($memberships);
                }
            });
    }

    private function assertBackfillComplete(): void
    {
        foreach (self::OPERATIONAL_TABLES as $tableName) {
            if (DB::table($tableName)->whereNull('tenant_id')->exists()) {
                throw new \RuntimeException(
                    "No se pudo completar el backfill de tenant_id en {$tableName}."
                );
            }
        }
    }

    private function assertGlobalUniquesCanBeRestored(): void
    {
        if ($this->hasDuplicates('productos', 'codigo')) {
            throw new \RuntimeException(
                'No se puede revertir: existen códigos de producto repetidos entre tenants.'
            );
        }

        if ($this->hasDuplicates('ventas', 'numero_factura')) {
            throw new \RuntimeException(
                'No se puede revertir: existen números de factura repetidos entre tenants.'
            );
        }
    }

    private function hasDuplicates(string $tableName, string $column): bool
    {
        return DB::table($tableName)
            ->select($column)
            ->groupBy($column)
            ->havingRaw('COUNT(*) > 1')
            ->exists();
    }

    private function deleteMigrationCreatedLegacyTenant(): void
    {
        $tenant = DB::table('tenants')
            ->select(['id', 'features'])
            ->where('slug', self::LEGACY_TENANT_SLUG)
            ->first();

        if (! $tenant) {
            return;
        }

        $features = json_decode((string) $tenant->features, true);

        if (($features['created_by_migration'] ?? null) === self::LEGACY_TENANT_MARKER) {
            DB::table('tenants')->where('id', $tenant->id)->delete();
        }
    }
};
