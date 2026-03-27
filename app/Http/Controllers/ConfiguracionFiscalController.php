<?php

namespace App\Http\Controllers;

use App\Models\ConfiguracionFiscal;
use App\Support\CuitValidator;
use Illuminate\Http\Request;

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
            'certificado_path' => 'nullable|string|max:500',
            'clave_privada_path' => 'nullable|string|max:500',
            'passphrase_certificado' => 'nullable|string|max:255',
        ]);

        if (! empty($validated['cuit_emisor'])) {
            $validated['cuit_emisor'] = CuitValidator::normalize($validated['cuit_emisor']);
        }

        if (empty($validated['passphrase_certificado'])) {
            unset($validated['passphrase_certificado']);
        }

        $config = ConfiguracionFiscal::query()->first();

        if (! $config) {
            $config = ConfiguracionFiscal::create($validated);
        } else {
            $config->update($validated);
        }

        $data = $config->fresh()->toArray();
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
