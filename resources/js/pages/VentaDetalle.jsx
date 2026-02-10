import React, { useEffect, useState } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import axios from 'axios';

export default function VentaDetalle() {
    const { id } = useParams();
    const navigate = useNavigate();

    const [venta, setVenta] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');

    useEffect(() => {
        fetchVenta();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [id]);

    const fetchVenta = async () => {
        try {
            setLoading(true);
            setError('');
            const response = await axios.get(`/ventas/${id}`);
            setVenta(response.data);
        } catch (err) {
            console.error('Error al cargar venta:', err);
            setError(
                err.response?.data?.message ||
                    'Error al cargar la información de la venta.'
            );
        } finally {
            setLoading(false);
        }
    };

    const handleVolver = () => {
        navigate('/ventas');
    };

    if (loading) {
        return (
            <div className="p-6">
                <div className="flex items-center justify-center h-64">
                    <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                    <p className="ml-2 text-gray-600">Cargando venta...</p>
                </div>
            </div>
        );
    }

    if (error) {
        return (
            <div className="p-6">
                <div className="mb-4">
                    <button
                        onClick={handleVolver}
                        className="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300"
                    >
                        ← Volver a Ventas
                    </button>
                </div>
                <div className="p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                    {error}
                </div>
            </div>
        );
    }

    if (!venta) {
        return (
            <div className="p-6">
                <div className="mb-4">
                    <button
                        onClick={handleVolver}
                        className="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300"
                    >
                        ← Volver a Ventas
                    </button>
                </div>
                <p className="text-gray-600">No se encontró la venta solicitada.</p>
            </div>
        );
    }

    const totalItems = venta.items?.reduce(
        (acc, item) => acc + (item.cantidad || 0),
        0
    );

    return (
        <div className="p-6 space-y-6">
            <div className="flex justify-between items-center">
                <div>
                    <h1 className="text-3xl font-bold text-gray-800">
                        Venta {venta.numero_factura}
                    </h1>
                    <p className="text-gray-500">
                        Fecha:{' '}
                        {venta.fecha
                            ? new Date(venta.fecha).toLocaleString()
                            : '-'}
                    </p>
                </div>
                <div className="flex gap-2">
                    <button
                        onClick={handleVolver}
                        className="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300"
                    >
                        ← Volver a Ventas
                    </button>
                </div>
            </div>

            {/* Información principal */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div className="bg-white rounded-lg shadow p-4">
                    <h2 className="text-sm font-semibold text-gray-500 uppercase mb-2">
                        Cliente
                    </h2>
                    {venta.cliente ? (
                        <div className="space-y-1 text-sm">
                            <p className="font-medium text-gray-900">
                                {venta.cliente.nombre} {venta.cliente.apellido}
                            </p>
                            {venta.cliente.dni && (
                                <p className="text-gray-600">
                                    DNI: {venta.cliente.dni}
                                </p>
                            )}
                            {venta.cliente.telefono && (
                                <p className="text-gray-600">
                                    Teléfono: {venta.cliente.telefono}
                                </p>
                            )}
                        </div>
                    ) : (
                        <p className="text-sm text-gray-600">
                            Cliente genérico / consumidor final
                        </p>
                    )}
                </div>

                <div className="bg-white rounded-lg shadow p-4">
                    <h2 className="text-sm font-semibold text-gray-500 uppercase mb-2">
                        Caja
                    </h2>
                    {venta.caja ? (
                        <div className="space-y-1 text-sm">
                            <p className="font-medium text-gray-900">
                                {venta.caja.nombre || `Caja #${venta.caja.id}`}
                            </p>
                            <p className="text-gray-600">
                                Apertura:{' '}
                                {venta.caja.fecha_apertura
                                    ? new Date(
                                          venta.caja.fecha_apertura
                                      ).toLocaleString()
                                    : '-'}
                            </p>
                            {venta.caja.fecha_cierre && (
                                <p className="text-gray-600">
                                    Cierre:{' '}
                                    {new Date(
                                        venta.caja.fecha_cierre
                                    ).toLocaleString()}
                                </p>
                            )}
                        </div>
                    ) : (
                        <p className="text-sm text-gray-600">Sin caja asociada</p>
                    )}
                </div>

                <div className="bg-white rounded-lg shadow p-4">
                    <h2 className="text-sm font-semibold text-gray-500 uppercase mb-2">
                        Resumen
                    </h2>
                    <div className="space-y-1 text-sm">
                        <p className="flex justify-between">
                            <span className="text-gray-600">Items:</span>
                            <span className="font-medium text-gray-900">
                                {totalItems}
                            </span>
                        </p>
                        <p className="flex justify-between">
                            <span className="text-gray-600">Total bruto:</span>
                            <span className="font-medium text-gray-900">
                                $
                                {parseFloat(venta.total || 0).toFixed(2)}
                            </span>
                        </p>
                        <p className="flex justify-between">
                            <span className="text-gray-600">Descuento:</span>
                            <span className="font-medium text-gray-900">
                                $
                                {parseFloat(venta.descuento || 0).toFixed(2)}
                            </span>
                        </p>
                        <p className="flex justify-between text-lg font-bold">
                            <span className="text-gray-800">Total final:</span>
                            <span className="text-green-600">
                                $
                                {parseFloat(
                                    venta.total_final || 0
                                ).toFixed(2)}
                            </span>
                        </p>
                        <p className="flex justify-between mt-2">
                            <span className="text-gray-600">Tipo de pago:</span>
                            <span className="font-medium text-gray-900">
                                {venta.tipo_pago}
                            </span>
                        </p>
                        {venta.tipo_pago === 'mixto' && (
                            <>
                                {venta.monto_tarjeta && (
                                    <p className="flex justify-between">
                                        <span className="text-gray-600">Monto en tarjeta:</span>
                                        <span className="font-medium text-gray-900">
                                            ${parseFloat(venta.monto_tarjeta || 0).toFixed(2)}
                                        </span>
                                    </p>
                                )}
                                {venta.monto_efectivo && (
                                    <p className="flex justify-between">
                                        <span className="text-gray-600">Monto en efectivo:</span>
                                        <span className="font-medium text-gray-900">
                                            ${parseFloat(venta.monto_efectivo || 0).toFixed(2)}
                                        </span>
                                    </p>
                                )}
                            </>
                        )}
                        {(venta.cuotas && (venta.tipo_pago === 'mixto' || venta.tipo_pago === 'tarjeta')) && (
                            <>
                                <p className="flex justify-between">
                                    <span className="text-gray-600">Cuotas:</span>
                                    <span className="font-medium text-gray-900">
                                        {venta.cuotas}
                                    </span>
                                </p>
                                {venta.monto_cuota && (
                                    <p className="flex justify-between">
                                        <span className="text-gray-600">Monto por cuota:</span>
                                        <span className="font-medium text-blue-600">
                                            ${parseFloat(venta.monto_cuota || 0).toFixed(2)}
                                        </span>
                                    </p>
                                )}
                            </>
                        )}
                        <p className="flex justify-between">
                            <span className="text-gray-600">Estado:</span>
                            <span className="font-medium text-gray-900">
                                {venta.estado}
                            </span>
                        </p>
                    </div>
                </div>
            </div>

            {/* Items de la venta */}
            <div className="bg-white rounded-lg shadow">
                <div className="px-6 py-4 border-b">
                    <h2 className="text-lg font-semibold text-gray-800">
                        Items de la Venta
                    </h2>
                </div>
                <div className="overflow-x-auto">
                    <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                    Producto
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                    Precio Unitario
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                    Cantidad
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                    Subtotal
                                </th>
                            </tr>
                        </thead>
                        <tbody className="bg-white divide-y divide-gray-200">
                            {venta.items && venta.items.length > 0 ? (
                                venta.items.map((item) => (
                                    <tr key={item.id}>
                                        <td className="px-6 py-4 text-sm text-gray-900">
                                            <div className="font-medium">
                                                {item.producto
                                                    ? item.producto.nombre
                                                    : 'Producto eliminado'}
                                            </div>
                                            {item.producto?.codigo && (
                                                <div className="text-xs text-gray-500">
                                                    Código:{' '}
                                                    {item.producto.codigo}
                                                </div>
                                            )}
                                        </td>
                                        <td className="px-6 py-4 text-sm text-gray-900">
                                            $
                                            {parseFloat(
                                                item.precio_unitario || 0
                                            ).toFixed(2)}
                                        </td>
                                        <td className="px-6 py-4 text-sm text-gray-900">
                                            {item.cantidad}
                                        </td>
                                        <td className="px-6 py-4 text-sm font-semibold text-gray-900">
                                            $
                                            {parseFloat(
                                                item.subtotal || 0
                                            ).toFixed(2)}
                                        </td>
                                    </tr>
                                ))
                            ) : (
                                <tr>
                                    <td
                                        colSpan="4"
                                        className="px-6 py-4 text-center text-gray-500"
                                    >
                                        No hay items registrados para esta
                                        venta.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>

            {/* Adjuntos */}
            <div className="bg-white rounded-lg shadow">
                <div className="px-6 py-4 border-b flex justify-between items-center">
                    <h2 className="text-lg font-semibold text-gray-800">
                        Adjuntos
                    </h2>
                    <Link
                        to="/ventas"
                        className="text-sm text-blue-600 hover:text-blue-800"
                    >
                        Volver a listado
                    </Link>
                </div>
                {venta.adjuntos && venta.adjuntos.length > 0 ? (
                    <ul className="divide-y divide-gray-200">
                        {venta.adjuntos.map((adjunto) => (
                            <li
                                key={adjunto.id}
                                className="px-6 py-3 flex justify-between items-center"
                            >
                                <div>
                                    <p className="text-sm font-medium text-gray-900">
                                        {adjunto.nombre_original}
                                    </p>
                                    <p className="text-xs text-gray-500">
                                        {adjunto.mime || 'Archivo'}
                                        {adjunto.size
                                            ? ` · ${(adjunto.size / 1024).toFixed(
                                                  1
                                              )} KB`
                                            : ''}
                                    </p>
                                </div>
                                {adjunto.url && (
                                    <a
                                        href={adjunto.url}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="px-3 py-1 text-sm bg-blue-600 text-white rounded hover:bg-blue-700"
                                    >
                                        Ver / Descargar
                                    </a>
                                )}
                            </li>
                        ))}
                    </ul>
                ) : (
                    <div className="px-6 py-4 text-sm text-gray-500">
                        No hay archivos adjuntos para esta venta.
                    </div>
                )}
            </div>
        </div>
    );
}

