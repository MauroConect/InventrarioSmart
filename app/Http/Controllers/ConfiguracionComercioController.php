<?php

namespace App\Http\Controllers;

use App\Models\ConfiguracionComercio;
use App\Services\Comercio\ComercioConfigService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\Request;

class ConfiguracionComercioController extends Controller
{
    public function __construct(
        private readonly TenantContext $context,
        private readonly ComercioConfigService $service,
    ) {
    }

    public function show()
    {
        return response()->json(ConfiguracionComercio::actual());
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'nombre_comercio' => 'required|string|max:255',
            'slogan' => 'nullable|string|max:255',
            'direccion' => 'nullable|string|max:500',
            'telefono' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'sitio_web' => 'nullable|url|max:255',
            'color_primario' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'color_sidebar' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'mensaje_ticket' => 'nullable|string|max:500',
            'mensaje_footer' => 'nullable|string|max:500',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,svg,webp|max:2048',
            'quitar_logo' => 'nullable|boolean',
        ]);

        $config = $this->service->update(
            $this->context->tenant(),
            collect($validated)->except(['logo', 'quitar_logo'])->toArray(),
            $request->file('logo'),
            $request->boolean('quitar_logo')
        );

        return response()->json([
            'message' => 'Configuración guardada correctamente',
            'config' => $config,
        ]);
    }
}
