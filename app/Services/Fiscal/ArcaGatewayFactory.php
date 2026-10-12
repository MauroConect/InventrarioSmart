<?php

namespace App\Services\Fiscal;

use App\Models\ConfiguracionFiscal;
use RuntimeException;

class ArcaGatewayFactory implements FiscalGatewayFactory
{
    public function make(ConfiguracionFiscal $config): FiscalGateway
    {
        if (! config('fiscal.enabled', false)) { throw new RuntimeException('Homologación fiscal no configurada; emisión externa deshabilitada.'); }
        if ($config->ambiente === 'produccion' && ! config('fiscal.production_enabled', false)) { throw new RuntimeException('Emisión productiva no habilitada.'); }
        if (! class_exists(\SoapClient::class)) { throw new RuntimeException('El adaptador directo ARCA requiere la extensión SOAP.'); }
        $store = app(FiscalCredentialStore::class);
        return new ArcaSoapGateway($config, $store->resolve($config, $config->certificado_path), $store->resolve($config, $config->clave_privada_path));
    }
}
