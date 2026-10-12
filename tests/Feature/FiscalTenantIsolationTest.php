<?php

namespace Tests\Feature;

use App\Models\{Caja, ConfiguracionFiscal, TenantModule, Venta};
use App\Services\AfipFacturacionService;
use App\Services\Fiscal\{ArcaGatewayFactory, FiscalCredentialStore, FiscalGateway, FiscalGatewayFactory};
use App\Support\Tenancy\{TenantContext, TenantRuntime};
use Illuminate\Support\Facades\{DB, Storage};
use RuntimeException;
use Tests\MySqlTenantTestCase;

class FiscalTenantIsolationTest extends MySqlTenantTestCase
{
    private function validCuit(int $suffix): string
    {
        $prefix = '20000000'.str_pad((string) $suffix, 2, '0', STR_PAD_LEFT);
        $sum = 0; foreach ([5,4,3,2,7,6,5,4,3,2] as $i => $weight) { $sum += (int) $prefix[$i] * $weight; }
        $digit = 11 - $sum % 11; if ($digit === 11) { $digit = 0; } if ($digit === 10) { $digit = 9; }
        return $prefix.$digit;
    }

    private function payload(int $suffix): array
    {
        return ['razon_social' => 'Comercio ficticio '.$suffix, 'cuit_emisor' => $this->validCuit($suffix), 'punto_venta' => $suffix, 'condicion_iva' => 'monotributo', 'ambiente' => 'homologacion', 'comprobante_tipo_default' => 'C', 'certificado_path' => 'certificado.crt', 'clave_privada_path' => 'clave.key', 'passphrase_certificado' => 'FICTITIOUS-PASSPHRASE-'.$suffix];
    }

    public function test_configuration_and_encrypted_secrets_are_owned_and_not_exposed(): void
    {
        [$a, $userA] = $this->commerce('A'); [$b, $userB] = $this->commerce('B');
        foreach ([[$a, $userA, 1], [$b, $userB, 2]] as [$tenant, $user, $suffix]) {
            TenantModule::updateOrCreate(['tenant_id' => $tenant->id, 'module_key' => 'fiscal.afip'], ['enabled' => true]);
            $this->signIn($tenant, $user);
            $this->postJson('/api/configuracion-fiscal', $this->payload($suffix))->assertOk()->assertJsonPath('data.cuit_emisor', $this->validCuit($suffix))->assertJsonMissingPath('data.passphrase_certificado');
            $this->getJson('/api/configuracion-fiscal')->assertOk()->assertJsonPath('has_passphrase', true)->assertJsonPath('cuit_emisor', $this->validCuit($suffix))->assertDontSee('FICTITIOUS-PASSPHRASE');
        }
        $this->assertNotSame($this->payload(1)['passphrase_certificado'], DB::table('configuraciones_fiscales')->where('tenant_id', $a->id)->value('passphrase_certificado'));
        $this->signIn($b, $userB);
        $this->postJson('/api/configuracion-fiscal', $this->payload(2))->assertOk();
        $this->postJson('/api/configuracion-fiscal', array_replace($this->payload(2), ['certificado_path' => '../foreign.crt']))->assertUnprocessable();
        $this->postJson('/api/configuracion-fiscal', $this->payload(1))->assertUnprocessable();
    }

    public function test_private_credentials_reject_foreign_namespaces_and_live_gateway_is_disabled(): void
    {
        Storage::fake('private');
        [$a] = $this->commerce('A'); [$b] = $this->commerce('B');
        $config = app(TenantContext::class)->run($a, fn () => ConfiguracionFiscal::create($this->payload(1)));
        app(TenantContext::class)->set($a);
        $root = app(TenantRuntime::class)->privatePath('fiscal/homologacion/credentials');
        Storage::disk('private')->put($root.'/certificado.crt', 'fake-certificate');
        $this->assertStringContainsString($a->uuid, app(FiscalCredentialStore::class)->resolve($config, 'certificado.crt'));
        foreach (['../other.crt', 'tenants/'.$b->uuid.'/certificado.crt'] as $name) {
            try { app(FiscalCredentialStore::class)->resolve($config, $name); $this->fail('Foreign secret read'); } catch (RuntimeException) { $this->assertTrue(true); }
        }
        app(TenantContext::class)->set($b);
        try { app(FiscalCredentialStore::class)->resolve($config, 'certificado.crt'); $this->fail('Foreign config accepted'); } catch (RuntimeException) { $this->assertTrue(true); }
        config(['fiscal.enabled' => false]);
        try { (new ArcaGatewayFactory)->make($config); $this->fail('Live emission allowed'); } catch (RuntimeException $e) { $this->assertStringContainsString('deshabilitada', $e->getMessage()); }
    }

    public function test_service_uses_only_active_tenant_issuer_and_rejects_foreign_sale(): void
    {
        $factory = new class implements FiscalGatewayFactory {
            public array $seen = [];
            public function make(ConfiguracionFiscal $config): FiscalGateway {
                $this->seen[] = [$config->tenant_id, $config->cuit_emisor, $config->punto_venta];
                return new class implements FiscalGateway {
                    public function lastVoucher(int $point, int $type): int { return $point * 100; }
                    public function findVoucher(int $number, int $point, int $type): ?array { return null; }
                    public function createVoucher(array $data): array { return ['CAE' => 'FAKE-CAE', 'CAEFchVto' => '20261201']; }
                };
            }
        };
        $service = new AfipFacturacionService($factory);
        $sales = [];
        foreach ([1,2] as $suffix) {
            [$tenant, $user] = $this->commerce('Fiscal '.$suffix);
            $sales[] = app(TenantContext::class)->run($tenant, function () use ($suffix, $user, $service, $tenant) {
                ConfiguracionFiscal::create($this->payload($suffix));
                $cash = Caja::create(['usuario_id' => $user->id, 'fecha_apertura' => now(), 'monto_apertura' => 0]);
                $sale = Venta::create(['caja_id' => $cash->id, 'usuario_id' => $user->id, 'numero_factura' => 'FISCAL-'.$suffix, 'fecha' => now(), 'tipo_pago' => 'efectivo', 'estado' => 'cerrada', 'total_final' => 10]);
                $this->assertSame($suffix * 100 + 1, $service->facturarVenta($sale)['comprobante_numero']);
                return [$tenant, $sale];
            });
        }
        $this->assertNotSame($factory->seen[0][0], $factory->seen[1][0]);
        $this->assertNotSame($factory->seen[0][1], $factory->seen[1][1]);
        app(TenantContext::class)->set($sales[1][0]);
        $this->expectException(RuntimeException::class);
        $service->facturarVenta($sales[0][1]);
    }
}
