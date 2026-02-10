import React, { useState, useEffect } from 'react';
import axios from 'axios';

export default function CuentasCorrientes() {
    const [cuentas, setCuentas] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetchCuentas();
    }, []);

    const fetchCuentas = async () => {
        try {
            const response = await axios.get('/cuentas-corrientes');
            setCuentas(response.data.data || response.data);
        } catch (error) {
            console.error('Error:', error);
        } finally {
            setLoading(false);
        }
    };

    if (loading) return <div>Cargando...</div>;

    const cuentasList = Array.isArray(cuentas) ? cuentas : (cuentas.data || []);

    return (
        <div>
            <h1 className="text-3xl font-bold mb-6">Cuentas Corrientes</h1>
            <div className="bg-white rounded-lg shadow overflow-hidden">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cliente</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Saldo</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Límite Crédito</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody className="bg-white divide-y divide-gray-200">
                        {cuentasList.map((cuenta) => (
                            <tr key={cuenta.id}>
                                <td className="px-6 py-4">{cuenta.cliente?.nombre} {cuenta.cliente?.apellido}</td>
                                <td className={`px-6 py-4 font-bold ${cuenta.saldo < 0 ? 'text-red-600' : 'text-green-600'}`}>
                                    ${cuenta.saldo?.toFixed(2)}
                                </td>
                                <td className="px-6 py-4">${cuenta.limite_credito?.toFixed(2)}</td>
                                <td className="px-6 py-4">
                                    <button onClick={() => window.location.href = `/cuentas-corrientes/${cuenta.id}`} className="text-blue-600">Ver Movimientos</button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
