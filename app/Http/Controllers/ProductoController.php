<?php

namespace App\Http\Controllers;

use App\Models\Producto;
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
        $validated = $request->validate([
            'codigo' => 'required|string|unique:productos,codigo',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'precio_compra' => 'required|numeric|min:0',
            'precio_venta' => 'required|numeric|min:0',
            'stock_minimo' => 'required|integer|min:0',
            'stock_actual' => 'required|integer|min:0',
            'categoria_id' => 'required|exists:categorias,id',
            'proveedor_id' => 'nullable|exists:proveedores,id',
            'activo' => 'boolean',
        ]);

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
        
        $validated = $request->validate([
            'codigo' => 'required|string|unique:productos,codigo,' . $id,
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'precio_compra' => 'required|numeric|min:0',
            'precio_venta' => 'required|numeric|min:0',
            'stock_minimo' => 'required|integer|min:0',
            'stock_actual' => 'required|integer|min:0',
            'categoria_id' => 'required|exists:categorias,id',
            'proveedor_id' => 'nullable|exists:proveedores,id',
            'activo' => 'boolean',
        ]);

        $producto->update($validated);
        return response()->json($producto->load(['categoria', 'proveedor']));
    }

    public function destroy($id)
    {
        $producto = Producto::findOrFail($id);
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
}
