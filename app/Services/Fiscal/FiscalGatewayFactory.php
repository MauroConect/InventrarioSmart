<?php

namespace App\Services\Fiscal;

use App\Models\ConfiguracionFiscal;

interface FiscalGatewayFactory
{
    public function make(ConfiguracionFiscal $config): FiscalGateway;
}
