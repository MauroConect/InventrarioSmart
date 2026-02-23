import React, { useState, useEffect } from 'react';
import axios from 'axios';

export default function DeudasClientes() {
    const [deudas, setDeudas] = useState([]);
    const [clientes, setClientes] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showModal, setShowModal] = useState(false);
    const [formData, setFormData] = useState({
        cliente_id: '',
        monto_total: '',
        cuotas_originales: '',
        cuotas_pagadas: '',
        cuotas_restantes: '',
        fecha_vencimiento: '',
        observaciones: ''
    });

    useEffect(() => {
        fetchDeudas();
        fetchClientes();
    }, []);

    const fetchDeudas = async () => {
        try {
            const response = await axios.get('/deudas-clientes');
            setDeudas(response.data.data || response.data);
        } catch (error) {
            console.error('Error:', error);
        } finally {
            setLoading(false);
        }
    };

    const fetchClientes = async () => {
        try {
            const response = await axios.get('/clientes');
            const clientesData = response.data.data || response.data || [];
            setClientes(Array.isArray(clientesData) ? clientesData.filter(c => c.activo !== false) : []);
        } catch (error) {
            console.error('Error al cargar clientes:', error);
        }
    };

    const registrarPago = async (deuda) => {
        const monto = parseFloat(prompt('Ingrese el monto a pagar:'));
        if (!monto || monto <= 0) return;
        try {
            await axios.post(`/deudas-clientes/${deuda.id}/pago`, { monto });
            fetchDeudas();
        } catch (error) {
            alert('Error al registrar pago');
        }
    };

    const handleSubmitDeuda = async (e) => {
        e.preventDefault();
        try {
            if (!formData.cliente_id || !formData.monto_total) {
                alert('Debe seleccionar un cliente e ingresar el monto total');
                return;
            }

            const datosEnvio = {
                cliente_id: formData.cliente_id,
                monto_total: parseFloat(formData.monto_total),
                fecha_vencimiento: formData.fecha_vencimiento || null,
                observaciones: formData.observaciones || null,
            };

            if (formData.cuotas_originales) {
                datosEnvio.cuotas_originales = parseInt(formData.cuotas_originales);
            }
            if (formData.cuotas_pagadas) {
                datosEnvio.cuotas_pagadas = parseInt(formData.cuotas_pagadas) || 0;
            }
            if (formData.cuotas_restantes) {
                datosEnvio.cuotas_restantes = parseInt(formData.cuotas_restantes);
            }

            await axios.post('/deudas-clientes', datosEnvio);
            fetchDeudas();
            setShowModal(false);
            setFormData({
                cliente_id: '',
                monto_total: '',
                cuotas_originales: '',
                cuotas_pagadas: '',
                cuotas_restantes: '',
                fecha_vencimiento: '',
                observaciones: ''
            });
        } catch (error) {
            const errorMessage = error.response?.data?.message || 'Error al registrar deuda';
            alert(errorMessage);
        }
    };

    const calcularYActualizarCuotasRestantes = (originales, pagadas) => {
        if (originales && originales !== '') {
            const orig = parseFloat(originales) || 0;
            const pag = parseFloat(pagadas) || 0;
            if (orig > 0) {
                const restantes = Math.max(0, orig - pag);
                return restantes.toString();
            }
        }
        return '';
    };

    if (loading) return <div>Cargando...</div>;

    const deudasList = Array.isArray(deudas) ? deudas : (deudas.data || []);

    return (
        <div className="p-3 sm:p-4 lg:p-6">
            <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 sm:gap-0 mb-4 sm:mb-6">
                <h1 className="text-2xl sm:text-3xl font-bold">Deudas de Clientes</h1>
                <button
                    onClick={() => setShowModal(true)}
                    className="w-full sm:w-auto bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
                >
                    + Nueva Deuda
                </button>
            </div>

            <div className="bg-white rounded-lg shadow overflow-hidden">
                <div className="overflow-x-auto -mx-3 sm:mx-0">
                    <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cliente</th>
                                <th className="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Monto Total</th>
                                <th className="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase hidden md:table-cell">Cuotas</th>
                                <th className="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pendiente</th>
                                <th className="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase hidden lg:table-cell">Estado</th>
                                <th className="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                            </tr>
                        </thead>
                        <tbody className="bg-white divide-y divide-gray-200">
                            {deudasList.length === 0 ? (
                                <tr>
                                    <td colSpan="6" className="px-3 sm:px-6 py-4 text-center text-gray-500">
                                        No hay deudas registradas.
                                    </td>
                                </tr>
                            ) : (
                                deudasList.map((deuda) => (
                                    <tr key={deuda.id} className="hover:bg-gray-50">
                                        <td className="px-3 sm:px-6 py-4">
                                            <div className="font-medium text-gray-900">{deuda.cliente?.nombre} {deuda.cliente?.apellido}</div>
                                        </td>
                                        <td className="px-3 sm:px-6 py-4">${parseFloat(deuda.monto_total || 0).toFixed(2)}</td>
                                        <td className="px-3 sm:px-6 py-4 hidden md:table-cell">
                                            {deuda.cuotas_originales ? (
                                                <div className="text-sm">
                                                    <div>Total: {deuda.cuotas_originales}</div>
                                                    <div className="text-green-600">Pagadas: {deuda.cuotas_pagadas || 0}</div>
                                                    <div className="text-red-600">Restantes: {deuda.cuotas_restantes || 0}</div>
                                                </div>
                                            ) : (
                                                <span className="text-gray-400">-</span>
                                            )}
                                        </td>
                                        <td className="px-3 sm:px-6 py-4 font-bold">${parseFloat(deuda.monto_pendiente || 0).toFixed(2)}</td>
                                        <td className="px-3 sm:px-6 py-4 hidden lg:table-cell">
                                            <span className={`px-2 py-1 rounded-full text-xs ${
                                                deuda.estado === 'pagada' ? 'bg-green-100 text-green-800' :
                                                deuda.estado === 'vencida' ? 'bg-red-100 text-red-800' :
                                                'bg-yellow-100 text-yellow-800'
                                            }`}>
                                                {deuda.estado}
                                            </span>
                                        </td>
                                        <td className="px-3 sm:px-6 py-4">
                                            {deuda.monto_pendiente > 0 && (
                                                <button 
                                                    onClick={() => registrarPago(deuda)} 
                                                    className="text-green-600 hover:text-green-800 text-sm"
                                                >
                                                    Registrar Pago
                                                </button>
                                            )}
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
            </div>

            {/* Modal Nueva Deuda */}
            {showModal && (
                <div className="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50 p-4">
                    <div className="bg-white rounded-lg w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                        <div className="sticky top-0 bg-white border-b px-4 sm:px-6 py-4 flex justify-between items-center">
                            <h3 className="text-lg font-bold">Nueva Deuda</h3>
                            <button
                                onClick={() => {
                                    setShowModal(false);
                                    setFormData({
                                        cliente_id: '',
                                        monto_total: '',
                                        cuotas_originales: '',
                                        cuotas_pagadas: '',
                                        cuotas_restantes: '',
                                        fecha_vencimiento: '',
                                        observaciones: ''
                                    });
                                }}
                                className="text-gray-500 hover:text-gray-700 text-2xl focus:outline-none"
                            >
                                ×
                            </button>
                        </div>
                        <form onSubmit={handleSubmitDeuda} className="p-4 sm:p-6 space-y-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Cliente <span className="text-red-500">*</span>
                                </label>
                                <select
                                    value={formData.cliente_id}
                                    onChange={(e) => setFormData({...formData, cliente_id: e.target.value})}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md"
                                    required
                                >
                                    <option value="">Seleccionar cliente...</option>
                                    {clientes.map((cliente) => (
                                        <option key={cliente.id} value={cliente.id}>
                                            {cliente.nombre} {cliente.apellido} - DNI: {cliente.dni}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Monto Total <span className="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        value={formData.monto_total}
                                        onChange={(e) => setFormData({...formData, monto_total: e.target.value})}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-md"
                                        placeholder="0.00"
                                        required
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Fecha de Vencimiento
                                    </label>
                                    <input
                                        type="date"
                                        value={formData.fecha_vencimiento}
                                        onChange={(e) => setFormData({...formData, fecha_vencimiento: e.target.value})}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-md"
                                    />
                                </div>
                            </div>

                            <div className="bg-blue-50 border border-blue-200 rounded-md p-3 sm:p-4">
                                <h4 className="font-semibold text-gray-800 mb-3 text-sm sm:text-base">Información de Cuotas (Opcional)</h4>
                                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Cuotas Originales
                                        </label>
                                        <input
                                            type="number"
                                            min="1"
                                            value={formData.cuotas_originales}
                                            onChange={(e) => {
                                                const originales = e.target.value;
                                                const restantes = calcularYActualizarCuotasRestantes(originales, formData.cuotas_pagadas);
                                                setFormData({...formData, cuotas_originales: originales, cuotas_restantes: restantes});
                                            }}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-md"
                                            placeholder="Ej: 12"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Cuotas Pagadas
                                        </label>
                                        <input
                                            type="number"
                                            min="0"
                                            value={formData.cuotas_pagadas}
                                            onChange={(e) => {
                                                const pagadas = e.target.value;
                                                const restantes = calcularYActualizarCuotasRestantes(formData.cuotas_originales, pagadas);
                                                setFormData({...formData, cuotas_pagadas: pagadas, cuotas_restantes: restantes});
                                            }}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-md"
                                            placeholder="Ej: 3"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Cuotas Restantes
                                        </label>
                                        <input
                                            type="number"
                                            min="0"
                                            value={formData.cuotas_restantes}
                                            onChange={(e) => setFormData({...formData, cuotas_restantes: e.target.value})}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50"
                                            placeholder="Se calcula automáticamente"
                                            readOnly
                                        />
                                    </div>
                                </div>
                                {formData.cuotas_originales && parseFloat(formData.cuotas_originales) > 0 && formData.monto_total && (
                                    <div className="mt-3 text-xs text-gray-600">
                                        <div>
                                            Monto por cuota: <span className="font-semibold text-blue-600">
                                                ${(parseFloat(formData.monto_total) / parseFloat(formData.cuotas_originales)).toFixed(2)}
                                            </span>
                                        </div>
                                        {formData.cuotas_pagadas && parseFloat(formData.cuotas_pagadas) > 0 && (
                                            <div>
                                                Monto pagado: <span className="font-semibold text-green-600">
                                                    ${((parseFloat(formData.monto_total) / parseFloat(formData.cuotas_originales)) * parseFloat(formData.cuotas_pagadas)).toFixed(2)}
                                                </span>
                                            </div>
                                        )}
                                    </div>
                                )}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Observaciones
                                </label>
                                <textarea
                                    value={formData.observaciones}
                                    onChange={(e) => setFormData({...formData, observaciones: e.target.value})}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md"
                                    rows="3"
                                    placeholder="Notas adicionales sobre la deuda..."
                                />
                            </div>

                            <div className="flex flex-col sm:flex-row justify-end gap-2 pt-4 border-t">
                                <button
                                    type="button"
                                    onClick={() => {
                                        setShowModal(false);
                                        setFormData({
                                            cliente_id: '',
                                            monto_total: '',
                                            cuotas_originales: '',
                                            cuotas_pagadas: '',
                                            cuotas_restantes: '',
                                            fecha_vencimiento: '',
                                            observaciones: ''
                                        });
                                    }}
                                    className="w-full sm:w-auto px-6 py-2 border border-gray-300 rounded-md hover:bg-gray-50"
                                >
                                    Cancelar
                                </button>
                                <button
                                    type="submit"
                                    className="w-full sm:w-auto px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
                                >
                                    Guardar Deuda
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
}
