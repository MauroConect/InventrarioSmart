<?php

namespace App\Http\Controllers;

use App\Models\ConfiguracionComercio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ConfiguracionComercioController extends Controller
{
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
            'color_primario' => 'nullable|string|max:7',
            'color_sidebar' => 'nullable|string|max:7',
            'mensaje_ticket' => 'nullable|string|max:500',
            'mensaje_footer' => 'nullable|string|max:500',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,svg,webp|max:2048',
            'quitar_logo' => 'nullable|boolean',
        ]);

        $config = ConfiguracionComercio::first();
        if (!$config) {
            $config = new ConfiguracionComercio();
        }

        $config->fill(collect($validated)->except(['logo', 'quitar_logo'])->toArray());

        if ($request->boolean('quitar_logo') && $config->logo_path) {
            Storage::disk('public')->delete($config->logo_path);
            $config->logo_path = null;
        }

        if ($request->hasFile('logo')) {
            if ($config->logo_path) {
                Storage::disk('public')->delete($config->logo_path);
            }
            $config->logo_path = $request->file('logo')->store('comercio', 'public');
        }

        $config->save();
        ConfiguracionComercio::limpiarCache();

        return response()->json([
            'message' => 'Configuración guardada correctamente',
            'config' => $config->fresh(),
        ]);
    }
}
