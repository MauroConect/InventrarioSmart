import React, { useState, useEffect } from 'react';
import axios from '../config/axios';
import { Link } from 'react-router-dom';
import ErrorBoundary from '../components/ErrorBoundary';
import VentasPorDiaChart from '../components/VentasPorDiaChart';
import ProductosMasVendidosChart from '../components/ProductosMasVendidosChart';
import VentasPorTipoPagoChart from '../components/VentasPorTipoPagoChart';

export default function Dashboard() {
    const [chequesProximos, setChequesProximos] = useState([]);
    const [loadingCheques, setLoadingCheques] = useState(true);
    const [estadisticas, setEstadisticas] = useState({
        caja_abierta: 0,
        total_productos: 0,
        total_clientes: 0,
        ventas_hoy: 0,
        monto_ventas_hoy: 0,
        productos_stock_bajo: 0,
        ventas_mes: 0,
        monto_ventas_mes: 0,
        deudas_pendientes: 0,
        cheques_proximos: 0,
        tiene_caja_abierta: false,
    });
    const [loadingEstadisticas, setLoadingEstadisticas] = useState(true);

    useEffect(() => {
        // Cargar datos iniciales
        fetchEstadisticas();
        fetchChequesProximos();

        // Actualizar datos cada 30 segundos
        const intervalEstadisticas = setInterval(() => {
            fetchEstadisticas();
        }, 30000); // 30 segundos

        const intervalCheques = setInterval(() => {
            fetchChequesProximos();
        }, 30000); // 30 segundos

        // Limpiar intervalos al desmontar el componente
        return () => {
            clearInterval(intervalEstadisticas);
            clearInterval(intervalCheques);
        };
    }, []);

    const fetchEstadisticas = async () => {
        try {
            setLoadingEstadisticas(true);
            const response = await axios.get('/dashboard/estadisticas');
            if (response.data) {
                setEstadisticas({
                    caja_abierta: response.data.caja_abierta || 0,
                    total_productos: response.data.total_productos || 0,
                    total_clientes: response.data.total_clientes || 0,
                    ventas_hoy: response.data.ventas_hoy || 0,
                    monto_ventas_hoy: response.data.monto_ventas_hoy || 0,
                    productos_stock_bajo: response.data.productos_stock_bajo || 0,
                    ventas_mes: response.data.ventas_mes || 0,
                    monto_ventas_mes: response.data.monto_ventas_mes || 0,
                    deudas_pendientes: response.data.deudas_pendientes || 0,
                    cheques_proximos: response.data.cheques_proximos || 0,
                    tiene_caja_abierta: response.data.tiene_caja_abierta || false,
                });
            }
        } catch (error) {
            console.error('Error al cargar estadísticas:', error);
            console.error('Detalles del error:', error.response?.data || error.message);
        } finally {
            setLoadingEstadisticas(false);
        }
    };

    const fetchChequesProximos = async () => {
        try {
            setLoadingCheques(true);
            const response = await axios.get('/cheques-proximos-vencer', { params: { dias: 30 } });
            setChequesProximos(Array.isArray(response.data) ? response.data.slice(0, 5) : []);
        } catch (error) {
            console.error('Error al cargar cheques próximos a vencer:', error);
        } finally {
            setLoadingCheques(false);
        }
    };

    const obtenerDiasHastaVencimiento = (fechaVencimiento) => {
        const hoy = new Date();
        const vencimiento = new Date(fechaVencimiento);
        const diferencia = Math.ceil((vencimiento - hoy) / (1000 * 60 * 60 * 24));
        return diferencia;
    };

    const obtenerColorDias = (dias) => {
        if (dias < 0) return 'text-red-600';
        if (dias <= 3) return 'text-orange-600';
        if (dias <= 7) return 'text-yellow-600';
        return 'text-green-600';
    };

    return (
        <div className="p-3 sm:p-4 lg:p-6">
            <h1 className="text-2xl sm:text-3xl font-bold mb-4 sm:mb-6">Dashboard</h1>
            
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-4 sm:mb-6">
                <div className="bg-white p-4 sm:p-6 rounded-lg shadow">
                    <h3 className="text-base sm:text-lg font-semibold text-gray-600">Caja Abierta</h3>
                    {loadingEstadisticas ? (
                        <div className="animate-pulse h-8 bg-gray-200 rounded mt-2"></div>
                    ) : (
                        <>
                            <p className="text-xl sm:text-2xl font-bold text-blue-600">
                                ${(estadisticas.caja_abierta || 0).toFixed(2)}
                            </p>
                            {!estadisticas.tiene_caja_abierta && (
                                <p className="text-xs text-gray-500 mt-1">No hay caja abierta</p>
                            )}
                        </>
                    )}
                </div>
                <div className="bg-white p-4 sm:p-6 rounded-lg shadow">
                    <h3 className="text-base sm:text-lg font-semibold text-gray-600">Productos</h3>
                    {loadingEstadisticas ? (
                        <div className="animate-pulse h-8 bg-gray-200 rounded mt-2"></div>
                    ) : (
                        <>
                            <p className="text-xl sm:text-2xl font-bold text-green-600">
                                {estadisticas.total_productos || 0}
                            </p>
                            {(estadisticas.productos_stock_bajo || 0) > 0 && (
                                <p className="text-xs text-orange-600 mt-1">
                                    {estadisticas.productos_stock_bajo || 0} con stock bajo
                                </p>
                            )}
                        </>
                    )}
                </div>
                <div className="bg-white p-4 sm:p-6 rounded-lg shadow">
                    <h3 className="text-base sm:text-lg font-semibold text-gray-600">Clientes</h3>
                    {loadingEstadisticas ? (
                        <div className="animate-pulse h-8 bg-gray-200 rounded mt-2"></div>
                    ) : (
                        <p className="text-xl sm:text-2xl font-bold text-purple-600">
                            {estadisticas.total_clientes || 0}
                        </p>
                    )}
                </div>
                <div className="bg-white p-4 sm:p-6 rounded-lg shadow">
                    <h3 className="text-base sm:text-lg font-semibold text-gray-600">Ventas Hoy</h3>
                    {loadingEstadisticas ? (
                        <div className="animate-pulse h-8 bg-gray-200 rounded mt-2"></div>
                    ) : (
                        <>
                            <p className="text-xl sm:text-2xl font-bold text-orange-600">
                                {estadisticas.ventas_hoy || 0}
                            </p>
                            <p className="text-xs text-gray-500 mt-1">
                                ${(estadisticas.monto_ventas_hoy || 0).toFixed(2)}
                            </p>
                        </>
                    )}
                </div>
            </div>

            {/* Estadísticas adicionales */}
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-4 sm:mb-6">
                <div className="bg-white p-4 sm:p-6 rounded-lg shadow">
                    <h3 className="text-base sm:text-lg font-semibold text-gray-600">Ventas del Mes</h3>
                    {loadingEstadisticas ? (
                        <div className="animate-pulse h-8 bg-gray-200 rounded mt-2"></div>
                    ) : (
                        <>
                            <p className="text-xl sm:text-2xl font-bold text-indigo-600">
                                {estadisticas.ventas_mes || 0}
                            </p>
                            <p className="text-xs text-gray-500 mt-1">
                                ${(estadisticas.monto_ventas_mes || 0).toFixed(2)}
                            </p>
                        </>
                    )}
                </div>
                <div className="bg-white p-4 sm:p-6 rounded-lg shadow">
                    <h3 className="text-base sm:text-lg font-semibold text-gray-600">Deudas Pendientes</h3>
                    {loadingEstadisticas ? (
                        <div className="animate-pulse h-8 bg-gray-200 rounded mt-2"></div>
                    ) : (
                        <p className="text-xl sm:text-2xl font-bold text-red-600">
                            ${(estadisticas.deudas_pendientes || 0).toFixed(2)}
                        </p>
                    )}
                </div>
                <div className="bg-white p-4 sm:p-6 rounded-lg shadow">
                    <h3 className="text-base sm:text-lg font-semibold text-gray-600">Stock Bajo</h3>
                    {loadingEstadisticas ? (
                        <div className="animate-pulse h-8 bg-gray-200 rounded mt-2"></div>
                    ) : (
                        <p className="text-xl sm:text-2xl font-bold text-yellow-600">
                            {estadisticas.productos_stock_bajo || 0}
                        </p>
                    )}
                </div>
                <div className="bg-white p-4 sm:p-6 rounded-lg shadow">
                    <h3 className="text-base sm:text-lg font-semibold text-gray-600">Cheques Próximos</h3>
                    {loadingEstadisticas ? (
                        <div className="animate-pulse h-8 bg-gray-200 rounded mt-2"></div>
                    ) : (
                        <p className="text-xl sm:text-2xl font-bold text-amber-600">
                            {estadisticas.cheques_proximos || 0}
                        </p>
                    )}
                </div>
            </div>

            {/* Gráficos */}
            <ErrorBoundary>
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6 mb-4 sm:mb-6">
                    <ErrorBoundary>
                        <VentasPorDiaChart />
                    </ErrorBoundary>
                    <ErrorBoundary>
                        <VentasPorTipoPagoChart />
                    </ErrorBoundary>
                </div>
            </ErrorBoundary>

            <ErrorBoundary>
                <div className="mb-4 sm:mb-6">
                    <ErrorBoundary>
                        <ProductosMasVendidosChart />
                    </ErrorBoundary>
                </div>
            </ErrorBoundary>

            {/* Sección de Cheques Próximos a Vencer */}
            <div className="bg-white rounded-lg shadow mb-4 sm:mb-6">
                <div className="p-3 sm:p-4 border-b flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
                    <h2 className="text-lg sm:text-xl font-semibold text-gray-800">Cheques Próximos a Vencer (30 días)</h2>
                    <Link
                        to="/cheques"
                        className="text-blue-600 hover:text-blue-800 text-sm font-medium whitespace-nowrap"
                    >
                        Ver todos →
                    </Link>
                </div>
                {loadingCheques ? (
                    <div className="p-8 text-center">
                        <div className="inline-block animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
                        <p className="mt-2 text-sm text-gray-500">Cargando cheques...</p>
                    </div>
                ) : chequesProximos.length === 0 ? (
                    <div className="p-8 text-center text-gray-500">
                        <p>No hay cheques próximos a vencer</p>
                    </div>
                ) : (
                    <div className="overflow-x-auto -mx-3 sm:mx-0">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nº Cheque</th>
                                    <th className="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Banco</th>
                                    <th className="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Monto</th>
                                    <th className="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase hidden sm:table-cell">Fecha Vencimiento</th>
                                    <th className="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Días</th>
                                    <th className="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase hidden md:table-cell">Cliente/Proveedor</th>
                                </tr>
                            </thead>
                            <tbody className="bg-white divide-y divide-gray-200">
                                {chequesProximos.map((cheque) => {
                                    const dias = obtenerDiasHastaVencimiento(cheque.fecha_vencimiento);
                                    return (
                                        <tr key={cheque.id} className="hover:bg-gray-50">
                                            <td className="px-3 sm:px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                {cheque.numero_cheque}
                                            </td>
                                            <td className="px-3 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {cheque.banco}
                                            </td>
                                            <td className="px-3 sm:px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                ${parseFloat(cheque.monto || 0).toFixed(2)}
                                            </td>
                                            <td className="px-3 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-500 hidden sm:table-cell">
                                                {new Date(cheque.fecha_vencimiento).toLocaleDateString()}
                                            </td>
                                            <td className="px-3 sm:px-6 py-4 whitespace-nowrap">
                                                <span className={`px-2 py-1 text-xs rounded-full font-bold ${
                                                    dias < 0 ? 'bg-red-100 text-red-800' :
                                                    dias <= 3 ? 'bg-orange-100 text-orange-800' :
                                                    dias <= 7 ? 'bg-yellow-100 text-yellow-800' :
                                                    'bg-green-100 text-green-800'
                                                }`}>
                                                    {dias < 0 ? `Vencido (${Math.abs(dias)} días)` : `${dias} días`}
                                                </span>
                                            </td>
                                            <td className="px-3 sm:px-6 py-4 text-sm text-gray-500 hidden md:table-cell">
                                                {cheque.cliente ? `${cheque.cliente.nombre} ${cheque.cliente.apellido}` : 
                                                 cheque.proveedor ? cheque.proveedor.nombre : '-'}
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </div>
    );
}
