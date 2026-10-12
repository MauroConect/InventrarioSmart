<?php

namespace Tests\Feature;

use App\Models\Caja;
use App\Models\Categoria;
use App\Models\Concerns\BelongsToTenant;
use App\Models\ItemVenta;
use App\Models\MovimientoCaja;
use App\Models\MovimientoStock;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Tenant;
use App\Models\Venta;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OperationalTenantIsolationTest extends TestCase
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

    protected function tearDown(): void
    {
        app(TenantContext::class)->clear();
        $this->dropTestSchema();

        parent::tearDown();
    }

    public function test_operational_models_are_tenant_scoped(): void
    {
        $models = [
            Categoria::class,
            Producto::class,
            Proveedor::class,
            MovimientoStock::class,
            Venta::class,
            ItemVenta::class,
            Caja::class,
            MovimientoCaja::class,
        ];

        foreach ($models as $model) {
            $this->assertContains(
                BelongsToTenant::class,
                class_uses_recursive($model),
                "{$model} debe usar BelongsToTenant."
            );
        }
    }

    public function test_migration_backfills_legacy_rows_and_memberships(): void
    {
        $this->createLegacySchema();

        $userId = DB::table('users')->insertGetId([
            'name' => 'Administrador legacy',
            'email' => 'legacy@example.test',
            'password' => 'secret',
            'role' => 'admin',
        ]);

        DB::table('categorias')->insert(['nombre' => 'General']);
        DB::table('proveedores')->insert(['nombre' => 'Proveedor legacy']);
        DB::table('productos')->insert(['codigo' => 'SKU-LEGACY']);
        DB::table('cajas')->insert(['created_at' => now()]);
        DB::table('movimientos_caja')->insert(['created_at' => now()]);
        DB::table('ventas')->insert([
            'numero_factura' => 'FACTURA-LEGACY',
            'fecha' => now(),
        ]);
        DB::table('items_venta')->insert(['created_at' => now()]);
        DB::table('movimientos_stock')->insert(['created_at' => now()]);

        $this->operationalMigration()->up();

        $tenantId = DB::table('tenants')
            ->where('slug', 'legacy-default')
            ->value('id');

        $this->assertNotNull($tenantId);

        foreach (self::OPERATIONAL_TABLES as $tableName) {
            $this->assertSame(
                1,
                DB::table($tableName)->where('tenant_id', $tenantId)->count(),
                "El backfill de {$tableName} no quedó asociado al tenant legacy."
            );
        }

        $this->assertDatabaseHas('tenant_user', [
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'role' => 'admin',
            'is_default' => true,
            'status' => 'active',
        ]);
    }

    public function test_scope_and_local_uniques_isolate_tenants(): void
    {
        $this->createLegacySchema();
        $this->operationalMigration()->up();

        $this->assertDatabaseMissing('tenants', ['slug' => 'legacy-default']);

        $tenantA = Tenant::query()->create([
            'slug' => 'tenant-a',
            'name' => 'Tenant A',
        ]);
        $tenantB = Tenant::query()->create([
            'slug' => 'tenant-b',
            'name' => 'Tenant B',
        ]);

        $context = app(TenantContext::class);
        $context->run($tenantA, fn () => Categoria::query()->create(['nombre' => 'A']));
        $context->run($tenantB, fn () => Categoria::query()->create(['nombre' => 'B']));

        $this->assertSame(
            ['A'],
            $context->run($tenantA, fn () => Categoria::query()->pluck('nombre')->all())
        );
        $this->assertSame(
            ['B'],
            $context->run($tenantB, fn () => Categoria::query()->pluck('nombre')->all())
        );
        $this->assertSame(0, Categoria::query()->count());

        DB::table('productos')->insert([
            ['tenant_id' => $tenantA->id, 'codigo' => 'SKU-COMPARTIDO'],
            ['tenant_id' => $tenantB->id, 'codigo' => 'SKU-COMPARTIDO'],
        ]);
        DB::table('ventas')->insert([
            [
                'tenant_id' => $tenantA->id,
                'numero_factura' => 'FACTURA-COMPARTIDA',
                'fecha' => now(),
            ],
            [
                'tenant_id' => $tenantB->id,
                'numero_factura' => 'FACTURA-COMPARTIDA',
                'fecha' => now(),
            ],
        ]);

        $this->assertDuplicateIsRejected('productos', [
            'tenant_id' => $tenantA->id,
            'codigo' => 'SKU-COMPARTIDO',
        ]);
        $this->assertDuplicateIsRejected('ventas', [
            'tenant_id' => $tenantA->id,
            'numero_factura' => 'FACTURA-COMPARTIDA',
            'fecha' => now(),
        ]);
    }

    public function test_empty_schema_can_be_migrated_and_reversed(): void
    {
        $this->createLegacySchema();
        $migration = $this->operationalMigration();

        $migration->up();
        $migration->down();

        foreach (self::OPERATIONAL_TABLES as $tableName) {
            $this->assertFalse(Schema::hasColumn($tableName, 'tenant_id'));
        }

        $this->assertDatabaseMissing('tenants', ['slug' => 'legacy-default']);
    }

    private function assertDuplicateIsRejected(string $tableName, array $attributes): void
    {
        try {
            DB::table($tableName)->insert($attributes);
            $this->fail("La unicidad local de {$tableName} no rechazó el duplicado.");
        } catch (QueryException) {
            $this->assertTrue(true);
        }
    }

    private function operationalMigration(): object
    {
        return require database_path(
            'migrations/2026_08_23_020001_add_tenant_isolation_to_operational_tables.php'
        );
    }

    private function createLegacySchema(): void
    {
        $this->dropTestSchema();

        Schema::create('tenants', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('rubro_profile')->default('base');
            $table->json('features')->nullable();
            $table->string('status')->default('active');
            $table->string('timezone')->default('UTC');
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->default('vendedor');
            $table->timestamps();
        });

        Schema::create('tenant_user', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default('vendedor');
            $table->boolean('is_default')->default(false);
            $table->string('status')->default('active');
            $table->timestamps();
            $table->unique(['tenant_id', 'user_id']);
        });

        Schema::create('categorias', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre')->nullable();
            $table->timestamps();
        });

        Schema::create('proveedores', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre')->nullable();
            $table->timestamps();
        });

        Schema::create('productos', function (Blueprint $table): void {
            $table->id();
            $table->string('codigo')->unique();
            $table->timestamps();
        });

        Schema::create('cajas', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
        });

        Schema::create('movimientos_caja', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('caja_id')->nullable();
            $table->timestamps();
        });

        Schema::create('ventas', function (Blueprint $table): void {
            $table->id();
            $table->string('numero_factura')->unique();
            $table->dateTime('fecha')->nullable();
            $table->timestamps();
        });

        Schema::create('items_venta', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('venta_id')->nullable();
            $table->timestamps();
        });

        Schema::create('movimientos_stock', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('producto_id')->nullable();
            $table->timestamps();
        });
    }

    private function dropTestSchema(): void
    {
        Schema::disableForeignKeyConstraints();

        foreach (array_reverse(self::OPERATIONAL_TABLES) as $tableName) {
            Schema::dropIfExists($tableName);
        }

        Schema::dropIfExists('tenant_user');
        Schema::dropIfExists('users');
        Schema::dropIfExists('tenants');

        Schema::enableForeignKeyConstraints();
    }
}
