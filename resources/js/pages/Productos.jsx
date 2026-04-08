import React, { useState, useEffect, useCallback } from 'react';
import axios from 'axios';
import { useAuth } from '../context/AuthContext';
import { canAccess } from '../utils/permissions';

export default function Productos() {
    const { user } = useAuth();
    const canManage = canAccess(user, 'productos.manage');
    const isVendedor = user?.role === 'vendedor';
    const [productos, setProductos] = useState([]);
    const [categorias, setCategorias] = useState([]);
    const [proveedores, setProveedores] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showModal, setShowModal] = useState(false);
    const [editing, setEditing] = useState(null);
    const [searchInput, setSearchInput] = useState('');
    const [debouncedSearch, setDebouncedSearch] = useState('');
    const [page, setPage] = useState(1);
    const [pagination, setPagination] = useState({
        current_page: 1,
        last_page: 1,
        total: 0,
        from: 0,
        to: 0,
    });
    const [error, setError] = useState('');
    const [success, setSuccess] = useState('');
    const [formData, setFormData] = useState({
        codigo: '',
        nombre: '',
        descripcion: '',
        precio_compra: 0,
        precio_venta: 0,
        stock_minimo: 0,
        stock_actual: 0,
        categoria_id: '',
        proveedor_id: '',
        activo: true,
    });

    useEffect(() => {
        fetchCategorias();
        if (canManage) {
            fetchProveedores();
        }
    }, [canManage]);

    useEffect(() => {
        const timeoutId = setTimeout(() => setDebouncedSearch(searchInput), 500);
        return () => clearTimeout(timeoutId);
    }, [searchInput]);

    const loadProductos = useCallback(async () => {
        try {
            setLoading(true);
            setError('');
            const params = { page };
            if (debouncedSearch) {
                params.search = debouncedSearch;
            }
            const response = await axios.get('/productos', { params });
            const body = response.data;
            let rows = [];
            if (body && Array.isArray(body.data) && body.last_page !== undefined) {
                rows = body.data;
                setProductos(rows);
                setPagination({
                    current_page: body.current_page || 1,
                    last_page: body.last_page || 1,
                    total: body.total || 0,
                    from: body.from ?? 0,
                    to: body.to ?? 0,
                });
            } else {
                rows = Array.isArray(body?.data)
                    ? body.data
                    : Array.isArray(body)
                      ? body
                      : [];
                setProductos(rows);
                setPagination({
                    current_page: 1,
                    last_page: 1,
                    total: rows.length,
                    from: rows.length ? 1 : 0,
                    to: rows.length,
                });
            }
            return rows;
        } catch (err) {
            console.error('Error al cargar productos:', err);
            setError('Error al cargar los productos. Por favor, intenta nuevamente.');
            setProductos([]);
            setPagination({ current_page: 1, last_page: 1, total: 0, from: 0, to: 0 });
            return [];
        } finally {
            setLoading(false);
        }
    }, [page, debouncedSearch]);

    useEffect(() => {
        loadProductos();
    }, [loadProductos]);

    const fetchCategorias = async () => {
        try {
            const response = await axios.get('/categorias');
            const categoriasData = response.data?.data || response.data || [];
            setCategorias(Array.isArray(categoriasData) ? categoriasData : []);
        } catch (error) {
            console.error('Error al cargar categorías:', error);
            setCategorias([]);
        }
    };

    const fetchProveedores = async () => {
        try {
            const response = await axios.get('/proveedores');
            const proveedoresData = response.data?.data || response.data || [];
            setProveedores(Array.isArray(proveedoresData) ? proveedoresData : []);
        } catch (error) {
            console.error('Error al cargar proveedores:', error);
            setProveedores([]);
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            setError('');
            setSuccess('');
            
            if (editing) {
                await axios.put(`/productos/${editing}`, formData);
                setSuccess('Producto actualizado correctamente');
            } else {
                await axios.post('/productos', formData);
                setSuccess('Producto creado correctamente');
            }
            
            loadProductos();
            setShowModal(false);
            setEditing(null);
            resetForm();
            
            setTimeout(() => setSuccess(''), 3000);
        } catch (error) {
            console.error('Error al guardar producto:', error);
            const errorMessage = error.response?.data?.message || 
                                error.response?.data?.error || 
                                'Error al guardar el producto. Verifica los datos ingresados.';
            setError(errorMessage);
        }
    };

    const resetForm = () => {
        setFormData({
            codigo: '',
            nombre: '',
            descripcion: '',
            precio_compra: 0,
            precio_venta: 0,
            stock_minimo: 0,
            stock_actual: 0,
            categoria_id: '',
            proveedor_id: '',
            activo: true,
        });
    };

    const handleEdit = (producto) => {
        setEditing(producto.id);
        setError('');
        setSuccess('');
        setFormData({
            codigo: producto.codigo || '',
            nombre: producto.nombre || '',
            descripcion: producto.descripcion || '',
            precio_compra: producto.precio_compra || 0,
            precio_venta: producto.precio_venta || 0,
            stock_minimo: producto.stock_minimo || 0,
            stock_actual: producto.stock_actual || 0,
            categoria_id: producto.categoria_id || '',
            proveedor_id: producto.proveedor_id || '',
            activo: producto.activo !== undefined ? producto.activo : true,
        });
        setShowModal(true);
    };

    const handleDelete = async (id) => {
        if (!window.confirm('¿Está seguro de eliminar este producto?')) return;
        try {
            setError('');
            setSuccess('');
            await axios.delete(`/productos/${id}`);
            setSuccess('Producto eliminado correctamente');
            const rows = await loadProductos();
            if (rows.length === 0 && page > 1) {
                setPage((p) => p - 1);
            }
            setTimeout(() => setSuccess(''), 3000);
        } catch (error) {
            console.error('Error al eliminar producto:', error);
            const errorMessage = error.response?.data?.message || 
                                'Error al eliminar el producto. Verifica que no esté siendo utilizado.';
            setError(errorMessage);
            setTimeout(() => setError(''), 5000);
        }
    };

    const productosList = Array.isArray(productos) ? productos : [];

    return (
        <div className="p-3 sm:p-4 lg:p-6">
            <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 sm:gap-0 mb-4 sm:mb-6">
                <h1 className="text-2xl sm:text-3xl font-bold text-gray-800">Sabores y productos</h1>
                <div className="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                    <input
                        type="text"
                        placeholder="Buscar por nombre o código..."
                        value={searchInput}
                        onChange={(e) => {
                            setSearchInput(e.target.value);
                            setPage(1);
                        }}
                        className="w-full sm:w-auto px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                    {canManage && (
                        <button
                            onClick={() => {
                                setEditing(null);
                                resetForm();
                                setShowModal(true);
                            }}
                            className="w-full sm:w-auto bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 whitespace-nowrap"
                        >
                            + Nuevo producto
                        </button>
                    )}
                </div>
            </div>

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

            <div className="bg-white rounded-lg shadow overflow-hidden">
                {loading ? (
                    <div className="p-8 text-center text-gray-500">
                        <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                        <p className="mt-2">Cargando productos...</p>
                    </div>
                ) : productosList.length === 0 ? (
                    <div className="p-8 text-center text-gray-500">
                        <p className="text-lg">No hay productos registrados</p>
                        <p className="text-sm mt-2">Haz clic en "Nuevo Producto" para agregar uno</p>
                    </div>
                ) : (
                    <div className="overflow-x-auto -mx-3 sm:mx-0">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Código</th>
                                    <th className="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                                    <th className="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">Precio Compra</th>
                                    <th className="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Precio Venta</th>
                                    {!isVendedor && (
                                        <th className="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                                    )}
                                    <th className="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">Categoría</th>
                                    <th className="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">Estado</th>
                                    {canManage && (
                                        <th className="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                    )}
                                </tr>
                            </thead>
                            <tbody className="bg-white divide-y divide-gray-200">
                                {productosList.map((producto) => (
                                    <tr key={producto.id} className="hover:bg-gray-50">
                                        <td className="px-3 sm:px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {producto.codigo}
                                        </td>
                                        <td className="px-3 sm:px-6 py-4 text-sm text-gray-900">
                                            <div className="font-medium">{producto.nombre}</div>
                                            {producto.descripcion && (
                                                <div className="text-xs text-gray-500 mt-1">{producto.descripcion}</div>
                                            )}
                                            <div className="text-xs text-gray-500 md:hidden mt-1">
                                                Compra: ${parseFloat(producto.precio_compra || 0).toFixed(2)}
                                            </div>
                                            <div className="text-xs text-gray-500 lg:hidden mt-1">
                                                Cat: {producto.categoria?.nombre || '-'}
                                            </div>
                                            <div className="sm:hidden mt-1">
                                                <span className={`px-2 py-1 text-xs rounded-full ${producto.activo ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`}>
                                                    {producto.activo ? 'Activo' : 'Inactivo'}
                                                </span>
                                            </div>
                                        </td>
                                        <td className="px-3 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-500 hidden md:table-cell">
                                            ${parseFloat(producto.precio_compra || 0).toFixed(2)}
                                        </td>
                                        <td className="px-3 sm:px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            ${parseFloat(producto.precio_venta || 0).toFixed(2)}
                                        </td>
                                        {!isVendedor && (
                                            <td className="px-3 sm:px-6 py-4 whitespace-nowrap text-sm">
                                                <div className="flex flex-col">
                                                    <span className={parseInt(producto.stock_actual || 0) < parseInt(producto.stock_minimo || 0) ? 'text-red-600 font-bold' : 'text-gray-900'}>
                                                        {producto.stock_actual || 0}
                                                    </span>
                                                    {parseInt(producto.stock_actual || 0) < parseInt(producto.stock_minimo || 0) && (
                                                        <span className="text-xs text-red-500">Mín: {producto.stock_minimo}</span>
                                                    )}
                                                </div>
                                            </td>
                                        )}
                                        <td className="px-3 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-500 hidden lg:table-cell">
                                            {producto.categoria?.nombre || '-'}
                                        </td>
                                        <td className="px-3 sm:px-6 py-4 whitespace-nowrap hidden sm:table-cell">
                                            <span className={`px-2 py-1 text-xs rounded-full ${producto.activo ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`}>
                                                {producto.activo ? 'Activo' : 'Inactivo'}
                                            </span>
                                        </td>
                                        {canManage && (
                                            <td className="px-3 sm:px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div className="flex flex-col sm:flex-row gap-2">
                                                    <button
                                                        onClick={() => handleEdit(producto)}
                                                        className="text-blue-600 hover:text-blue-900 focus:outline-none text-left sm:text-center"
                                                    >
                                                        Editar
                                                    </button>
                                                    <button
                                                        onClick={() => handleDelete(producto.id)}
                                                        className="text-red-600 hover:text-red-900 focus:outline-none text-left sm:text-center"
                                                    >
                                                        Eliminar
                                                    </button>
                                                </div>
                                            </td>
                                        )}
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                        {pagination.last_page > 1 && (
                            <div className="flex flex-col sm:flex-row justify-between items-center gap-3 px-4 py-3 bg-gray-50 border-t border-gray-200">
                                <p className="text-sm text-gray-600">
                                    {pagination.total > 0
                                        ? `Mostrando ${pagination.from}–${pagination.to} de ${pagination.total}`
                                        : ''}
                                </p>
                                <div className="flex items-center gap-2">
                                    <button
                                        type="button"
                                        disabled={page <= 1}
                                        onClick={() => setPage((p) => Math.max(1, p - 1))}
                                        className={`px-3 py-1.5 text-sm border border-gray-300 rounded-md bg-white ${
                                            page <= 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-200'
                                        }`}
                                    >
                                        Anterior
                                    </button>
                                    <span className="text-sm text-gray-700 tabular-nums">
                                        Página {pagination.current_page} de {pagination.last_page}
                                    </span>
                                    <button
                                        type="button"
                                        disabled={page >= pagination.last_page}
                                        onClick={() =>
                                            setPage((p) => Math.min(pagination.last_page, p + 1))
                                        }
                                        className={`px-3 py-1.5 text-sm border border-gray-300 rounded-md bg-white ${
                                            page >= pagination.last_page
                                                ? 'opacity-50 cursor-not-allowed'
                                                : 'hover:bg-gray-200'
                                        }`}
                                    >
                                        Siguiente
                                    </button>
                                </div>
                            </div>
                        )}
                    </div>
                )}
            </div>

            {showModal && canManage && (
                <div className="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center p-3 sm:p-4">
                    <div className="relative bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                        <div className="sticky top-0 bg-white border-b px-4 sm:px-6 py-4">
                            <h3 className="text-lg sm:text-xl font-bold text-gray-800">{editing ? 'Editar' : 'Nuevo'} Producto</h3>
                        </div>
                        <form onSubmit={handleSubmit} className="p-4 sm:p-6 space-y-4">
                            {error && (
                                <div className="p-3 bg-red-100 border border-red-400 text-red-700 rounded text-sm">
                                    {error}
                                </div>
                            )}
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Código</label>
                                    <input
                                        type="text"
                                        value={formData.codigo}
                                        onChange={(e) => setFormData({ ...formData, codigo: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        required
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
                                    <input
                                        type="text"
                                        value={formData.nombre}
                                        onChange={(e) => setFormData({ ...formData, nombre: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        required
                                    />
                                </div>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                                <textarea
                                    value={formData.descripcion}
                                    onChange={(e) => setFormData({ ...formData, descripcion: e.target.value })}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md"
                                    rows="3"
                                />
                            </div>
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Precio Compra</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        value={formData.precio_compra}
                                        onChange={(e) => setFormData({ ...formData, precio_compra: parseFloat(e.target.value) || 0 })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        required
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Precio Venta</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        value={formData.precio_venta}
                                        onChange={(e) => setFormData({ ...formData, precio_venta: parseFloat(e.target.value) || 0 })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        required
                                    />
                                </div>
                            </div>
                            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Stock Mínimo</label>
                                    <input
                                        type="number"
                                        value={formData.stock_minimo}
                                        onChange={(e) => setFormData({ ...formData, stock_minimo: parseInt(e.target.value) || 0 })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        required
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Stock Actual</label>
                                    <input
                                        type="number"
                                        value={formData.stock_actual}
                                        onChange={(e) => setFormData({ ...formData, stock_actual: parseInt(e.target.value) || 0 })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        required
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Categoría *</label>
                                    <select
                                        value={formData.categoria_id || ''}
                                        onChange={(e) => setFormData({ ...formData, categoria_id: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        required
                                    >
                                        <option value="">Seleccionar categoría...</option>
                                        {categorias.filter(cat => cat.activa !== false).map((cat) => (
                                            <option key={cat.id} value={cat.id}>{cat.nombre}</option>
                                        ))}
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Proveedor</label>
                                <select
                                    value={formData.proveedor_id || ''}
                                    onChange={(e) => setFormData({ ...formData, proveedor_id: e.target.value || null })}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                >
                                    <option value="">Seleccionar proveedor (opcional)...</option>
                                    {proveedores.filter(prov => prov.activo !== false).map((prov) => (
                                        <option key={prov.id} value={prov.id}>{prov.nombre}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <label className="flex items-center">
                                    <input
                                        type="checkbox"
                                        checked={formData.activo !== false}
                                        onChange={(e) => setFormData({ ...formData, activo: e.target.checked })}
                                        className="mr-2 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                    />
                                    <span className="text-sm text-gray-700">Producto activo</span>
                                </label>
                            </div>
                            <div className="flex flex-col sm:flex-row justify-end gap-2 pt-4 border-t">
                                <button
                                    type="button"
                                    onClick={() => {
                                        setShowModal(false);
                                        setEditing(null);
                                        resetForm();
                                        setError('');
                                    }}
                                    className="w-full sm:w-auto px-6 py-2 border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500"
                                >
                                    Cancelar
                                </button>
                                <button
                                    type="submit"
                                    className="w-full sm:w-auto px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                >
                                    {editing ? 'Actualizar' : 'Guardar'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
}
