import React, { useState, useEffect } from 'react';
import axios from 'axios';

export default function AumentoMasivoPrecios() {
    const [proveedores, setProveedores] = useState([]);
    const [productos, setProductos] = useState([]);
    const [productosSeleccionados, setProductosSeleccionados] = useState(new Set());
    const [proveedorSeleccionado, setProveedorSeleccionado] = useState('');
    const [modoFiltro, setModoFiltro] = useState('proveedor'); // 'proveedor' o 'general'
    const [porcentaje, setPorcentaje] = useState(0);
    const [aplicarACompra, setAplicarACompra] = useState(false);
    const [aplicarAVenta, setAplicarAVenta] = useState(false);
    const [loading, setLoading] = useState(false);
    const [cargandoProductos, setCargandoProductos] = useState(false);
    const [error, setError] = useState('');
    const [success, setSuccess] = useState('');
    const [search, setSearch] = useState('');

    useEffect(() => {
        fetchProveedores();
    }, []);

    useEffect(() => {
        if (modoFiltro === 'proveedor' && proveedorSeleccionado) {
            fetchProductosPorProveedor(proveedorSeleccionado);
        } else if (modoFiltro === 'general') {
            fetchTodosLosProductos();
        } else {
            setProductos([]);
            setProductosSeleccionados(new Set());
        }
    }, [modoFiltro, proveedorSeleccionado]);

    useEffect(() => {
        const timeoutId = setTimeout(() => {
            if (search && productos.length > 0) {
                filtrarProductos();
            }
        }, 300);
        return () => clearTimeout(timeoutId);
    }, [search]);

    const fetchProveedores = async () => {
        try {
            const response = await axios.get('/proveedores');
            const proveedoresData = response.data?.data || response.data || [];
            setProveedores(Array.isArray(proveedoresData) ? proveedoresData.filter(p => p.activo !== false) : []);
        } catch (error) {
            console.error('Error al cargar proveedores:', error);
            setError('Error al cargar los proveedores');
        }
    };

    const fetchProductosPorProveedor = async (proveedorId) => {
        try {
            setCargandoProductos(true);
            setError('');
            const response = await axios.get(`/productos/proveedor/${proveedorId}`);
            const productosData = Array.isArray(response.data) ? response.data : [];
            setProductos(productosData);
            setProductosSeleccionados(new Set());
        } catch (error) {
            console.error('Error al cargar productos:', error);
            setError('Error al cargar los productos del proveedor');
            setProductos([]);
        } finally {
            setCargandoProductos(false);
        }
    };

    const fetchTodosLosProductos = async () => {
        try {
            setCargandoProductos(true);
            setError('');
            const response = await axios.get('/productos', { params: { all: 'true' } });
            const productosData = Array.isArray(response.data) ? response.data : [];
            setProductos(productosData);
            setProductosSeleccionados(new Set());
        } catch (error) {
            console.error('Error al cargar productos:', error);
            setError('Error al cargar todos los productos');
            setProductos([]);
        } finally {
            setCargandoProductos(false);
        }
    };

    const filtrarProductos = () => {
        // La búsqueda se maneja automáticamente en el backend con el filtro search
        // pero también podemos filtrar localmente para mejor UX
        if (!search) {
            if (modoFiltro === 'proveedor' && proveedorSeleccionado) {
                fetchProductosPorProveedor(proveedorSeleccionado);
            } else if (modoFiltro === 'general') {
                fetchTodosLosProductos();
            }
            return;
        }
    };

    const handleSeleccionarTodos = () => {
        if (productosSeleccionados.size === productos.length) {
            setProductosSeleccionados(new Set());
        } else {
            setProductosSeleccionados(new Set(productos.map(p => p.id.toString())));
        }
    };

    const handleSeleccionarProducto = (productoId) => {
        const nuevoSet = new Set(productosSeleccionados);
        const idStr = productoId.toString();
        if (nuevoSet.has(idStr)) {
            nuevoSet.delete(idStr);
        } else {
            nuevoSet.add(idStr);
        }
        setProductosSeleccionados(nuevoSet);
    };

    const handleAplicarAumento = async () => {
        if (productosSeleccionados.size === 0) {
            setError('Debe seleccionar al menos un producto');
            return;
        }

        if (porcentaje <= 0 || porcentaje > 1000) {
            setError('El porcentaje debe ser mayor a 0 y menor o igual a 1000');
            return;
        }

        if (!aplicarACompra && !aplicarAVenta) {
            setError('Debe seleccionar al menos un tipo de precio (compra o venta)');
            return;
        }

        try {
            setLoading(true);
            setError('');
            setSuccess('');

            const productoIds = Array.from(productosSeleccionados).map(id => parseInt(id));

            const response = await axios.post('/productos/aumento-masivo', {
                proveedor_id: modoFiltro === 'proveedor' ? proveedorSeleccionado : null,
                producto_ids: productoIds,
                porcentaje_aumento: porcentaje,
                aplicar_a_compra: aplicarACompra,
                aplicar_a_venta: aplicarAVenta,
            });

            setSuccess(response.data.message || 'Aumento aplicado correctamente');
            
            // Recargar productos para mostrar los nuevos precios
            if (modoFiltro === 'proveedor' && proveedorSeleccionado) {
                await fetchProductosPorProveedor(proveedorSeleccionado);
            } else if (modoFiltro === 'general') {
                await fetchTodosLosProductos();
            }
            
            // Limpiar selección
            setProductosSeleccionados(new Set());
            setPorcentaje(0);

            setTimeout(() => setSuccess(''), 5000);
        } catch (error) {
            console.error('Error al aplicar aumento:', error);
            const errorMessage = error.response?.data?.message || 
                                'Error al aplicar el aumento. Verifica los datos ingresados.';
            setError(errorMessage);
            setTimeout(() => setError(''), 5000);
        } finally {
            setLoading(false);
        }
    };

    const productosFiltrados = productos.filter(producto => {
        if (!search) return true;
        const searchLower = search.toLowerCase();
        return producto.nombre.toLowerCase().includes(searchLower) ||
               producto.codigo.toLowerCase().includes(searchLower);
    });

    return (
        <div className="p-6">
            <div className="mb-6">
                <h1 className="text-3xl font-bold text-gray-800 mb-4">Aumento Masivo de Precios</h1>
                
                {error && (
                    <div className="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                        {error}
                    </div>
                )}

                {success && (
                    <div className="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                        {success}
                    </div>
                )}

                <div className="bg-white p-6 rounded-lg shadow mb-6">
                    <h2 className="text-xl font-semibold mb-4">Configuración del Aumento</h2>
                    
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Modo de selección
                            </label>
                            <div className="flex gap-4">
                                <label className="flex items-center">
                                    <input
                                        type="radio"
                                        name="modoFiltro"
                                        value="proveedor"
                                        checked={modoFiltro === 'proveedor'}
                                        onChange={(e) => {
                                            setModoFiltro(e.target.value);
                                            setProveedorSeleccionado('');
                                            setProductosSeleccionados(new Set());
                                        }}
                                        className="mr-2"
                                    />
                                    <span>Por Proveedor</span>
                                </label>
                                <label className="flex items-center">
                                    <input
                                        type="radio"
                                        name="modoFiltro"
                                        value="general"
                                        checked={modoFiltro === 'general'}
                                        onChange={(e) => {
                                            setModoFiltro(e.target.value);
                                            setProveedorSeleccionado('');
                                            setProductosSeleccionados(new Set());
                                        }}
                                        className="mr-2"
                                    />
                                    <span>Lista General</span>
                                </label>
                            </div>
                        </div>

                        {modoFiltro === 'proveedor' && (
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Seleccionar Proveedor
                                </label>
                                <select
                                    value={proveedorSeleccionado}
                                    onChange={(e) => {
                                        setProveedorSeleccionado(e.target.value);
                                        setProductosSeleccionados(new Set());
                                    }}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                >
                                    <option value="">Seleccionar proveedor...</option>
                                    {proveedores.map((prov) => (
                                        <option key={prov.id} value={prov.id}>
                                            {prov.nombre}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        )}

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Porcentaje de Aumento (%)
                            </label>
                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                max="1000"
                                value={porcentaje}
                                onChange={(e) => setPorcentaje(parseFloat(e.target.value) || 0)}
                                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="Ej: 15 para 15%"
                            />
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Aplicar a:
                            </label>
                            <div className="space-y-2">
                                <label className="flex items-center">
                                    <input
                                        type="checkbox"
                                        checked={aplicarACompra}
                                        onChange={(e) => setAplicarACompra(e.target.checked)}
                                        className="mr-2 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                    />
                                    <span>Precio de Compra</span>
                                </label>
                                <label className="flex items-center">
                                    <input
                                        type="checkbox"
                                        checked={aplicarAVenta}
                                        onChange={(e) => setAplicarAVenta(e.target.checked)}
                                        className="mr-2 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                    />
                                    <span>Precio de Venta</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                {(modoFiltro === 'general' || (modoFiltro === 'proveedor' && proveedorSeleccionado)) && (
                    <div className="bg-white p-6 rounded-lg shadow">
                        <div className="flex justify-between items-center mb-4">
                            <h2 className="text-xl font-semibold">
                                Productos {modoFiltro === 'proveedor' ? 'del Proveedor' : 'Disponibles'} 
                                ({productosFiltrados.length})
                            </h2>
                            <div className="flex gap-2">
                                <input
                                    type="text"
                                    placeholder="Buscar productos..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    className="px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                />
                                <button
                                    onClick={handleSeleccionarTodos}
                                    className="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500"
                                >
                                    {productosSeleccionados.size === productosFiltrados.length ? 'Deseleccionar Todos' : 'Seleccionar Todos'}
                                </button>
                            </div>
                        </div>

                        {cargandoProductos ? (
                            <div className="p-8 text-center text-gray-500">
                                <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                                <p className="mt-2">Cargando productos...</p>
                            </div>
                        ) : productosFiltrados.length === 0 ? (
                            <div className="p-8 text-center text-gray-500">
                                <p className="text-lg">No hay productos disponibles</p>
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-4 py-3 text-left">
                                                <input
                                                    type="checkbox"
                                                    checked={productosFiltrados.length > 0 && productosSeleccionados.size === productosFiltrados.length}
                                                    onChange={handleSeleccionarTodos}
                                                    className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                                />
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Código</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Precio Compra Actual</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Precio Venta Actual</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Precio Compra Nuevo</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Precio Venta Nuevo</th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white divide-y divide-gray-200">
                                        {productosFiltrados.map((producto) => {
                                            const precioCompraNuevo = aplicarACompra && porcentaje > 0 
                                                ? (parseFloat(producto.precio_compra || 0) * (1 + (porcentaje / 100))).toFixed(2)
                                                : null;
                                            const precioVentaNuevo = aplicarAVenta && porcentaje > 0 
                                                ? (parseFloat(producto.precio_venta || 0) * (1 + (porcentaje / 100))).toFixed(2)
                                                : null;
                                            
                                            return (
                                                <tr 
                                                    key={producto.id} 
                                                    className={`hover:bg-gray-50 ${productosSeleccionados.has(producto.id.toString()) ? 'bg-blue-50' : ''}`}
                                                >
                                                    <td className="px-4 py-4 whitespace-nowrap">
                                                        <input
                                                            type="checkbox"
                                                            checked={productosSeleccionados.has(producto.id.toString())}
                                                            onChange={() => handleSeleccionarProducto(producto.id)}
                                                            className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                                        />
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                        {producto.codigo}
                                                    </td>
                                                    <td className="px-6 py-4 text-sm text-gray-900">
                                                        <div className="font-medium">{producto.nombre}</div>
                                                        {producto.proveedor && (
                                                            <div className="text-xs text-gray-500 mt-1">
                                                                {producto.proveedor.nombre}
                                                            </div>
                                                        )}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        ${parseFloat(producto.precio_compra || 0).toFixed(2)}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                        ${parseFloat(producto.precio_venta || 0).toFixed(2)}
                                                    </td>
                                                    <td className={`px-6 py-4 whitespace-nowrap text-sm font-semibold ${
                                                        aplicarACompra && productosSeleccionados.has(producto.id.toString()) 
                                                            ? 'text-green-600' 
                                                            : 'text-gray-400'
                                                    }`}>
                                                        {precioCompraNuevo ? `$${precioCompraNuevo}` : '-'}
                                                    </td>
                                                    <td className={`px-6 py-4 whitespace-nowrap text-sm font-semibold ${
                                                        aplicarAVenta && productosSeleccionados.has(producto.id.toString()) 
                                                            ? 'text-green-600' 
                                                            : 'text-gray-400'
                                                    }`}>
                                                        {precioVentaNuevo ? `$${precioVentaNuevo}` : '-'}
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            </div>
                        )}

                        {productosSeleccionados.size > 0 && (
                            <div className="mt-6 flex justify-between items-center p-4 bg-blue-50 rounded-lg">
                                <span className="text-sm font-medium text-gray-700">
                                    {productosSeleccionados.size} producto(s) seleccionado(s)
                                </span>
                                <button
                                    onClick={handleAplicarAumento}
                                    disabled={loading || porcentaje <= 0 || (!aplicarACompra && !aplicarAVenta)}
                                    className="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:bg-gray-400 disabled:cursor-not-allowed"
                                >
                                    {loading ? 'Aplicando...' : `Aplicar ${porcentaje}% de Aumento`}
                                </button>
                            </div>
                        )}
                    </div>
                )}
            </div>
        </div>
    );
}
