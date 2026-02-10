import React, { useState, useEffect } from 'react';
import axios from '../config/axios';
import {
    PieChart,
    Pie,
    Cell,
    ResponsiveContainer,
    Tooltip,
    Legend
} from 'recharts';

export default function VentasPorTipoPagoChart() {
    const [data, setData] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        fetchData();
    }, []);

    const fetchData = async () => {
        try {
            setLoading(true);
            setError(null);
            const hoy = new Date();
            const mes = hoy.getMonth() + 1;
            const ano = hoy.getFullYear();
            
            const response = await axios.get('/dashboard/ventas-por-tipo-pago', {
                params: { mes, ano }
            });
            
            setData(Array.isArray(response.data) ? response.data : []);
        } catch (error) {
            console.error('Error al cargar ventas por tipo de pago:', error);
            setError('Error al cargar datos');
            setData([]);
        } finally {
            setLoading(false);
        }
    };

    const COLORS = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'];

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
                <h3 className="text-lg font-semibold text-gray-800 mb-4">Ventas por Tipo de Pago</h3>
                <div className="flex items-center justify-center h-64 text-red-500">
                    <p>{error}</p>
                </div>
            </div>
        );
    }

    if (data.length === 0) {
        return (
            <div className="bg-white p-6 rounded-lg shadow">
                <h3 className="text-lg font-semibold text-gray-800 mb-4">Ventas por Tipo de Pago</h3>
                <div className="flex items-center justify-center h-64 text-gray-500">
                    <p>No hay datos disponibles</p>
                </div>
            </div>
        );
    }

    return (
        <div className="bg-white p-4 sm:p-6 rounded-lg shadow">
            <h3 className="text-lg font-semibold text-gray-800 mb-4">Ventas por Tipo de Pago (Mes Actual)</h3>
            <div style={{ width: '100%', height: '300px' }}>
                <ResponsiveContainer>
                    <PieChart>
                        <Pie
                            data={data}
                            cx="50%"
                            cy="50%"
                            labelLine={false}
                            label={({ name, percent }) => `${name}: ${(percent * 100).toFixed(0)}%`}
                            outerRadius={80}
                            fill="#8884d8"
                            dataKey="value"
                        >
                            {data.map((entry, index) => (
                                <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                            ))}
                        </Pie>
                        <Tooltip
                            formatter={(value) => `$${parseFloat(value).toFixed(2)}`}
                        />
                        <Legend />
                    </PieChart>
                </ResponsiveContainer>
            </div>
        </div>
    );
}
