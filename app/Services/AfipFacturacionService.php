<?php

namespace App\Services;

use Afip;
use App\Models\ConfiguracionFiscal;
use App\Models\Venta;
use App\Support\CuitValidator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class AfipFacturacionService
{
    public function facturarVenta(Venta $venta): array
    {
        $config = ConfiguracionFiscal::query()->first();

        if (!$config) {
            throw new RuntimeException('No existe configuracion fiscal. Complete la configuracion AFIP/ARCA.');
        }

        if (empty($config->cuit_emisor) || empty($config->punto_venta)) {
            throw new RuntimeException('Debe configurar CUIT emisor y punto de venta.');
        }

        $cuitEmisor = CuitValidator::normalize($config->cuit_emisor);
        if (strlen($cuitEmisor) !== 11 || ! CuitValidator::isValid($cuitEmisor)) {
            throw new RuntimeException('El CUIT emisor no es valido (11 digitos y digito verificador).');
        }

        if (empty($config->certificado_path) || empty($config->clave_privada_path)) {
            throw new RuntimeException('Debe configurar los paths de certificado y clave privada.');
        }

        $tipoComprobante = strtoupper((string) $config->comprobante_tipo_default);
        $this->assertCondicionVsComprobante($config->condicion_iva, $tipoComprobante);

        $total = (float) ($venta->total_final ?? $venta->total ?? 0);
        if ($total <= 0) {
            throw new RuntimeException('La venta tiene un total invalido para facturar.');
        }

        $cbteTipo = $this->resolveCbteTipo($tipoComprobante);
        $ptoVta = (int) $config->punto_venta;

        [$docTipo, $docNro] = $this->resolveDocumentoReceptor($venta, $tipoComprobante);

        $production = $config->ambiente === 'produccion';

        Log::info('AFIP facturacion: inicio', [
            'venta_id' => $venta->id,
            'ambiente' => $config->ambiente,
            'production_flag' => $production,
            'cbte_tipo' => $cbteTipo,
            'tipo_comprobante' => $tipoComprobante,
            'pto_vta' => $ptoVta,
        ]);

        $afip = new Afip([
            'CUIT' => (int) $cuitEmisor,
            'production' => $production,
            'cert' => $config->certificado_path,
            'key' => $config->clave_privada_path,
            'passphrase' => $config->passphrase_certificado ?: null,
            'res_folder' => storage_path('app/afip'),
        ]);

        $lastVoucher = $afip->ElectronicBilling->GetLastVoucher($ptoVta, $cbteTipo);
        $nextVoucher = $lastVoucher + 1;

        $data = [
            'CantReg' => 1,
            'PtoVta' => $ptoVta,
            'CbteTipo' => $cbteTipo,
            'Concepto' => 1,
            'DocTipo' => $docTipo,
            'DocNro' => $docNro,
            'CbteDesde' => $nextVoucher,
            'CbteHasta' => $nextVoucher,
            'CbteFch' => (int) Carbon::now()->format('Ymd'),
            'ImpTotal' => $total,
            'ImpTotConc' => 0,
            'ImpNeto' => $total,
            'ImpOpEx' => 0,
            'ImpIVA' => 0,
            'ImpTrib' => 0,
            'MonId' => 'PES',
            'MonCotiz' => 1,
        ];

        $result = $afip->ElectronicBilling->CreateVoucher($data);

        Log::info('AFIP facturacion: respuesta', [
            'venta_id' => $venta->id,
            'cae' => $result['CAE'] ?? null,
            'ambiente' => $config->ambiente,
        ]);

        return [
            'comprobante_tipo' => $tipoComprobante,
            'comprobante_numero' => $nextVoucher,
            'cae' => $result['CAE'] ?? null,
            'cae_vencimiento' => $this->formatCaeVto($result['CAEFchVto'] ?? null),
            'afip_response' => $result,
        ];
    }

    private function assertCondicionVsComprobante(?string $condicionIva, string $tipoComprobante): void
    {
        $c = $condicionIva ?? '';

        if ($tipoComprobante === 'A' && $c !== 'responsable_inscripto') {
            throw new RuntimeException(
                'Factura A solo corresponde a emisor Responsable Inscripto. Ajuste condicion IVA o use Factura B o C.'
            );
        }
    }

    /**
     * @return array{0: int, 1: int} DocTipo, DocNro
     */
    private function resolveDocumentoReceptor(Venta $venta, string $tipoComprobante): array
    {
        $cliente = $venta->cliente;

        if ($tipoComprobante === 'A') {
            if (! $cliente || empty($cliente->cuit)) {
                throw new RuntimeException(
                    'Factura A requiere cliente con CUIT valido de 11 digitos. Edite el cliente y cargue el CUIT.'
                );
            }
            $cuit = CuitValidator::normalize($cliente->cuit);
            if (strlen($cuit) !== 11 || ! CuitValidator::isValid($cuit)) {
                throw new RuntimeException('El CUIT del cliente no es valido para Factura A.');
            }

            return [80, (int) $cuit];
        }

        if ($cliente && ! empty($cliente->dni)) {
            $dni = (int) preg_replace('/\D/', '', (string) $cliente->dni);

            return [96, $dni > 0 ? $dni : 0];
        }

        return [99, 0];
    }

    private function resolveCbteTipo(string $tipo): int
    {
        return match (strtoupper($tipo)) {
            'A' => 1,
            'B' => 6,
            'C' => 11,
            default => 6,
        };
    }

    private function formatCaeVto(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        return Carbon::createFromFormat('Ymd', $value)->format('Y-m-d');
    }
}
