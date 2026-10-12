<?php

namespace App\Services\Fiscal;

use App\Models\ConfiguracionFiscal;
use App\Support\Tenancy\TenantRuntime;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use SoapClient;

/** Direct WSAA/WSFE transport. No SDK proxy receives credentials. External validation remains required. */
class ArcaSoapGateway implements FiscalGateway
{
    public function __construct(private readonly ConfiguracionFiscal $config, private readonly string $certificate, private readonly string $privateKey) {}

    private function client(string $service): SoapClient
    {
        return new SoapClient(config('fiscal.'.$this->config->ambiente.'.'.$service), ['exceptions' => true, 'trace' => false, 'connection_timeout' => 15, 'cache_wsdl' => WSDL_CACHE_NONE, 'stream_context' => stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true], 'http' => ['timeout' => 30]])]);
    }

    private function auth(): array
    {
        $disk = Storage::disk('private');
        $folder = app(TenantRuntime::class)->privatePath('fiscal/'.$this->config->ambiente.'/auth');
        $file = $folder.'/'.hash('sha256', $this->config->cuit_emisor.file_get_contents($this->certificate)).'.json';
        if ($disk->exists($file)) {
            $cached = json_decode($disk->get($file), true);
            if (isset($cached['expires'], $cached['Token'], $cached['Sign']) && strtotime($cached['expires']) > time() + 120) { return ['Token' => $cached['Token'], 'Sign' => $cached['Sign'], 'Cuit' => (int) $this->config->cuit_emisor]; }
        }
        $disk->makeDirectory($folder);
        $input = tempnam($disk->path($folder), 'tra-'); $output = $input.'.cms';
        try {
            $xml = new \SimpleXMLElement('<loginTicketRequest version="1.0"><header/><service>wsfe</service></loginTicketRequest>');
            $xml->header->addChild('uniqueId', (string) time());
            $xml->header->addChild('generationTime', gmdate('c', time() - 300));
            $xml->header->addChild('expirationTime', gmdate('c', time() + 3600));
            file_put_contents($input, $xml->asXML());
            $signed = openssl_cms_sign($input, $output, 'file://'.$this->certificate, ['file://'.$this->privateKey, $this->config->passphrase_certificado ?: ''], [], OPENSSL_CMS_BINARY, OPENSSL_ENCODING_DER);
            if (! $signed) { throw new RuntimeException('No se pudo firmar la autenticación fiscal.'); }
            $result = $this->client('wsaa')->loginCms(['in0' => base64_encode(file_get_contents($output))]);
            $ticket = simplexml_load_string($result->loginCmsReturn, \SimpleXMLElement::class, LIBXML_NONET);
            if (! $ticket || ! isset($ticket->credentials->token, $ticket->credentials->sign, $ticket->header->expirationTime)) { throw new RuntimeException('Respuesta de autenticación fiscal inválida.'); }
            $cached = ['Token' => (string) $ticket->credentials->token, 'Sign' => (string) $ticket->credentials->sign, 'expires' => (string) $ticket->header->expirationTime];
            $disk->put($file, json_encode($cached));
            return ['Token' => $cached['Token'], 'Sign' => $cached['Sign'], 'Cuit' => (int) $this->config->cuit_emisor];
        } catch (\Throwable $e) {
            throw new RuntimeException('No se pudo autenticar ante ARCA; revisar homologación y credenciales privadas.');
        } finally {
            if (is_file($input)) { unlink($input); } if (is_file($output)) { unlink($output); }
        }
    }

    private function request(string $method, array $parameters): object
    {
        try { return $this->client('wsfe')->__soapCall($method, [array_merge(['Auth' => $this->auth()], $parameters)]); }
        catch (\Throwable $e) { throw new RuntimeException('Comunicación fiscal incompleta; consultar el comprobante antes de reintentar.'); }
    }

    public function lastVoucher(int $point, int $type): int
    {
        $result = $this->request('FECompUltimoAutorizado', ['PtoVta' => $point, 'CbteTipo' => $type])->FECompUltimoAutorizadoResult;
        if (isset($result->Errors)) { throw new RuntimeException('ARCA rechazó la consulta de numeración.'); }
        return (int) $result->CbteNro;
    }

    public function findVoucher(int $number, int $point, int $type): ?array
    {
        $result = $this->request('FECompConsultar', ['FeCompConsReq' => ['CbteNro' => $number, 'PtoVta' => $point, 'CbteTipo' => $type]])->FECompConsultarResult;
        if (isset($result->Errors)) {
            $errors = is_array($result->Errors->Err) ? $result->Errors->Err : [$result->Errors->Err];
            foreach ($errors as $error) { if ((int) $error->Code === 602) { return null; } }
            throw new RuntimeException('ARCA no permitió reconciliar el comprobante.');
        }
        if (! isset($result->ResultGet->CodAutorizacion)) { return null; }
        return ['CAE' => (string) $result->ResultGet->CodAutorizacion, 'CAEFchVto' => (string) $result->ResultGet->FchVto, 'ImpTotal' => (float) $result->ResultGet->ImpTotal, 'DocTipo' => (int) $result->ResultGet->DocTipo, 'DocNro' => (int) $result->ResultGet->DocNro];
    }

    public function createVoucher(array $data): array
    {
        $header = ['CantReg' => 1, 'PtoVta' => $data['PtoVta'], 'CbteTipo' => $data['CbteTipo']];
        unset($data['CantReg'], $data['PtoVta'], $data['CbteTipo']);
        $result = $this->request('FECAESolicitar', ['FeCAEReq' => ['FeCabReq' => $header, 'FeDetReq' => ['FECAEDetRequest' => [$data]]]])->FECAESolicitarResult;
        $detail = $result->FeDetResp->FECAEDetResponse ?? null;
        if (is_array($detail)) { $detail = $detail[0]; }
        if (($detail->Resultado ?? null) !== 'A' || empty($detail->CAE)) { throw new RuntimeException('ARCA rechazó el comprobante; revisar los datos fiscales.'); }
        return ['CAE' => (string) $detail->CAE, 'CAEFchVto' => (string) $detail->CAEFchVto];
    }
}
