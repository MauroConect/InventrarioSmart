import React, { useState, useEffect } from 'react';
import axios from '../config/axios';
import {
    LineChart,
    Line,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    Legend,
    ResponsiveContainer
} from 'recharts';

export default function VentasPorDiaChart() {
    const [data, setData] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [mes, setMes] = useState(new Date().getMonth() + 1);
    const [ano, setAno] = useState(new Date().getFullYear());

    useEffect(() => {
        fetchData();
    }, [mes, ano]);

    const fetchData = async () => {
        try {
            setLoading(true);
            setError(null);
            const response = await axios.get('/dashboard/ventas-por-dia', {
                params: { mes, ano }
            });
            setData(Array.isArray(response.data) ? response.data : []);
        } catch (error) {
            console.error('Error al cargar ventas por día:', error);
            setError('Error al cargar datos');
            setData([]);
        } finally {
            setLoading(false);
        }
    };

    const formatFecha = (fecha) => {
        const date = new Date(fecha);
        return `${date.getDate()}/${date.getMonth() + 1}`;
    };

    if (loading) {
        return (
            <div className="bg-white p-6 rounded-lg shadow">
                <div className="flex items-center justify-center h-64">
                    <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                </div>
            </div>
        );
    }

    if (error) {
        return (
            <div className="bg-white p-6 rounded-lg shadow">
                <h3 className="text-lg font-semibold text-gray-800 mb-4">Ventas por Día</h3>
                <div className="flex items-center justify-center h-64 text-red-500">
                    <p>{error}</p>
                </div>
            </div>
        );
    }

    if (data.length === 0) {
        return (
            <div className="bg-white p-6 rounded-lg shadow">
                <h3 className="text-lg font-semibold text-gray-800 mb-4">Ventas por Día</h3>
                <div className="flex items-center justify-center h-64 text-gray-500">
                    <p>No hay datos disponibles para el período seleccionado</p>
                </div>
            </div>
        );
    }

    return (
        <div className="bg-white p-4 sm:p-6 rounded-lg shadow">
            <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 gap-4">
                <h3 className="text-lg font-semibold text-gray-800">Ventas por Día</h3>
                <div className="flex gap-2">
                    <select
                        value={mes}
                        onChange={(e) => setMes(parseInt(e.target.value))}
                        className="px-3 py-1 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        {Array.from({ length: 12 }, (_, i) => i + 1).map((m) => (
                            <option key={m} value={m}>
                                {new Date(2000, m - 1).toLocaleString('es-ES', { month: 'long' })}
                            </option>
                        ))}
                    </select>
                    <select
                        value={ano}
                        onChange={(e) => setAno(parseInt(e.target.value))}
                        className="px-3 py-1 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        {Array.from({ length: 5 }, (_, i) => new Date().getFullYear() - 2 + i).map((a) => (
                            <option key={a} value={a}>
                                {a}
                            </option>
                        ))}
                    </select>
                </div>
            </div>
            <div style={{ width: '100%', height: '300px' }}>
                <ResponsiveContainer>
                    <LineChart data={data} margin={{ top: 5, right: 30, left: 20, bottom: 5 }}>
                        <CartesianGrid strokeDasharray="3 3" />
                        <XAxis
                            dataKey="fecha"
                            tickFormatter={formatFecha}
                            style={{ fontSize: '12px' }}
                        />
                        <YAxis
                            style={{ fontSize: '12px' }}
                            tickFormatter={(value) => `$${value.toLocaleString()}`}
                        />
                        <Tooltip
                            formatter={(value, name) => {
                                if (name === 'total') return [`$${parseFloat(value).toFixed(2)}`, 'Total'];
                                if (name === 'cantidad') return [value, 'Cantidad'];
                                return [value, name];
                            }}
                            labelFormatter={(label) => `Fecha: ${formatFecha(label)}`}
                        />
                        <Legend />
                        <Line
                            type="monotone"
                            dataKey="total"
                            stroke="#3b82f6"
                            strokeWidth={2}
                            name="Total Ventas"
                            dot={{ r: 4 }}
                            activeDot={{ r: 6 }}
                        />
                        <Line
                            type="monotone"
                            dataKey="cantidad"
                            stroke="#10b981"
                            strokeWidth={2}
                            name="Cantidad"
                            dot={{ r: 4 }}
                        />
                    </LineChart>
                </ResponsiveContainer>
            </div>
        </div>
    );
}
