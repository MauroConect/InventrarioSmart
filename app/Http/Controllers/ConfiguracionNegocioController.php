<?php

namespace App\Http\Controllers;

use App\Models\ConfiguracionNegocio;
use Illuminate\Http\Request;

class ConfiguracionNegocioController extends Controller
{
    public function show()
    {
        $config = ConfiguracionNegocio::query()->first();

        if (! $config) {
            return response()->json($this->defaults());
        }

        return response()->json($config);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'nombre_negocio' => 'required|string|max:255',
            'slogan' => 'nullable|string|max:255',
            'logo_url' => 'nullable|url|max:500',
            'color_primario' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'color_fondo' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'color_texto' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'color_tarjeta' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'color_header' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'color_chip' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'color_precio' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'color_categoria' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'descripcion_corta' => 'nullable|string|max:500',
            'moneda' => 'required|in:ARS,USD,EUR',
            'telefono_whatsapp' => 'nullable|string|max:30',
            'instagram_url' => 'nullable|url|max:500',
            'direccion' => 'nullable|string|max:255',
            'horario_atencion' => 'nullable|string|max:255',
            'mensaje_bienvenida' => 'nullable|string|max:255',
            'mostrar_precios' => 'boolean',
            'mostrar_descripciones' => 'boolean',
        ]);

        $config = ConfiguracionNegocio::query()->first();

        if (! $config) {
            $config = ConfiguracionNegocio::create($validated);
        } else {
            $config->update($validated);
        }

        return response()->json([
            'message' => 'Configuracion del negocio guardada correctamente.',
            'data' => $config->fresh(),
        ]);
    }

    private function defaults(): array
    {
        return [
            'nombre_negocio' => 'Danielles Bar & Buffet',
            'slogan' => 'Menu digital',
            'logo_url' => null,
            'color_primario' => '#ea580c',
            'color_fondo' => '#fff7ed',
            'color_texto' => '#111827',
            'color_tarjeta' => '#ffffff',
            'color_header' => '#ffffff',
            'color_chip' => '#ea580c',
            'color_precio' => '#ea580c',
            'color_categoria' => '#f97316',
            'descripcion_corta' => 'Explora categorias y productos disponibles.',
            'moneda' => 'ARS',
            'telefono_whatsapp' => null,
            'instagram_url' => null,
            'direccion' => null,
            'horario_atencion' => null,
            'mensaje_bienvenida' => 'Bienvenido a nuestro menu digital',
            'mostrar_precios' => true,
            'mostrar_descripciones' => true,
        ];
    }
}
