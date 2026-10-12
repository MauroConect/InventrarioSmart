<?php

namespace App\Http\Controllers;

use App\Models\ConfiguracionFiscal;
use App\Support\CuitValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Support\Tenancy\TenantContext;

class ConfiguracionFiscalController extends Controller
{
    public function show()
    {
        $config = ConfiguracionFiscal::query()->first();

        if (! $config) {
            return response()->json([
                'razon_social' => null,
                'cuit_emisor' => null,
                'condicion_iva' => 'monotributo',
                'punto_venta' => null,
                'ambiente' => 'homologacion',
                'comprobante_tipo_default' => 'B',
                'certificado_path' => null,
                'clave_privada_path' => null,
                'has_passphrase' => false,
            ]);
        }

        $data = $config->toArray();
        $data['certificado_path'] = $config->certificado_path;
        $data['clave_privada_path'] = $config->clave_privada_path;
        $hasPass = ! empty($config->passphrase_certificado);
        unset($data['passphrase_certificado']);
        $data['has_passphrase'] = $hasPass;

        return response()->json($data);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'razon_social' => 'nullable|string|max:255',
            'cuit_emisor' => ['nullable', 'string', 'max:20', function ($attribute, $value, $fail) {
                if ($value === null || $value === '') {
                    return;
                }
                $n = CuitValidator::normalize($value);
                if (strlen($n) !== 11 || ! CuitValidator::isValid($n)) {
                    $fail('El CUIT emisor no es valido.');
                }
            }],
            'condicion_iva' => 'required|in:responsable_inscripto,monotributo,exento',
            'punto_venta' => 'nullable|integer|min:1|max:99999',
            'ambiente' => 'required|in:homologacion,produccion',
            'comprobante_tipo_default' => 'required|in:A,B,C',
            'certificado_path' => ['nullable', 'string', 'max:100', 'regex:/^[a-zA-Z0-9_-]+\.(crt|pem)$/'],
            'clave_privada_path' => ['nullable', 'string', 'max:100', 'regex:/^[a-zA-Z0-9_-]+\.(key|pem)$/'],
            'passphrase_certificado' => 'nullable|string|max:255',
        ]);

        if (! empty($validated['cuit_emisor'])) {
            $validated['cuit_emisor'] = CuitValidator::normalize($validated['cuit_emisor']);
        }
        validator($validated, ['cuit_emisor' => [Rule::unique('configuraciones_fiscales', 'cuit_emisor')
            ->where('punto_venta', $validated['punto_venta'] ?? null)->where('ambiente', $validated['ambiente'])
            ->whereNot('tenant_id', app(TenantContext::class)->id())]])->validate();

        if (empty($validated['passphrase_certificado'])) {
            unset($validated['passphrase_certificado']);
        }

        $config = DB::transaction(function () use ($validated) {
            \App\Models\Tenant::whereKey(app(TenantContext::class)->id())->lockForUpdate()->firstOrFail();
            $config = ConfiguracionFiscal::query()->firstOrNew();
            $config->fill($validated)->save();
            return $config;
        });

        $data = $config->fresh()->toArray();
        $data['certificado_path'] = $config->certificado_path;
        $data['clave_privada_path'] = $config->clave_privada_path;
        $hasPass = ! empty($config->passphrase_certificado);
        unset($data['passphrase_certificado']);
        $data['has_passphrase'] = $hasPass;

        return response()->json([
            'message' => 'Configuracion fiscal guardada correctamente.',
            'data' => $data,
        ]);
    }

    /**
     * Datos publicos del emisor para comprobantes (sin certificados ni secretos).
     */
    public function paraComprobante()
    {
        $config = ConfiguracionFiscal::query()->first();

        if (!$config) {
            return response()->json([
                'razon_social' => null,
                'cuit_emisor' => null,
                'punto_venta' => null,
                'ambiente' => null,
                'condicion_iva' => null,
            ]);
        }

        return response()->json([
            'razon_social' => $config->razon_social,
            'cuit_emisor' => $config->cuit_emisor,
            'punto_venta' => $config->punto_venta,
            'ambiente' => $config->ambiente,
            'condicion_iva' => $config->condicion_iva,
        ]);
    }
}
