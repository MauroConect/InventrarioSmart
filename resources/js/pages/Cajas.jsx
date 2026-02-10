import React, { useState, useEffect } from 'react';
import axios from 'axios';

export default function Cajas() {
    const [cajas, setCajas] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showModal, setShowModal] = useState(false);
    const [showCerrarModal, setShowCerrarModal] = useState(false);
    const [cajaSeleccionada, setCajaSeleccionada] = useState(null);
    const [montoApertura, setMontoApertura] = useState(0);
    const [nombreCaja, setNombreCaja] = useState('');
    const [resumenCierre, setResumenCierre] = useState(null);
    const [cargandoResumen, setCargandoResumen] = useState(false);
    const [montoReal, setMontoReal] = useState('');
    const [observaciones, setObservaciones] = useState('');
    const [error, setError] = useState('');
    const [success, setSuccess] = useState('');
    const [cerrando, setCerrando] = useState(false);

    useEffect(() => {
        fetchCajas();
    }, []);

    useEffect(() => {
        if (showCerrarModal && cajaSeleccionada) {
            fetchResumenCierre(cajaSeleccionada.id);
        }
    }, [showCerrarModal, cajaSeleccionada]);

    const fetchCajas = async () => {
        try {
            setLoading(true);
            const response = await axios.get('/cajas');
            setCajas(response.data.data || response.data);
        } catch (error) {
            console.error('Error:', error);
            setError('Error al cargar las cajas');
        } finally {
            setLoading(false);
        }
    };

    const fetchResumenCierre = async (cajaId) => {
        try {
            setCargandoResumen(true);
            setError('');
            const response = await axios.get(`/cajas/${cajaId}/resumen-cierre`);
            setResumenCierre(response.data);
            setMontoReal(response.data.resumen.monto_esperado.toFixed(2));
        } catch (error) {
            console.error('Error al cargar resumen:', error);
            setError(error.response?.data?.message || 'Error al cargar el resumen de cierre');
            setShowCerrarModal(false);
        } finally {
            setCargandoResumen(false);
        }
    };

    const abrirCaja = async (e) => {
        e.preventDefault();
        try {
            setError('');
            setSuccess('');
            await axios.post('/cajas', { 
                nombre: nombreCaja || null,
                monto_apertura: montoApertura 
            });
            setSuccess('Caja abierta correctamente');
            fetchCajas();
            setShowModal(false);
            setMontoApertura(0);
            setNombreCaja('');
            setTimeout(() => setSuccess(''), 3000);
        } catch (error) {
            setError(error.response?.data?.message || 'Error al abrir caja');
        }
    };

    const handleAbrirModalCierre = (caja) => {
        setCajaSeleccionada(caja);
        setShowCerrarModal(true);
        setError('');
        setSuccess('');
        setMontoReal('');
        setObservaciones('');
    };

    const cerrarCaja = async () => {
        if (!montoReal || parseFloat(montoReal) < 0) {
            setError('Debe ingresar un monto real válido');
            return;
        }

        try {
            setCerrando(true);
            setError('');
            setSuccess('');
            
            await axios.post(`/cajas/${cajaSeleccionada.id}/cerrar`, {
                monto_real: parseFloat(montoReal),
                observaciones: observaciones || null,
            });

            setSuccess('Caja cerrada correctamente');
            await fetchCajas();
            setShowCerrarModal(false);
            setCajaSeleccionada(null);
            setResumenCierre(null);
            setMontoReal('');
            setObservaciones('');
            setTimeout(() => setSuccess(''), 3000);
        } catch (error) {
            console.error('Error al cerrar caja:', error);
            setError(error.response?.data?.message || 'Error al cerrar la caja');
        } finally {
            setCerrando(false);
        }
    };

    const calcularDiferencia = () => {
        if (!resumenCierre || !montoReal) return 0;
        const esperado = resumenCierre.resumen.monto_esperado;
        const real = parseFloat(montoReal) || 0;
        return real - esperado;
    };

    if (loading) {
        return (
            <div className="p-6">
                <div className="flex items-center justify-center h-64">
                    <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                    <p className="ml-2 text-gray-600">Cargando cajas...</p>
                </div>
            </div>
        );
    }

    const cajasList = Array.isArray(cajas) ? cajas : (cajas.data || []);
    const cajasAbiertas = cajasList.filter(c => c.estado === 'abierta');
    const diferencia = calcularDiferencia();

    return (
        <div className="p-6">
            <div className="flex justify-between items-center mb-6">
                <h1 className="text-3xl font-bold text-gray-800">Cajas</h1>
                <button
                    onClick={() => {
                        setShowModal(true);
                        setError('');
                        setSuccess('');
                        setNombreCaja('');
                        setMontoApertura(0);
                    }}
                    className="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500"
                >
                    + Abrir Caja
                </button>
            </div>

            {error && (
                <div className="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                    {error}
                </div>
            )}

            {success && (
                <div className="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                    {success}
                </div>
            )}

            {cajasAbiertas.length > 0 && (
                <div className="mb-4 space-y-2">
                    {cajasAbiertas.map((caja) => (
                        <div key={caja.id} className="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded flex justify-between items-center">
                            <div>
                                <strong>Caja Abierta:</strong> {caja.nombre || `Caja #${caja.id}`} - 
                                Monto de apertura: ${parseFloat(caja.monto_apertura || 0).toFixed(2)} - 
                                Fecha: {new Date(caja.fecha_apertura).toLocaleString()}
                            </div>
                            <button
                                onClick={() => handleAbrirModalCierre(caja)}
                                className="ml-4 bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 text-sm focus:outline-none focus:ring-2 focus:ring-red-500"
                            >
                                Cerrar Caja
                            </button>
                        </div>
                    ))}
                </div>
            )}

            <div className="bg-white rounded-lg shadow overflow-hidden">
                <div className="overflow-x-auto">
                    <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha Apertura</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Monto Apertura</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha Cierre</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Monto Cierre</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Diferencia</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                            </tr>
                        </thead>
                        <tbody className="bg-white divide-y divide-gray-200">
                            {cajasList.length === 0 ? (
                                <tr>
                                    <td colSpan="7" className="px-6 py-4 text-center text-gray-500">
                                        No hay cajas registradas
                                    </td>
                                </tr>
                            ) : (
                                cajasList.map((caja) => (
                                    <tr key={caja.id} className="hover:bg-gray-50">
                                        <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {caja.nombre || `Caja #${caja.id}`}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {new Date(caja.fecha_apertura).toLocaleString()}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            ${parseFloat(caja.monto_apertura || 0).toFixed(2)}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {caja.fecha_cierre ? new Date(caja.fecha_cierre).toLocaleString() : '-'}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {caja.monto_cierre ? `$${parseFloat(caja.monto_cierre).toFixed(2)}` : '-'}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm">
                                            {caja.diferencia !== null && (
                                                <span className={`font-medium ${parseFloat(caja.diferencia) >= 0 ? 'text-green-600' : 'text-red-600'}`}>
                                                    ${parseFloat(caja.diferencia).toFixed(2)}
                                                </span>
                                            )}
                                            {caja.diferencia === null && '-'}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <span className={`px-2 py-1 text-xs rounded-full ${
                                                caja.estado === 'abierta' 
                                                    ? 'bg-green-100 text-green-800' 
                                                    : 'bg-gray-100 text-gray-800'
                                            }`}>
                                                {caja.estado}
                                            </span>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
            </div>

            {/* Modal Abrir Caja */}
            {showModal && (
                <div className="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4">
                    <div className="relative bg-white rounded-lg shadow-xl w-full max-w-md">
                        <div className="p-6">
                            <h3 className="text-xl font-bold text-gray-800 mb-4">Abrir Caja</h3>
                            {error && (
                                <div className="mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded text-sm">
                                    {error}
                                </div>
                            )}
                            <form onSubmit={abrirCaja}>
                                <div className="mb-4">
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Nombre de la Caja (opcional)
                                    </label>
                                    <input
                                        type="text"
                                        placeholder="Ej: Caja Principal, Caja Secundaria, etc."
                                        value={nombreCaja}
                                        onChange={(e) => setNombreCaja(e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                                        maxLength={255}
                                    />
                                    <p className="mt-1 text-xs text-gray-500">
                                        Si no se especifica, se generará un nombre automático
                                    </p>
                                </div>
                                <div className="mb-4">
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Monto de Apertura <span className="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        placeholder="0.00"
                                        value={montoApertura}
                                        onChange={(e) => setMontoApertura(parseFloat(e.target.value) || 0)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                                        required
                                        autoFocus
                                    />
                                </div>
                                <div className="flex justify-end gap-2 pt-4">
                                    <button
                                        type="button"
                                        onClick={() => {
                                            setShowModal(false);
                                            setError('');
                                            setMontoApertura(0);
                                            setNombreCaja('');
                                        }}
                                        className="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500"
                                    >
                                        Cancelar
                                    </button>
                                    <button
                                        type="submit"
                                        className="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500"
                                    >
                                        Abrir Caja
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            )}

            {/* Modal Revisión y Cierre de Caja */}
            {showCerrarModal && (
                <div className="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 p-4">
                    <div className="relative bg-white rounded-lg shadow-xl w-full max-w-6xl mx-auto my-8 max-h-[90vh] overflow-y-auto">
                        <div className="sticky top-0 bg-white border-b px-6 py-4 flex justify-between items-center z-10">
                            <h3 className="text-2xl font-bold text-gray-800">Revisión de Cierre de Caja</h3>
                            <button
                                onClick={() => {
                                    setShowCerrarModal(false);
                                    setCajaSeleccionada(null);
                                    setResumenCierre(null);
                                    setError('');
                                }}
                                className="text-gray-500 hover:text-gray-700 text-2xl"
                            >
                                ×
                            </button>
                        </div>

                        {cargandoResumen ? (
                            <div className="p-12 text-center">
                                <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                                <p className="mt-2 text-gray-600">Cargando resumen...</p>
                            </div>
                        ) : resumenCierre ? (
                            <div className="p-6">
                                {error && (
                                    <div className="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                                        {error}
                                    </div>
                                )}

                                {/* Información de la Caja */}
                                <div className="mb-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
                                    <h4 className="font-semibold text-lg mb-2 text-blue-900">Información de la Caja</h4>
                                    <div className="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                        <div>
                                            <span className="text-gray-600">Nombre:</span>
                                            <p className="font-medium">{resumenCierre.caja.nombre || `Caja #${resumenCierre.caja.id}`}</p>
                                        </div>
                                        <div>
                                            <span className="text-gray-600">Fecha Apertura:</span>
                                            <p className="font-medium">{new Date(resumenCierre.caja.fecha_apertura).toLocaleString()}</p>
                                        </div>
                                        <div>
                                            <span className="text-gray-600">Usuario:</span>
                                            <p className="font-medium">{resumenCierre.caja.usuario?.name || 'N/A'}</p>
                                        </div>
                                        <div>
                                            <span className="text-gray-600">Monto Apertura:</span>
                                            <p className="font-medium text-lg">${parseFloat(resumenCierre.resumen.monto_apertura).toFixed(2)}</p>
                                        </div>
                                    </div>
                                </div>

                                {/* Resumen Financiero */}
                                <div className="mb-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                    <div className="p-4 bg-green-50 rounded-lg border border-green-200">
                                        <h5 className="text-sm font-medium text-green-700 mb-1">Total Ventas</h5>
                                        <p className="text-2xl font-bold text-green-900">
                                            ${parseFloat(resumenCierre.resumen.total_ventas).toFixed(2)}
                                        </p>
                                        <p className="text-xs text-green-600 mt-1">
                                            {resumenCierre.resumen.cantidad_ventas} venta(s)
                                        </p>
                                    </div>
                                    <div className="p-4 bg-blue-50 rounded-lg border border-blue-200">
                                        <h5 className="text-sm font-medium text-blue-700 mb-1">Total Ingresos</h5>
                                        <p className="text-2xl font-bold text-blue-900">
                                            ${parseFloat(resumenCierre.resumen.total_ingresos).toFixed(2)}
                                        </p>
                                        <p className="text-xs text-blue-600 mt-1">
                                            {resumenCierre.resumen.cantidad_ingresos} movimiento(s)
                                        </p>
                                    </div>
                                    <div className="p-4 bg-red-50 rounded-lg border border-red-200">
                                        <h5 className="text-sm font-medium text-red-700 mb-1">Total Egresos</h5>
                                        <p className="text-2xl font-bold text-red-900">
                                            ${parseFloat(resumenCierre.resumen.total_egresos).toFixed(2)}
                                        </p>
                                        <p className="text-xs text-red-600 mt-1">
                                            {resumenCierre.resumen.cantidad_egresos} movimiento(s)
                                        </p>
                                    </div>
                                    <div className="p-4 bg-purple-50 rounded-lg border border-purple-200">
                                        <h5 className="text-sm font-medium text-purple-700 mb-1">Monto Esperado</h5>
                                        <p className="text-2xl font-bold text-purple-900">
                                            ${parseFloat(resumenCierre.resumen.monto_esperado).toFixed(2)}
                                        </p>
                                    </div>
                                </div>

                                {/* Cálculo de Cierre */}
                                <div className="mb-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
                                    <h4 className="font-semibold text-lg mb-4 text-gray-800">Cálculo de Cierre</h4>
                                    <div className="space-y-3">
                                        <div className="flex justify-between text-sm">
                                            <span className="text-gray-600">Monto Apertura:</span>
                                            <span className="font-medium">${parseFloat(resumenCierre.resumen.monto_apertura).toFixed(2)}</span>
                                        </div>
                                        <div className="flex justify-between text-sm">
                                            <span className="text-gray-600">+ Total Ventas:</span>
                                            <span className="font-medium text-green-600">+ ${parseFloat(resumenCierre.resumen.total_ventas).toFixed(2)}</span>
                                        </div>
                                        <div className="flex justify-between text-sm">
                                            <span className="text-gray-600">+ Total Ingresos:</span>
                                            <span className="font-medium text-blue-600">+ ${parseFloat(resumenCierre.resumen.total_ingresos).toFixed(2)}</span>
                                        </div>
                                        <div className="flex justify-between text-sm">
                                            <span className="text-gray-600">- Total Egresos:</span>
                                            <span className="font-medium text-red-600">- ${parseFloat(resumenCierre.resumen.total_egresos).toFixed(2)}</span>
                                        </div>
                                        <div className="border-t pt-2 flex justify-between font-bold">
                                            <span>Monto Esperado:</span>
                                            <span className="text-purple-600">${parseFloat(resumenCierre.resumen.monto_esperado).toFixed(2)}</span>
                                        </div>
                                    </div>
                                </div>

                                {/* Lista de Ventas */}
                                {resumenCierre.ventas && resumenCierre.ventas.length > 0 && (
                                    <div className="mb-6">
                                        <h4 className="font-semibold text-lg mb-3 text-gray-800">Ventas Realizadas</h4>
                                        <div className="overflow-x-auto">
                                            <table className="min-w-full divide-y divide-gray-200 text-sm">
                                                <thead className="bg-gray-50">
                                                    <tr>
                                                        <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Factura</th>
                                                        <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                                                        <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Cliente</th>
                                                        <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                                                    </tr>
                                                </thead>
                                                <tbody className="bg-white divide-y divide-gray-200">
                                                    {resumenCierre.ventas.map((venta) => (
                                                        <tr key={venta.id}>
                                                            <td className="px-4 py-2">{venta.numero_factura || '-'}</td>
                                                            <td className="px-4 py-2">{new Date(venta.fecha).toLocaleString()}</td>
                                                            <td className="px-4 py-2">{venta.cliente?.nombre || 'Cliente General'}</td>
                                                            <td className="px-4 py-2 font-medium">${parseFloat(venta.total_final || 0).toFixed(2)}</td>
                                                        </tr>
                                                    ))}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                )}

                                {/* Lista de Movimientos */}
                                {resumenCierre.movimientos && resumenCierre.movimientos.length > 0 && (
                                    <div className="mb-6">
                                        <h4 className="font-semibold text-lg mb-3 text-gray-800">Movimientos de Caja</h4>
                                        <div className="overflow-x-auto">
                                            <table className="min-w-full divide-y divide-gray-200 text-sm">
                                                <thead className="bg-gray-50">
                                                    <tr>
                                                        <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                                                        <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                                                        <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Concepto</th>
                                                        <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Monto</th>
                                                    </tr>
                                                </thead>
                                                <tbody className="bg-white divide-y divide-gray-200">
                                                    {resumenCierre.movimientos.map((movimiento) => (
                                                        <tr key={movimiento.id}>
                                                            <td className="px-4 py-2">{new Date(movimiento.created_at).toLocaleString()}</td>
                                                            <td className="px-4 py-2">
                                                                <span className={`px-2 py-1 text-xs rounded-full ${
                                                                    movimiento.tipo === 'ingreso' 
                                                                        ? 'bg-green-100 text-green-800' 
                                                                        : 'bg-red-100 text-red-800'
                                                                }`}>
                                                                    {movimiento.tipo}
                                                                </span>
                                                            </td>
                                                            <td className="px-4 py-2">{movimiento.concepto}</td>
                                                            <td className={`px-4 py-2 font-medium ${
                                                                movimiento.tipo === 'ingreso' ? 'text-green-600' : 'text-red-600'
                                                            }`}>
                                                                {movimiento.tipo === 'ingreso' ? '+' : '-'}${parseFloat(movimiento.monto || 0).toFixed(2)}
                                                            </td>
                                                        </tr>
                                                    ))}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                )}

                                {/* Formulario de Cierre */}
                                <div className="mb-6 p-4 bg-yellow-50 rounded-lg border border-yellow-200">
                                    <h4 className="font-semibold text-lg mb-4 text-gray-800">Confirmación de Cierre</h4>
                                    <div className="space-y-4">
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                Monto Real en Caja *
                                            </label>
                                            <input
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                value={montoReal}
                                                onChange={(e) => setMontoReal(e.target.value)}
                                                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                placeholder="0.00"
                                                required
                                                autoFocus
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                Observaciones
                                            </label>
                                            <textarea
                                                value={observaciones}
                                                onChange={(e) => setObservaciones(e.target.value)}
                                                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                rows="3"
                                                placeholder="Observaciones sobre el cierre (opcional)"
                                            />
                                        </div>
                                        <div className="p-3 bg-white rounded border border-gray-300">
                                            <div className="flex justify-between items-center mb-2">
                                                <span className="text-sm font-medium text-gray-700">Monto Esperado:</span>
                                                <span className="text-lg font-bold text-purple-600">
                                                    ${parseFloat(resumenCierre.resumen.monto_esperado).toFixed(2)}
                                                </span>
                                            </div>
                                            <div className="flex justify-between items-center mb-2">
                                                <span className="text-sm font-medium text-gray-700">Monto Real:</span>
                                                <span className="text-lg font-bold">
                                                    ${parseFloat(montoReal || 0).toFixed(2)}
                                                </span>
                                            </div>
                                            <div className="border-t pt-2 flex justify-between items-center">
                                                <span className="text-sm font-bold text-gray-800">Diferencia:</span>
                                                <span className={`text-xl font-bold ${
                                                    diferencia >= 0 ? 'text-green-600' : 'text-red-600'
                                                }`}>
                                                    {diferencia >= 0 ? '+' : ''}${diferencia.toFixed(2)}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {/* Botones de Acción */}
                                <div className="flex justify-end gap-3 pt-4 border-t">
                                    <button
                                        onClick={() => {
                                            setShowCerrarModal(false);
                                            setCajaSeleccionada(null);
                                            setResumenCierre(null);
                                            setMontoReal('');
                                            setObservaciones('');
                                            setError('');
                                        }}
                                        className="px-6 py-2 border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500"
                                        disabled={cerrando}
                                    >
                                        Cancelar
                                    </button>
                                    <button
                                        onClick={cerrarCaja}
                                        disabled={cerrando || !montoReal || parseFloat(montoReal) < 0}
                                        className="px-6 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 disabled:bg-gray-400 disabled:cursor-not-allowed"
                                    >
                                        {cerrando ? 'Cerrando...' : 'Confirmar Cierre de Caja'}
                                    </button>
                                </div>
                            </div>
                        ) : null}
                    </div>
                </div>
            )}
        </div>
    );
}
