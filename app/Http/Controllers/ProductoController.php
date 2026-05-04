<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class ProductoController extends Controller
{
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

        // Si se solicita obtener todos los productos (para aumento masivo)
        if ($request->has('all') && $request->all === 'true') {
            $query->where('activo', true);
            return response()->json($query->get());
        }

        return response()->json($query->paginate(15));
    }

    public function store(Request $request)
    {
        $this->normalizeProductoRequest($request);

        // stock_actual puede ser negativo (sobrevendido); stock_minimo suele ser ≥ 0
        $validated = $request->validate([
            'codigo' => 'required|string|unique:productos,codigo',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'precio_compra' => 'required|numeric|min:0',
            'precio_venta' => 'required|numeric|min:0',
            'stock_minimo' => 'required|integer|min:0',
            'stock_actual' => 'required|integer',
            'categoria_id' => 'required|exists:categorias,id',
            'proveedor_id' => 'nullable|exists:proveedores,id',
            'activo' => 'boolean',
        ], $this->productoValidationMessages());

        $producto = Producto::create($validated);
        return response()->json($producto->load(['categoria', 'proveedor']), 201);
    }

    public function show($id)
    {
        $producto = Producto::with(['categoria', 'proveedor'])->findOrFail($id);
        return response()->json($producto);
    }

    public function update(Request $request, $id)
    {
        $producto = Producto::findOrFail($id);

        $this->normalizeProductoRequest($request);

        $validated = $request->validate([
            'codigo' => 'required|string|unique:productos,codigo,' . $id,
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'precio_compra' => 'required|numeric|min:0',
            'precio_venta' => 'required|numeric|min:0',
            'stock_minimo' => 'required|integer|min:0',
            'stock_actual' => 'required|integer',
            'categoria_id' => 'required|exists:categorias,id',
            'proveedor_id' => 'nullable|exists:proveedores,id',
            'activo' => 'boolean',
        ], $this->productoValidationMessages());

        $producto->update($validated);
        return response()->json($producto->load(['categoria', 'proveedor']));
    }

    public function updateActivo(Request $request, Producto $producto)
    {
        $validated = $request->validate([
            'activo' => 'required|boolean',
        ]);
        $producto->activo = $validated['activo'];
        $producto->save();

        return response()->json($producto->load(['categoria', 'proveedor']));
    }

    public function destroy($id)
    {
        $producto = Producto::findOrFail($id);
        try {
            $producto->delete();
        } catch (QueryException $e) {
            if (($e->errorInfo[0] ?? '') === '23000') {
                return response()->json([
                    'message' => 'No se puede eliminar: el producto tiene ventas u otros registros vinculados. Podés desactivarlo para ocultarlo sin perder el historial.',
                ], 422);
            }
            throw $e;
        }

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

    /**
     * Mensajes explícitos: si el servidor usa resources/lang vacío, igual no se ve la clave cruda.
     *
     * @return array<string, string>
     */
    private function productoValidationMessages(): array
    {
        return [
            'codigo.required' => 'El código es obligatorio.',
            'codigo.unique' => 'Ese código ya está registrado.',
            'nombre.required' => 'El nombre es obligatorio.',
            'precio_compra.required' => 'El precio de compra es obligatorio.',
            'precio_compra.numeric' => 'El precio de compra debe ser un número.',
            'precio_compra.min' => 'El precio de compra no puede ser negativo.',
            'precio_venta.required' => 'El precio de venta es obligatorio.',
            'precio_venta.numeric' => 'El precio de venta debe ser un número.',
            'precio_venta.min' => 'El precio de venta no puede ser negativo.',
            'stock_minimo.required' => 'El stock mínimo es obligatorio.',
            'stock_minimo.integer' => 'El stock mínimo debe ser un número entero.',
            'stock_minimo.min' => 'El stock mínimo no puede ser negativo.',
            'stock_actual.required' => 'El stock actual es obligatorio.',
            'stock_actual.integer' => 'El stock actual debe ser un número entero.',
            'categoria_id.required' => 'La categoría es obligatoria.',
            'categoria_id.exists' => 'La categoría seleccionada no es válida.',
            'proveedor_id.exists' => 'El proveedor seleccionado no es válido.',
        ];
    }

    /**
     * Acepta comas como separador decimal, convierte stock a enteros y
     * proveedor opcional vacío a null para evitar fallos silenciosos de exists.
     */
    private function normalizeProductoRequest(Request $request): void
    {
        $data = $request->all();

        foreach (['precio_compra', 'precio_venta'] as $field) {
            if (! array_key_exists($field, $data)) {
                continue;
            }
            $raw = $data[$field];
            if ($raw === null || $raw === '') {
                continue;
            }
            if (is_string($raw)) {
                $clean = preg_replace('/[^\d,.-]/', '', trim($raw));
                $clean = str_replace(',', '.', $clean);
                if ($clean !== '' && is_numeric($clean)) {
                    $data[$field] = (float) $clean;
                }
            } elseif (is_numeric($raw)) {
                $data[$field] = (float) $raw;
            }
        }

        foreach (['stock_minimo', 'stock_actual'] as $field) {
            if (! array_key_exists($field, $data)) {
                continue;
            }
            $raw = $data[$field];
            if ($raw === null || $raw === '') {
                continue;
            }
            if (is_numeric($raw)) {
                $data[$field] = (int) round((float) $raw);
            }
        }

        if (array_key_exists('proveedor_id', $data) && $data['proveedor_id'] === '') {
            $data['proveedor_id'] = null;
        }

        $request->merge($data);
    }
}
