<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\ConfiguracionNegocio;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MenuQrController extends Controller
{
    public function index(): View
    {
        return view('menu-qr');
    }

    public function catalogoPublico(): JsonResponse
    {
        $negocio = ConfiguracionNegocio::query()->first();
        $categorias = Categoria::query()
            ->where('activa', true)
            ->orderBy('nombre')
            ->with([
                'productos' => function ($query) {
                    $query->where('activo', true)
                        ->orderBy('nombre')
                        ->with(['imagenes:id,producto_id,ruta,orden'])
                        ->select([
                            'id',
                            'nombre',
                            'descripcion',
                            'precio_venta',
                            'categoria_id',
                        ]);
                },
            ])
            ->get(['id', 'nombre', 'descripcion']);

        return response()->json([
            'negocio' => [
                'nombre_negocio' => $negocio?->nombre_negocio ?? 'Danielles Bar & Buffet',
                'slogan' => $negocio?->slogan ?? 'Menu digital',
                'logo_url' => $negocio?->logo_url,
                'color_primario' => $negocio?->color_primario ?? '#ea580c',
                'color_fondo' => $negocio?->color_fondo ?? '#fff7ed',
                'color_texto' => $negocio?->color_texto ?? '#111827',
                'color_tarjeta' => $negocio?->color_tarjeta ?? '#ffffff',
                'color_header' => $negocio?->color_header ?? '#ffffff',
                'color_chip' => $negocio?->color_chip ?? '#ea580c',
                'color_precio' => $negocio?->color_precio ?? '#ea580c',
                'color_categoria' => $negocio?->color_categoria ?? '#f97316',
                'descripcion_corta' => $negocio?->descripcion_corta ?? 'Explora categorias y productos disponibles.',
                'moneda' => $negocio?->moneda ?? 'ARS',
                'telefono_whatsapp' => $negocio?->telefono_whatsapp,
                'instagram_url' => $negocio?->instagram_url,
                'direccion' => $negocio?->direccion,
                'horario_atencion' => $negocio?->horario_atencion,
                'mensaje_bienvenida' => $negocio?->mensaje_bienvenida ?? 'Bienvenido a nuestro menu digital',
                'mostrar_precios' => (bool) ($negocio?->mostrar_precios ?? true),
                'mostrar_descripciones' => (bool) ($negocio?->mostrar_descripciones ?? true),
            ],
            'categorias' => $categorias,
        ]);
    }

    public function imagenProducto(string $filename): BinaryFileResponse
    {
        $safeName = basename($filename);
        $path = storage_path('app/public/productos/' . $safeName);

        abort_unless(File::exists($path), 404);

        return response()->file($path);
    }
}
