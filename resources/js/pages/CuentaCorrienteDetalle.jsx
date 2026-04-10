import React, { useState, useEffect, useCallback } from 'react';
import { useParams, Link } from 'react-router-dom';
import axios from 'axios';
import MovimientoCuentaCorrienteModal from '../components/MovimientoCuentaCorrienteModal';

function parseNum(v) {
    const n = parseFloat(v);
    return Number.isFinite(n) ? n : 0;
}

function creditoDisponible(cuenta) {
    const lim = parseNum(cuenta?.limite_credito);
    const sal = parseNum(cuenta?.saldo);
    if (lim <= 0) {
        return null;
    }
    return Math.max(0, lim - sal);
}

function etiquetaSaldo(saldo) {
    const s = parseNum(saldo);
    if (s > 0) {
        return { texto: 'Debe', clase: 'text-amber-800' };
    }
    if (s < 0) {
        return { texto: 'A favor', clase: 'text-blue-700' };
    }
    return { texto: 'Al día', clase: 'text-green-700' };
}

export default function CuentaCorrienteDetalle() {
    const { id } = useParams();
    const [cuenta, setCuenta] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [modalMov, setModalMov] = useState(false);
    const [tipoModal, setTipoModal] = useState('haber');

    const cargar = useCallback(async () => {
        if (!id) return;
        try {
            setLoading(true);
            setError('');
            const res = await axios.get(`cuentas-corrientes/${id}`);
            setCuenta(res.data);
        } catch (e) {
            console.error(e);
            setError('No se pudo cargar la cuenta corriente.');
            setCuenta(null);
        } finally {
            setLoading(false);
        }
    }, [id]);

    useEffect(() => {
        cargar();
    }, [cargar]);

    if (loading && !cuenta) {
        return (
            <div className="p-6">
                <p className="text-gray-600">Cargando…</p>
            </div>
        );
    }

    if (error && !cuenta) {
        return (
            <div className="p-6">
                <p className="text-red-600 mb-4">{error}</p>
                <Link to="/cuentas-corrientes" className="text-blue-600 hover:underline">
                    Volver al listado
                </Link>
            </div>
        );
    }

    const clienteNombre = cuenta?.cliente
        ? `${cuenta.cliente.nombre || ''} ${cuenta.cliente.apellido || ''}`.trim()
        : '—';
    const disp = creditoDisponible(cuenta);
    const saldoInfo = etiquetaSaldo(cuenta?.saldo);
    const movimientos = Array.isArray(cuenta?.movimientos) ? cuenta.movimientos : [];

    return (
        <div className="p-4 sm:p-6 max-w-5xl">
            <div className="mb-4">
                <Link to="/cuentas-corrientes" className="text-sm text-blue-600 hover:underline">
                    ← Cuentas corrientes
                </Link>
            </div>

            <div className="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6">
                <div>
                    <h1 className="text-2xl sm:text-3xl font-bold text-gray-900">Cuenta corriente</h1>
                    <p className="text-lg text-gray-700 mt-1">{clienteNombre}</p>
                    <p className="text-sm text-gray-500 mt-1">
                        Estado:{' '}
                        <span className={cuenta?.activa ? 'text-green-700 font-medium' : 'text-red-600 font-medium'}>
                            {cuenta?.activa ? 'Activa' : 'Inactiva'}
                        </span>
                    </p>
                </div>
                <div className="flex flex-wrap gap-2">
                    <button
                        type="button"
                        onClick={() => {
                            setTipoModal('haber');
                            setModalMov(true);
                        }}
                        disabled={!cuenta?.activa}
                        className="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed text-sm"
                    >
                        Registrar pago
                    </button>
                    <button
                        type="button"
                        onClick={() => {
                            setTipoModal('debe');
                            setModalMov(true);
                        }}
                        disabled={!cuenta?.activa}
                        className="px-4 py-2 bg-amber-600 text-white rounded-md hover:bg-amber-700 disabled:opacity-50 disabled:cursor-not-allowed text-sm"
                    >
                        Cargo / ajuste debe
                    </button>
                </div>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
                <div className="bg-white rounded-lg border border-gray-200 p-4 shadow-sm">
                    <p className="text-xs font-medium text-gray-500 uppercase">Deuda actual</p>
                    <p className={`text-2xl font-bold mt-1 ${saldoInfo.clase}`}>${parseNum(cuenta?.saldo).toFixed(2)}</p>
                    <p className="text-sm text-gray-600 mt-1">{saldoInfo.texto}</p>
                </div>
                <div className="bg-white rounded-lg border border-gray-200 p-4 shadow-sm">
                    <p className="text-xs font-medium text-gray-500 uppercase">Límite de crédito</p>
                    <p className="text-2xl font-bold mt-1 text-gray-900">${parseNum(cuenta?.limite_credito).toFixed(2)}</p>
                    <p className="text-sm text-gray-600 mt-1">
                        {parseNum(cuenta?.limite_credito) <= 0 ? 'Sin tope (compras según reglas de venta)' : 'Tope máximo de deuda'}
                    </p>
                </div>
                <div className="bg-white rounded-lg border border-gray-200 p-4 shadow-sm">
                    <p className="text-xs font-medium text-gray-500 uppercase">Crédito disponible</p>
                    <p className="text-2xl font-bold mt-1 text-gray-900">
                        {disp === null ? '—' : `$${disp.toFixed(2)}`}
                    </p>
                    <p className="text-sm text-gray-600 mt-1">
                        {disp === null ? 'Sin límite configurado' : 'Para nuevas compras en cuenta'}
                    </p>
                </div>
            </div>

            <div className="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                <div className="px-4 py-3 border-b border-gray-200 bg-gray-50">
                    <h2 className="font-semibold text-gray-800">Movimientos</h2>
                </div>
                <div className="overflow-x-auto">
                    <table className="min-w-full divide-y divide-gray-200 text-sm">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                                <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                                <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Concepto</th>
                                <th className="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Monto</th>
                                <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase hidden md:table-cell">Venta</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-200">
                            {movimientos.length === 0 ? (
                                <tr>
                                    <td colSpan={5} className="px-4 py-8 text-center text-gray-500">
                                        No hay movimientos registrados.
                                    </td>
                                </tr>
                            ) : (
                                movimientos.map((m) => (
                                    <tr key={m.id} className="hover:bg-gray-50">
                                        <td className="px-4 py-2 whitespace-nowrap text-gray-600">
                                            {m.created_at ? new Date(m.created_at).toLocaleString('es-AR') : '—'}
                                        </td>
                                        <td className="px-4 py-2">
                                            <span
                                                className={`inline-flex px-2 py-0.5 rounded text-xs font-medium ${
                                                    m.tipo === 'debe' ? 'bg-amber-100 text-amber-900' : 'bg-green-100 text-green-900'
                                                }`}
                                            >
                                                {m.tipo === 'debe' ? 'Debe' : 'Haber (pago)'}
                                            </span>
                                        </td>
                                        <td className="px-4 py-2 text-gray-800">
                                            {m.concepto}
                                            {m.observaciones ? (
                                                <span className="block text-xs text-gray-500 mt-0.5">{m.observaciones}</span>
                                            ) : null}
                                        </td>
                                        <td className="px-4 py-2 text-right font-medium">${parseNum(m.monto).toFixed(2)}</td>
                                        <td className="px-4 py-2 text-gray-600 hidden md:table-cell">
                                            {m.venta?.numero_factura || (m.venta_id ? `#${m.venta_id}` : '—')}
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
            </div>

            <MovimientoCuentaCorrienteModal
                cuentaId={cuenta?.id}
                tituloCliente={clienteNombre}
                open={modalMov}
                tipoInicial={tipoModal}
                onClose={() => setModalMov(false)}
                onGuardado={cargar}
            />
        </div>
    );
}
