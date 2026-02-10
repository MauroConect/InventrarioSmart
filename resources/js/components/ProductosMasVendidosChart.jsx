import React, { useState, useEffect } from 'react';
import axios from '../config/axios';
import {
    BarChart,
    Bar,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    Legend,
    ResponsiveContainer
} from 'recharts';

export default function ProductosMasVendidosChart() {
    const [data, setData] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [limite, setLimite] = useState(10);

    useEffect(() => {
        fetchData();
    }, [limite]);

    const fetchData = async () => {
        try {
            setLoading(true);
            setError(null);
            const hoy = new Date();
            const primerDiaMes = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
            const response = await axios.get('/dashboard/productos-mas-vendidos', {
                params: {
                    limite,
                    fecha_inicio: primerDiaMes.toISOString().split('T')[0],
                    fecha_fin: hoy.toISOString().split('T')[0]
                }
            });
            setData(Array.isArray(response.data) ? response.data : []);
        } catch (error) {
            console.error('Error al cargar productos más vendidos:', error);
            setError('Error al cargar datos');
            setData([]);
        } finally {
            setLoading(false);
        }
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
                <h3 className="text-lg font-semibold text-gray-800 mb-4">Productos Más Vendidos</h3>
                <div className="flex items-center justify-center h-64 text-red-500">
                    <p>{error}</p>
                </div>
            </div>
        );
    }

    if (data.length === 0) {
        return (
            <div className="bg-white p-6 rounded-lg shadow">
                <h3 className="text-lg font-semibold text-gray-800 mb-4">Productos Más Vendidos</h3>
                <div className="flex items-center justify-center h-64 text-gray-500">
                    <p>No hay datos disponibles</p>
                </div>
            </div>
        );
    }

    // Truncar nombres largos para el gráfico
    const dataFormateada = data.map(item => ({
        ...item,
        nombre_corto: item.nombre.length > 20 
            ? item.nombre.substring(0, 20) + '...' 
            : item.nombre
    }));

    return (
        <div className="bg-white p-4 sm:p-6 rounded-lg shadow">
            <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 gap-4">
                <h3 className="text-lg font-semibold text-gray-800">Productos Más Vendidos</h3>
                <select
                    value={limite}
                    onChange={(e) => setLimite(parseInt(e.target.value))}
                    className="px-3 py-1 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <option value={5}>Top 5</option>
                    <option value={10}>Top 10</option>
                    <option value={15}>Top 15</option>
                    <option value={20}>Top 20</option>
                </select>
            </div>
            <div style={{ width: '100%', height: '400px' }}>
                <ResponsiveContainer>
                    <BarChart
                        data={dataFormateada}
                        margin={{ top: 5, right: 30, left: 20, bottom: 60 }}
                    >
                    <CartesianGrid strokeDasharray="3 3" />
                    <XAxis
                        dataKey="nombre_corto"
                        angle={-45}
                        textAnchor="end"
                        height={100}
                        style={{ fontSize: '11px' }}
                    />
                    <YAxis
                        yAxisId="left"
                        style={{ fontSize: '12px' }}
                    />
                    <YAxis
                        yAxisId="right"
                        orientation="right"
                        style={{ fontSize: '12px' }}
                        tickFormatter={(value) => `$${value.toLocaleString()}`}
                    />
                    <Tooltip
                        formatter={(value, name) => {
                            if (name === 'cantidad_vendida') return [value, 'Cantidad Vendida'];
                            if (name === 'total_vendido') return [`$${parseFloat(value).toFixed(2)}`, 'Total Vendido'];
                            return [value, name];
                        }}
                        contentStyle={{ backgroundColor: 'white', border: '1px solid #ccc', borderRadius: '4px' }}
                    />
                    <Legend />
                    <Bar
                        yAxisId="left"
                        dataKey="cantidad_vendida"
                        fill="#10b981"
                        name="Cantidad Vendida"
                        radius={[4, 4, 0, 0]}
                    />
                    <Bar
                        yAxisId="right"
                        dataKey="total_vendido"
                        fill="#3b82f6"
                        name="Total Vendido ($)"
                        radius={[4, 4, 0, 0]}
                    />
                </BarChart>
                </ResponsiveContainer>
            </div>
        </div>
    );
}
