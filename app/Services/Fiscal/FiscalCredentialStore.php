<?php

namespace App\Services\Fiscal;

use App\Models\ConfiguracionFiscal;
use App\Support\Tenancy\{TenantContext, TenantRuntime};
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class FiscalCredentialStore
{
    public function resolve(ConfiguracionFiscal $config, string $name): string
    {
        if ((int) $config->tenant_id !== app(TenantContext::class)->id() || ! preg_match('/^[a-zA-Z0-9_-]+\.(crt|pem|key)$/', $name)) { throw new RuntimeException('Referencia fiscal inválida para el comercio.'); }
        $path = app(TenantRuntime::class)->privatePath('fiscal/'.$config->ambiente.'/credentials/'.$name);
        if (! Storage::disk('private')->exists($path)) { throw new RuntimeException('Falta instalar una credencial privada del comercio.'); }
        $root = realpath(Storage::disk('private')->path(app(TenantRuntime::class)->privatePath('fiscal/'.$config->ambiente.'/credentials')));
        $resolved = realpath(Storage::disk('private')->path($path));
        $base = realpath(Storage::disk('private')->path(''));
        $expectedRoot = $base.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, app(TenantRuntime::class)->privatePath('fiscal/'.$config->ambiente.'/credentials'));
        if (! $root || $root !== $expectedRoot || ! $resolved || ! str_starts_with($resolved, $root.DIRECTORY_SEPARATOR)) { throw new RuntimeException('Credencial fuera del namespace privado.'); }
        return $resolved;
    }
}
