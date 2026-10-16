<?php

namespace Tests\Feature;

use App\Models\ConfiguracionComercio;
use App\Models\Producto;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PersistentUploadStore;
use App\Support\Tenancy\TenantContext;
use Database\Seeders\DedicatedTenantSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DedicatedPostgresDeploymentTest extends TestCase
{
    private Tenant $tenant;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        if (DB::getDriverName() !== 'pgsql' || config('database.connections.pgsql.search_path') !== 'pesquero_verification') {
            $this->markTestSkipped('Requiere PostgreSQL y el esquema aislado pesquero_verification, con las migraciones aplicadas.');
        }
        $this->assertSame('pesquero_verification', DB::selectOne('SELECT current_schema() AS schema')->schema);
        DB::beginTransaction();
        config([
            'tenancy.legacy_mode' => false,
            'deployment.tenant_slug' => 'pesquero-guamini',
            'deployment.tenant_name' => 'Pesquero Guaminí',
            'deployment.admin_email' => 'admin@example.test',
            'deployment.admin_password' => 'Pruebas-Pesquero-2026!',
            'deployment.upload_storage' => 'database',
        ]);
        $this->seed(DedicatedTenantSeeder::class);
        $this->tenant = Tenant::where('slug', 'pesquero-guamini')->firstOrFail();
        $this->admin = User::where('email', 'admin@example.test')->firstOrFail();
    }

    protected function tearDown(): void
    {
        app(TenantContext::class)->clear();
        while (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        parent::tearDown();
    }

    public function test_restarts_preserve_password_branding_and_module_settings(): void
    {
        $this->admin->update(['password' => 'Otra-Clave-De-Prueba-2026!']);
        app(TenantContext::class)->run($this->tenant, fn () => ConfiguracionComercio::query()->firstOrFail()->update(['nombre_comercio' => 'Nombre personalizado']));
        DB::table('tenant_modules')->where('tenant_id', $this->tenant->id)->where('module_key', 'platform.support')->update(['enabled' => false]);
        $this->seed(DedicatedTenantSeeder::class);
        $this->assertSame(1, Tenant::count());
        $this->assertSame(1, User::count());
        $this->assertTrue(Hash::check('Otra-Clave-De-Prueba-2026!', $this->admin->fresh()->password));
        $this->assertDatabaseHas('configuracion_comercio', ['tenant_id' => $this->tenant->id, 'nombre_comercio' => 'Nombre personalizado']);
        $this->assertDatabaseHas('tenant_modules', ['tenant_id' => $this->tenant->id, 'module_key' => 'platform.support', 'enabled' => false]);
        $this->assertFalse($this->admin->isPlatformSuperadmin());
    }

    public function test_dedicated_login_enters_only_the_client_and_disables_public_signup(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/login')->assertOk()->assertSee('Ingresar a Pesquero Guaminí')->assertDontSee('Registrate');
        $this->get('/register')->assertNotFound();
        $this->post('/register', [])->assertNotFound();
        $this->post('/login', ['email' => 'admin@example.test', 'password' => 'Pruebas-Pesquero-2026!'])->assertRedirect('/app');
        $this->get('/dashboard')->assertOk();
        $this->get('/platform')->assertForbidden();
        $this->getJson('/api/tenant-context')->assertOk()->assertJsonPath('tenant.slug', 'pesquero-guamini');
        $this->getJson('/api/dashboard/productos-mas-vendidos')->assertOk();
        $this->getJson('/api/dashboard/ventas-por-dia')->assertOk();
    }

    public function test_postgres_sale_transfer_stock_idempotency_and_cash_close(): void
    {
        $this->actingAs($this->admin, 'web')->withSession(['tenant_id' => $this->tenant->id]);
        $category = $this->postJson('/api/categorias', ['nombre' => 'Categoría ficticia'])->assertCreated()->json('id');
        $product = $this->postJson('/api/productos', [
            'codigo' => 'PRUEBA-001', 'nombre' => 'Producto ficticio', 'categoria_id' => $category,
            'precio_compra' => 40, 'precio_venta' => 100, 'stock_actual' => 5, 'stock_minimo' => 1,
        ])->assertCreated()->json('id');
        $cash = $this->postJson('/api/cajas', ['nombre' => 'Caja ficticia', 'monto_apertura' => 100])->assertCreated()->json('id');
        $payload = ['caja_id' => $cash, 'items' => [['producto_id' => $product, 'cantidad' => 2]], 'tipo_pago' => 'transferencia'];
        $sale = $this->withHeader('Idempotency-Key', 'postgres-transfer-verification')->postJson('/api/ventas', $payload)->assertCreated()->json('id');
        $this->postJson('/api/ventas', $payload)->assertCreated()->assertHeader('Idempotency-Replayed', 'true')->assertJsonPath('id', $sale);
        $this->flushHeaders();
        app(TenantContext::class)->run($this->tenant, fn () => $this->assertSame('3.000', Producto::findOrFail($product)->stock_actual));
        $this->getJson('/api/cajas/'.$cash.'/resumen-cierre')->assertOk()->assertJsonPath('resumen.monto_esperado', 100);
        $this->postJson('/api/cajas/'.$cash.'/cerrar', ['monto_real' => 100])->assertOk()->assertJsonPath('diferencia', '0.00');
        $this->withHeader('X-Tenant-Id', $this->tenant->uuid)->getJson('/api/tenant-context')->assertOk()->assertJsonPath('tenant.slug', 'pesquero-guamini');
        $this->withHeader('X-Tenant-Id', 'otro-comercio')->getJson('/api/productos')->assertForbidden();
    }

    public function test_uploads_survive_local_disk_loss_and_private_files_are_not_public(): void
    {
        $store = app(PersistentUploadStore::class);
        $file = UploadedFile::fake()->image('logo.png');
        $publicPath = $store->store($file, 'tenants/'.$this->tenant->uuid.'/branding', 'public');
        $privatePath = $store->store($file, 'tenants/'.$this->tenant->uuid.'/sales/1', 'private');
        Storage::fake('public');
        Storage::fake('private');
        $this->assertTrue($store->exists($publicPath, 'public'));
        $this->assertTrue($store->exists($privatePath, 'private'));
        $this->get('/storage/'.$publicPath)->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->get('/storage/'.$privatePath)->assertNotFound();
        $this->assertSame($file->getContent(), $this->streamContent($store->download($privatePath, 'private')));
        $store->delete($privatePath, 'private');
        $this->assertFalse($store->exists($privatePath, 'private'));
    }

    private function streamContent(\Symfony\Component\HttpFoundation\StreamedResponse $response): string
    {
        ob_start();
        $response->sendContent();
        return ob_get_clean();
    }
}
