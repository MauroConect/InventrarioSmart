import React, { useState, useEffect } from 'react';
import axios from 'axios';

export default function Clientes() {
    const [clientes, setClientes] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showModal, setShowModal] = useState(false);
    const [editing, setEditing] = useState(null);
    const [formData, setFormData] = useState({
        nombre: '', apellido: '', dni: '', telefono: '', email: '', direccion: '', activo: true,
        tiene_deuda: false, cuotas_originales: '', cuotas_pagadas: '', cuotas_restantes: '', monto_deuda: ''
    });

    useEffect(() => {
        fetchClientes();
    }, []);

    const fetchClientes = async () => {
        try {
            const response = await axios.get('/clientes');
            setClientes(response.data.data || response.data);
        } catch (error) {
            console.error('Error:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            // Preparar datos para enviar
            const datosEnvio = {
                nombre: formData.nombre,
                apellido: formData.apellido,
                dni: formData.dni,
                telefono: formData.telefono || null,
                email: formData.email || null,
                direccion: formData.direccion || null,
                activo: formData.activo !== false,
            };
            
            // Solo agregar campos de deuda si es nuevo cliente y tiene deuda
            if (!editing && formData.tiene_deuda) {
                datosEnvio.tiene_deuda = true;
                datosEnvio.monto_deuda = parseFloat(formData.monto_deuda) || 0;
                if (formData.cuotas_originales) {
                    datosEnvio.cuotas_originales = parseInt(formData.cuotas_originales) || null;
                }
                if (formData.cuotas_pagadas) {
                    datosEnvio.cuotas_pagadas = parseInt(formData.cuotas_pagadas) || 0;
                }
                if (formData.cuotas_restantes) {
                    datosEnvio.cuotas_restantes = parseInt(formData.cuotas_restantes) || null;
                }
            }
            
            if (editing) {
                await axios.put(`/clientes/${editing}`, datosEnvio);
            } else {
                await axios.post('/clientes', datosEnvio);
            }
            fetchClientes();
            setShowModal(false);
            setEditing(null);
            setFormData({ nombre: '', apellido: '', dni: '', telefono: '', email: '', direccion: '', activo: true,
                tiene_deuda: false, cuotas_originales: '', cuotas_pagadas: '', cuotas_restantes: '', monto_deuda: '' });
        } catch (error) {
            const errorMessage = error.response?.data?.message || 'Error al guardar';
            alert(errorMessage);
        }
    };

    if (loading) return <div>Cargando...</div>;

    const clientesList = Array.isArray(clientes) ? clientes : (clientes.data || []);

    return (
        <div>
            <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 sm:gap-0 mb-4 sm:mb-6">
                <h1 className="text-2xl sm:text-3xl font-bold">Clientes</h1>
                <button 
                    onClick={() => { setEditing(null); setShowModal(true); }} 
                    className="w-full sm:w-auto bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
                >
                    Nuevo Cliente
                </button>
            </div>
            <div className="bg-white rounded-lg shadow overflow-hidden">
                <div className="overflow-x-auto -mx-3 sm:mx-0">
                    <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                                <th className="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase hidden sm:table-cell">DNI</th>
                                <th className="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase hidden md:table-cell">Teléfono</th>
                                <th className="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                            </tr>
                        </thead>
                        <tbody className="bg-white divide-y divide-gray-200">
                            {clientesList.map((cliente) => (
                                <tr key={cliente.id} className="hover:bg-gray-50">
                                    <td className="px-3 sm:px-6 py-4">
                                        <div className="font-medium text-gray-900">{cliente.nombre} {cliente.apellido}</div>
                                        <div className="text-sm text-gray-500 sm:hidden">DNI: {cliente.dni}</div>
                                        <div className="text-sm text-gray-500 sm:hidden md:hidden">Tel: {cliente.telefono || '-'}</div>
                                    </td>
                                    <td className="px-3 sm:px-6 py-4 hidden sm:table-cell">{cliente.dni}</td>
                                    <td className="px-3 sm:px-6 py-4 hidden md:table-cell">{cliente.telefono || '-'}</td>
                                    <td className="px-3 sm:px-6 py-4">
                                        <div className="flex flex-col sm:flex-row gap-2">
                                            <button 
                                                onClick={() => { 
                                                    setEditing(cliente.id); 
                                                    setFormData({
                                                        ...cliente,
                                                        tiene_deuda: false,
                                                        cuotas_originales: '',
                                                        cuotas_pagadas: '',
                                                        cuotas_restantes: '',
                                                        monto_deuda: ''
                                                    }); 
                                                    setShowModal(true); 
                                                }} 
                                                className="text-blue-600 hover:text-blue-800 text-sm"
                                            >
                                                Editar
                                            </button>
                                            <button 
                                                onClick={() => axios.delete(`/clientes/${cliente.id}`).then(fetchClientes)} 
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
                            <h3 className="text-lg font-bold">{editing ? 'Editar' : 'Nuevo'} Cliente</h3>
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
                                placeholder="Apellido" 
                                value={formData.apellido} 
                                onChange={(e) => setFormData({...formData, apellido: e.target.value})} 
                                className="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                required 
                            />
                            <input 
                                type="text" 
                                placeholder="DNI" 
                                value={formData.dni} 
                                onChange={(e) => setFormData({...formData, dni: e.target.value})} 
                                className="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                required 
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
                            
                            {/* Sección de deuda existente - Solo al crear nuevo cliente */}
                            {!editing && (
                                <div className="border-t pt-3 mt-3">
                                    <div className="flex items-center mb-3">
                                        <input 
                                            type="checkbox" 
                                            id="tiene_deuda"
                                            checked={formData.tiene_deuda} 
                                            onChange={(e) => setFormData({...formData, tiene_deuda: e.target.checked})} 
                                            className="mr-2 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                        />
                                        <label htmlFor="tiene_deuda" className="text-sm font-medium text-gray-700">
                                            Cliente tiene deuda existente
                                        </label>
                                    </div>
                                
                                {formData.tiene_deuda && (
                                    <div className="bg-yellow-50 border border-yellow-200 rounded-md p-3 space-y-3">
                                        <h4 className="font-semibold text-gray-800 text-sm mb-2">Información de Deuda</h4>
                                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    Monto Total de Deuda
                                                </label>
                                                <input 
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    placeholder="0.00"
                                                    value={formData.monto_deuda} 
                                                    onChange={(e) => setFormData({...formData, monto_deuda: e.target.value})} 
                                                    className="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                                />
                                            </div>
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    Cuotas Originales
                                                </label>
                                                <input 
                                                    type="number"
                                                    min="1"
                                                    placeholder="Ej: 12"
                                                    value={formData.cuotas_originales} 
                                                    onChange={(e) => {
                                                        const originales = e.target.value;
                                                        const pagadas = parseFloat(formData.cuotas_pagadas) || 0;
                                                        const restantes = originales ? (parseFloat(originales) - pagadas) : '';
                                                        setFormData({...formData, cuotas_originales: originales, cuotas_restantes: restantes.toString()});
                                                    }} 
                                                    className="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                                />
                                            </div>
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    Cuotas Pagadas
                                                </label>
                                                <input 
                                                    type="number"
                                                    min="0"
                                                    placeholder="Ej: 3"
                                                    value={formData.cuotas_pagadas} 
                                                    onChange={(e) => {
                                                        const pagadas = e.target.value;
                                                        const originales = parseFloat(formData.cuotas_originales) || 0;
                                                        const restantes = originales > 0 ? (originales - (parseFloat(pagadas) || 0)) : '';
                                                        setFormData({...formData, cuotas_pagadas: pagadas, cuotas_restantes: restantes.toString()});
                                                    }} 
                                                    className="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                                />
                                            </div>
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    Cuotas Restantes
                                                </label>
                                                <input 
                                                    type="number"
                                                    min="0"
                                                    placeholder="Se calcula automáticamente"
                                                    value={formData.cuotas_restantes} 
                                                    onChange={(e) => setFormData({...formData, cuotas_restantes: e.target.value})} 
                                                    className="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500 bg-gray-50" 
                                                    readOnly
                                                />
                                            </div>
                                        </div>
                                    </div>
                                )}
                                </div>
                            )}
                            
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
