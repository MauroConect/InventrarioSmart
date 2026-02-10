import React, { useState, useEffect } from 'react';
import axios from 'axios';

export default function MovimientosStock() {
    const [movimientos, setMovimientos] = useState([]);
    const [productos, setProductos] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showModal, setShowModal] = useState(false);
    const [formData, setFormData] = useState({
        producto_id: '', tipo: 'entrada', cantidad: 0, motivo: '', observaciones: ''
    });

    useEffect(() => {
        fetchMovimientos();
        fetchProductos();
    }, []);

    const fetchMovimientos = async () => {
        try {
            const response = await axios.get('/movimientos-stock');
            setMovimientos(response.data.data || response.data);
        } catch (error) {
            console.error('Error:', error);
        } finally {
            setLoading(false);
        }
    };

    const fetchProductos = async () => {
        try {
            const response = await axios.get('/productos');
            setProductos(response.data.data || response.data);
        } catch (error) {
            console.error('Error:', error);
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            await axios.post('/movimientos-stock', formData);
            fetchMovimientos();
            setShowModal(false);
            setFormData({ producto_id: '', tipo: 'entrada', cantidad: 0, motivo: '', observaciones: '' });
        } catch (error) {
            alert(error.response?.data?.message || 'Error al crear movimiento');
        }
    };

    if (loading) return <div>Cargando...</div>;

    const movimientosList = Array.isArray(movimientos) ? movimientos : (movimientos.data || []);
    const productosList = Array.isArray(productos) ? productos : (productos.data || productos || []);

    return (
        <div>
            <div className="flex justify-between items-center mb-6">
                <h1 className="text-3xl font-bold">Movimientos de Stock</h1>
                <button onClick={() => setShowModal(true)} className="bg-blue-600 text-white px-4 py-2 rounded">
                    Nuevo Movimiento
                </button>
            </div>
            <div className="bg-white rounded-lg shadow overflow-hidden">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Producto</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cantidad</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Motivo</th>
                        </tr>
                    </thead>
                    <tbody className="bg-white divide-y divide-gray-200">
                        {movimientosList.map((mov) => (
                            <tr key={mov.id}>
                                <td className="px-6 py-4">{new Date(mov.created_at).toLocaleString()}</td>
                                <td className="px-6 py-4">{mov.producto?.nombre}</td>
                                <td className="px-6 py-4">
                                    <span className={`px-2 py-1 rounded-full text-xs ${
                                        mov.tipo === 'entrada' ? 'bg-green-100 text-green-800' :
                                        mov.tipo === 'salida' ? 'bg-red-100 text-red-800' :
                                        'bg-yellow-100 text-yellow-800'
                                    }`}>
                                        {mov.tipo}
                                    </span>
                                </td>
                                <td className="px-6 py-4">{mov.cantidad}</td>
                                <td className="px-6 py-4">{mov.motivo}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            {showModal && (
                <div className="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50">
                    <div className="bg-white p-6 rounded-lg w-96">
                        <h3 className="text-lg font-bold mb-4">Nuevo Movimiento</h3>
                        <form onSubmit={handleSubmit} className="space-y-3">
                            <select value={formData.producto_id} onChange={(e) => setFormData({...formData, producto_id: e.target.value})} className="w-full px-3 py-2 border rounded" required>
                                <option value="">Seleccionar Producto</option>
                                {productosList.map(p => <option key={p.id} value={p.id}>{p.nombre}</option>)}
                            </select>
                            <select value={formData.tipo} onChange={(e) => setFormData({...formData, tipo: e.target.value})} className="w-full px-3 py-2 border rounded" required>
                                <option value="entrada">Entrada</option>
                                <option value="salida">Salida</option>
                                <option value="ajuste">Ajuste</option>
                            </select>
                            <input type="number" placeholder="Cantidad" value={formData.cantidad} onChange={(e) => setFormData({...formData, cantidad: parseInt(e.target.value) || 0})} className="w-full px-3 py-2 border rounded" required />
                            <input type="text" placeholder="Motivo" value={formData.motivo} onChange={(e) => setFormData({...formData, motivo: e.target.value})} className="w-full px-3 py-2 border rounded" required />
                            <textarea placeholder="Observaciones" value={formData.observaciones} onChange={(e) => setFormData({...formData, observaciones: e.target.value})} className="w-full px-3 py-2 border rounded" />
                            <div className="flex justify-end gap-2">
                                <button type="button" onClick={() => setShowModal(false)} className="px-4 py-2 border rounded">Cancelar</button>
                                <button type="submit" className="px-4 py-2 bg-blue-600 text-white rounded">Guardar</button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
}
