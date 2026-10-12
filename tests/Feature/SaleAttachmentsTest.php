<?php

namespace Tests\Feature;

use App\Models\{Caja, Venta, VentaAdjunto};
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\MySqlTenantTestCase;

class SaleAttachmentsTest extends MySqlTenantTestCase
{
    private function sale($tenant, $user): Venta
    {
        return app(TenantContext::class)->run($tenant, function () use ($user) {
            $cash = Caja::create(['usuario_id' => $user->id, 'fecha_apertura' => now(), 'monto_apertura' => 0]);
            return Venta::create(['caja_id' => $cash->id, 'usuario_id' => $user->id, 'numero_factura' => 'TEST-'.str()->random(8), 'fecha' => now(), 'tipo_pago' => 'efectivo', 'estado' => 'cerrada']);
        });
    }

    public function test_upload_download_and_delete_require_same_tenant_and_permission(): void
    {
        Storage::fake('private'); Storage::fake('public');
        [$a, $owner] = $this->commerce(); [$b, $other] = $this->commerce('B');
        $sale = $this->sale($a, $owner);
        $this->signIn($a, $owner);
        $response = $this->postJson('/api/ventas/'.$sale->id.'/adjuntos', ['adjuntos' => [UploadedFile::fake()->create('recibo.pdf', 1, 'application/pdf')]])->assertCreated();
        $id = $response->json('adjuntos.0.id');
        $url = $response->json('adjuntos.0.url');
        $response->assertJsonMissingPath('adjuntos.0.ruta');
        $attachment = app(TenantContext::class)->run($a, fn () => VentaAdjunto::findOrFail($id));
        $this->assertStringStartsWith('tenants/'.$a->uuid.'/sales/'.$sale->id.'/', $attachment->ruta);
        Storage::disk('private')->assertExists($attachment->ruta);
        Storage::disk('public')->assertMissing($attachment->ruta);
        $this->get($url)->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->signIn($b, $other);
        $this->getJson($url)->assertNotFound();
        $this->deleteJson($url)->assertNotFound();
        $this->postJson('/api/ventas/'.$sale->id.'/adjuntos', ['adjuntos' => [UploadedFile::fake()->create('b.pdf', 1, 'application/pdf')]])->assertNotFound();
        $this->signIn($a, $owner);
        $this->deleteJson($url)->assertNoContent();
        Storage::disk('private')->assertMissing($attachment->ruta);
        $this->getJson($url)->assertNotFound();
        $this->app['auth']->forgetGuards();
        $this->getJson($url)->assertUnauthorized();
    }

    public function test_legacy_migration_preflights_copies_and_removes_public_file(): void
    {
        Storage::fake('private'); Storage::fake('public');
        [$a, $owner] = $this->commerce();
        $sale = $this->sale($a, $owner);
        Storage::disk('public')->put('ventas_adjuntos/legacy.pdf', 'fictitious-content');
        $attachment = app(TenantContext::class)->run($a, fn () => VentaAdjunto::create(['venta_id' => $sale->id, 'ruta' => 'ventas_adjuntos/legacy.pdf', 'nombre_original' => 'legacy.pdf']));
        $this->artisan('tenant:privatize-sale-attachments')->assertExitCode(0);
        Storage::disk('public')->assertExists('ventas_adjuntos/legacy.pdf');
        $this->artisan('tenant:privatize-sale-attachments', ['--apply' => true])->assertExitCode(0);
        Storage::disk('public')->assertMissing('ventas_adjuntos/legacy.pdf');
        $path = app(TenantContext::class)->run($a, fn () => $attachment->fresh()->ruta);
        $this->assertSame('fictitious-content', Storage::disk('private')->get($path));
        $this->artisan('tenant:privatize-sale-attachments', ['--apply' => true])->assertExitCode(0);
    }

    public function test_inline_sale_upload_rejects_unsupported_mime_before_stock_changes(): void
    {
        Storage::fake('private');
        [$a, $owner] = $this->commerce();
        [$cash, $product] = app(TenantContext::class)->run($a, function () use ($owner) {
            $category = \App\Models\Categoria::create(['nombre' => 'Ficticia']);
            $product = \App\Models\Producto::create(['codigo' => 'ATT-TEST', 'nombre' => 'Producto', 'categoria_id' => $category->id, 'stock_actual' => 10, 'precio_venta' => 10]);
            $cash = Caja::create(['usuario_id' => $owner->id, 'fecha_apertura' => now(), 'monto_apertura' => 0]);
            return [$cash, $product];
        });
        $this->signIn($a, $owner);
        $this->postJson('/api/ventas', ['caja_id' => $cash->id, 'items' => [['producto_id' => $product->id, 'cantidad' => 1]], 'tipo_pago' => 'efectivo', 'adjuntos' => [UploadedFile::fake()->create('script.html', 1, 'text/html')]])->assertUnprocessable()->assertJsonValidationErrors('adjuntos.0');
        $this->assertSame('10.000', app(TenantContext::class)->run($a, fn () => $product->fresh()->stock_actual));
        $this->assertSame(0, app(TenantContext::class)->run($a, fn () => Venta::count()));
    }
}
