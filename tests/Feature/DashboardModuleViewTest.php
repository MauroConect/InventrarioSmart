<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Modules\TenantFeatureService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class DashboardModuleViewTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite', ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
        DB::purge();
        View::share('errors', new ViewErrorBag);
    }

    public function test_disabled_finance_modules_do_not_render_their_widgets(): void
    {
        $html = $this->renderDashboard(false, 'admin');
        $this->assertStringNotContainsString('>Cheques Próximos', $html);
        $this->assertStringNotContainsString('Cargando cheques...', $html);
        $this->assertStringNotContainsString('Deudas Pendientes', $html);
        $this->assertStringContainsString('chequesEnabled: false', $html);
        $this->assertStringContainsString('Ventas del Mes', $html);
    }

    public function test_enabled_modules_render_their_widgets_for_the_tenant_admin(): void
    {
        $html = $this->renderDashboard(true, 'admin');
        $this->assertStringContainsString('Cheques Próximos a Vencer (30 días)', $html);
        $this->assertStringContainsString('Deudas Pendientes', $html);
        $this->assertStringContainsString('chequesEnabled: true', $html);
        $this->assertStringContainsString('Reintentar', $html);
    }

    public function test_finance_widgets_are_hidden_without_permission_to_the_admin_api(): void
    {
        $html = $this->renderDashboard(true, 'vendedor');
        $this->assertStringNotContainsString('>Cheques Próximos', $html);
        $this->assertStringNotContainsString('Deudas Pendientes', $html);
        $this->assertStringContainsString('chequesEnabled: false', $html);
    }

    private function renderDashboard(bool $enabled, string $role): string
    {
        $user = (new User)->forceFill(['id' => 1, 'name' => 'Operador ficticio', 'role' => $role]);
        $this->actingAs($user, 'web');
        $this->mock(TenantFeatureService::class, function ($mock) use ($enabled): void {
            $mock->shouldReceive('enabled')->andReturnUsing(fn ($key) => $enabled && in_array($key, ['finance.cheques', 'finance.credit'], true));
            $mock->shouldReceive('frontendFlags')->andReturn(['cheques' => $enabled, 'credit' => $enabled]);
        });

        return view('dashboard')->render();
    }
}
