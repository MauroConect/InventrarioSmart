<?php

return [
    'enabled' => (bool) env('FISCAL_ENABLED', false),
    'production_enabled' => (bool) env('FISCAL_PRODUCTION_ENABLED', false),
    'homologacion' => ['wsaa' => 'https://wsaahomo.afip.gov.ar/ws/services/LoginCms?WSDL', 'wsfe' => 'https://wswhomo.afip.gov.ar/wsfev1/service.asmx?WSDL'],
    'produccion' => ['wsaa' => 'https://wsaa.afip.gov.ar/ws/services/LoginCms?WSDL', 'wsfe' => 'https://servicios1.afip.gov.ar/wsfev1/service.asmx?WSDL'],
];
