import React, { useState, useEffect } from 'react';
import axios from 'axios';

export default function DeudasClientes() {
    const [deudas, setDeudas] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetchDeudas();
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

    if (loading) return <div>Cargando...</div>;

    const deudasList = Array.isArray(deudas) ? deudas : (deudas.data || []);

    return (
        <div>
            <h1 className="text-3xl font-bold mb-6">Deudas de Clientes</h1>
            <div className="bg-white rounded-lg shadow overflow-hidden">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cliente</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Monto Total</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pendiente</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody className="bg-white divide-y divide-gray-200">
                        {deudasList.map((deuda) => (
                            <tr key={deuda.id}>
                                <td className="px-6 py-4">{deuda.cliente?.nombre} {deuda.cliente?.apellido}</td>
                                <td className="px-6 py-4">${deuda.monto_total?.toFixed(2)}</td>
                                <td className="px-6 py-4 font-bold">${deuda.monto_pendiente?.toFixed(2)}</td>
                                <td className="px-6 py-4">
                                    <span className={`px-2 py-1 rounded-full text-xs ${
                                        deuda.estado === 'pagada' ? 'bg-green-100 text-green-800' :
                                        deuda.estado === 'vencida' ? 'bg-red-100 text-red-800' :
                                        'bg-yellow-100 text-yellow-800'
                                    }`}>
                                        {deuda.estado}
                                    </span>
                                </td>
                                <td className="px-6 py-4">
                                    {deuda.monto_pendiente > 0 && (
                                        <button onClick={() => registrarPago(deuda)} className="text-green-600">Registrar Pago</button>
                                    )}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
