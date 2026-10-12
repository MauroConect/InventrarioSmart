<?php

namespace App\Http\Controllers;

use App\Models\ItemVenta;
use App\Models\Producto;
use App\Services\CustomForms\CustomFormService;
use App\Services\Modules\TenantFeatureService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProductoController extends Controller
{
    public function __construct(
        private readonly TenantFeatureService $features,
        private readonly CustomFormService $customForms,
    ) {
    }

    /**
     * Siguiente código interno incremental (prefijo INT + dígitos) para productos sin código de fábrica.
     */
    public function siguienteCodigoInterno()
    {
        $prefix = 'INT';
        $prefixLen = strlen($prefix);
        $maxNum = 0;

        foreach (Producto::query()->where('codigo', 'like', $prefix.'%')->pluck('codigo') as $codigo) {
            $suffix = substr((string) $codigo, $prefixLen);
            if ($suffix !== '' && ctype_digit($suffix)) {
                $maxNum = max($maxNum, (int) $suffix);
            }
        }

        $next = $maxNum + 1;
        $codigo = $prefix.str_pad((string) $next, 6, '0', STR_PAD_LEFT);

        return response()->json(['codigo' => $codigo]);
    }

    public function index(Request $request)
    {
        $query = Producto::with(['categoria', 'proveedor']);

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('codigo', 'like', "%{$search}%");
            });
        }

        if ($request->has('categoria_id')) {
            $query->where('categoria_id', $request->categoria_id);
        }

        // Si se solicita obtener todos los productos (para POS, comandas y aumento masivo)
        if ($request->boolean('all')) {
            $query->where('activo', true);
            $productos = $query->get();
            $this->customForms->attachToMany($productos, 'productos');

            return response()->json($productos);
        }

        $page = $query->paginate(15);
        $this->customForms->attachToMany($page->getCollection(), 'productos');

        return response()->json($page);
    }

    public function buscarPorCodigo(Request $request)
    {
        $request->validate(['codigo' => 'required|string']);

        $producto = Producto::with(['categoria', 'proveedor'])
            ->where('codigo', $request->codigo)
            ->where('activo', true)
            ->first();

        if (!$producto) {
            return response()->json(['message' => 'Producto no encontrado'], 404);
        }

        return response()->json($producto);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo' => 'required|string|unique:productos,codigo',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'precio_compra' => 'required|numeric|min:0',
            'precio_venta' => 'required|numeric|min:0',
            'stock_minimo' => 'required|numeric|min:0',
            'stock_actual' => 'required|numeric|min:0',
            'categoria_id' => 'required|exists:categorias,id',
            'proveedor_id' => 'nullable|exists:proveedores,id',
            'activo' => 'boolean',
            'tipo_venta' => 'nullable|in:unidad,peso',
            'unidad_medida' => 'nullable|in:u,kg,g,lt,ml',
        ]);

        $this->assertWeightedAllowed($validated);

        $producto = Producto::create($validated);
        $this->customForms->syncValues($producto, 'productos', $request->input('custom_fields', []));
        $this->customForms->attachTo($producto, 'productos');

        return response()->json($producto->load(['categoria', 'proveedor']), 201);
    }

    public function show($id)
    {
        $producto = Producto::with(['categoria', 'proveedor'])->findOrFail($id);
        $this->customForms->attachTo($producto, 'productos');

        return response()->json($producto);
    }

    public function update(Request $request, $id)
    {
        $producto = Producto::findOrFail($id);
        
        $validated = $request->validate([
            'codigo' => 'required|string|unique:productos,codigo,' . $id,
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'precio_compra' => 'required|numeric|min:0',
            'precio_venta' => 'required|numeric|min:0',
            'stock_minimo' => 'required|numeric|min:0',
            'stock_actual' => 'required|numeric|min:0',
            'categoria_id' => 'required|exists:categorias,id',
            'proveedor_id' => 'nullable|exists:proveedores,id',
            'activo' => 'boolean',
            'tipo_venta' => 'nullable|in:unidad,peso',
            'unidad_medida' => 'nullable|in:u,kg,g,lt,ml',
        ]);

        $this->assertWeightedAllowed($validated);

        $producto->update($validated);
        $this->customForms->syncValues($producto, 'productos', $request->input('custom_fields', []));
        $this->customForms->attachTo($producto, 'productos');

        return response()->json($producto->load(['categoria', 'proveedor']));
    }

    public function destroy($id)
    {
        $producto = Producto::findOrFail($id);

        $tieneHistorialVentas = ItemVenta::where('producto_id', $producto->id)->exists();

        if ($tieneHistorialVentas) {
            if (!$producto->activo) {
                return response()->json([
                    'message' => 'El producto tiene historial de ventas y ya se encuentra inactivo.',
                    'deactivated' => true,
                ], 200);
            }

            $producto->update(['activo' => false]);

            return response()->json([
                'message' => 'El producto tiene historial de ventas y no puede eliminarse. Se desactivo correctamente.',
                'deactivated' => true,
            ], 200);
        }

        $producto->delete();
        return response()->json(null, 204);
    }

    public function getByProveedor(Request $request, $proveedorId)
    {
        $productos = Producto::with(['categoria', 'proveedor'])
            ->where('proveedor_id', $proveedorId)
            ->where('activo', true)
            ->get();
        
        return response()->json($productos);
    }

    public function aumentoMasivo(Request $request)
    {
        $validated = $request->validate([
            'proveedor_id' => 'nullable|exists:proveedores,id',
            'producto_ids' => 'required|array|min:1',
            'producto_ids.*' => 'required|exists:productos,id',
            'porcentaje_aumento' => 'required|numeric|min:0|max:1000',
            'aplicar_a_compra' => 'boolean',
            'aplicar_a_venta' => 'boolean',
        ]);

        $productoIds = $validated['producto_ids'];
        $porcentaje = $validated['porcentaje_aumento'];
        $aplicarACompra = $request->has('aplicar_a_compra') && $request->aplicar_a_compra;
        $aplicarAVenta = $request->has('aplicar_a_venta') && $request->aplicar_a_venta;

        if (!$aplicarACompra && !$aplicarAVenta) {
            return response()->json([
                'message' => 'Debe seleccionar al menos un tipo de precio (compra o venta)'
            ], 422);
        }

        $productos = Producto::whereIn('id', $productoIds)->get();
        $actualizados = 0;

        foreach ($productos as $producto) {
            $actualizado = false;

            if ($aplicarACompra) {
                $nuevoPrecioCompra = $producto->precio_compra * (1 + ($porcentaje / 100));
                $producto->precio_compra = round($nuevoPrecioCompra, 2);
                $actualizado = true;
            }

            if ($aplicarAVenta) {
                $nuevoPrecioVenta = $producto->precio_venta * (1 + ($porcentaje / 100));
                $producto->precio_venta = round($nuevoPrecioVenta, 2);
                $actualizado = true;
            }

            if ($actualizado) {
                $producto->save();
                $actualizados++;
            }
        }

        return response()->json([
            'message' => "Se actualizaron {$actualizados} productos correctamente",
            'productos_actualizados' => $actualizados,
            'total_productos' => count($productoIds)
        ]);
    }

    private function assertWeightedAllowed(array $validated): void
    {
        if (($validated['tipo_venta'] ?? 'unidad') === 'peso' && ! $this->features->enabled('catalog.weighted')) {
            throw ValidationException::withMessages([
                'tipo_venta' => 'El módulo de productos pesables no está habilitado.',
            ]);
        }
    }
}
