import React, { useState, useEffect } from 'react';
import axios from 'axios';

export default function Proveedores() {
    const [proveedores, setProveedores] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showModal, setShowModal] = useState(false);
    const [editing, setEditing] = useState(null);
    const [formData, setFormData] = useState({
        nombre: '', razon_social: '', cuit: '', telefono: '', email: '', direccion: '', activo: true
    });

    useEffect(() => {
        fetchProveedores();
    }, []);

    const fetchProveedores = async () => {
        try {
            const response = await axios.get('/proveedores');
            setProveedores(response.data.data || response.data);
        } catch (error) {
            console.error('Error:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            if (editing) {
                await axios.put(`/proveedores/${editing}`, formData);
            } else {
                await axios.post('/proveedores', formData);
            }
            fetchProveedores();
            setShowModal(false);
            setEditing(null);
            setFormData({ nombre: '', razon_social: '', cuit: '', telefono: '', email: '', direccion: '', activo: true });
        } catch (error) {
            alert('Error al guardar');
        }
    };

    if (loading) return <div>Cargando...</div>;

    const proveedoresList = Array.isArray(proveedores) ? proveedores : (proveedores.data || []);

    return (
        <div>
            <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 sm:gap-0 mb-4 sm:mb-6">
                <h1 className="text-2xl sm:text-3xl font-bold">Proveedores</h1>
                <button 
                    onClick={() => { setEditing(null); setShowModal(true); }} 
                    className="w-full sm:w-auto bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
                >
                    Nuevo Proveedor
                </button>
            </div>
            <div className="bg-white rounded-lg shadow overflow-hidden">
                <div className="overflow-x-auto -mx-3 sm:mx-0">
                    <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                                <th className="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase hidden sm:table-cell">CUIT</th>
                                <th className="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase hidden md:table-cell">Teléfono</th>
                                <th className="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                            </tr>
                        </thead>
                        <tbody className="bg-white divide-y divide-gray-200">
                            {proveedoresList.map((prov) => (
                                <tr key={prov.id} className="hover:bg-gray-50">
                                    <td className="px-3 sm:px-6 py-4">
                                        <div className="font-medium text-gray-900">{prov.nombre}</div>
                                        <div className="text-sm text-gray-500 sm:hidden">CUIT: {prov.cuit || '-'}</div>
                                        <div className="text-sm text-gray-500 sm:hidden md:hidden">Tel: {prov.telefono || '-'}</div>
                                    </td>
                                    <td className="px-3 sm:px-6 py-4 hidden sm:table-cell">{prov.cuit || '-'}</td>
                                    <td className="px-3 sm:px-6 py-4 hidden md:table-cell">{prov.telefono || '-'}</td>
                                    <td className="px-3 sm:px-6 py-4">
                                        <div className="flex flex-col sm:flex-row gap-2">
                                            <button 
                                                onClick={() => { setEditing(prov.id); setFormData(prov); setShowModal(true); }} 
                                                className="text-blue-600 hover:text-blue-800 text-sm"
                                            >
                                                Editar
                                            </button>
                                            <button 
                                                onClick={() => axios.delete(`/proveedores/${prov.id}`).then(fetchProveedores)} 
                                                className="text-red-600 hover:text-red-800 text-sm"
                                            >
                                                Eliminar
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
            {showModal && (
                <div className="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50 p-4">
                    <div className="bg-white rounded-lg w-full max-w-md max-h-[90vh] overflow-y-auto">
                        <div className="sticky top-0 bg-white border-b px-4 sm:px-6 py-4">
                            <h3 className="text-lg font-bold">{editing ? 'Editar' : 'Nuevo'} Proveedor</h3>
                        </div>
                        <form onSubmit={handleSubmit} className="p-4 sm:p-6 space-y-3">
                            <input 
                                type="text" 
                                placeholder="Nombre" 
                                value={formData.nombre} 
                                onChange={(e) => setFormData({...formData, nombre: e.target.value})} 
                                className="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                required 
                            />
                            <input 
                                type="text" 
                                placeholder="CUIT" 
                                value={formData.cuit} 
                                onChange={(e) => setFormData({...formData, cuit: e.target.value})} 
                                className="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500" 
                            />
                            <input 
                                type="text" 
                                placeholder="Teléfono" 
                                value={formData.telefono} 
                                onChange={(e) => setFormData({...formData, telefono: e.target.value})} 
                                className="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500" 
                            />
                            <input 
                                type="email" 
                                placeholder="Email" 
                                value={formData.email} 
                                onChange={(e) => setFormData({...formData, email: e.target.value})} 
                                className="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500" 
                            />
                            <textarea 
                                placeholder="Dirección" 
                                value={formData.direccion} 
                                onChange={(e) => setFormData({...formData, direccion: e.target.value})} 
                                className="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                rows="3"
                            />
                            <div className="flex flex-col sm:flex-row justify-end gap-2 pt-3 border-t">
                                <button 
                                    type="button" 
                                    onClick={() => setShowModal(false)} 
                                    className="w-full sm:w-auto px-4 py-2 border rounded hover:bg-gray-50"
                                >
                                    Cancelar
                                </button>
                                <button 
                                    type="submit" 
                                    className="w-full sm:w-auto px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
                                >
                                    Guardar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
}
