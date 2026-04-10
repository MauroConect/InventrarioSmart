import React, { useState, useEffect, useCallback } from 'react';
import { Link } from 'react-router-dom';
import axios from 'axios';
import { useAuth } from '../context/AuthContext';
import { canAccess } from '../utils/permissions';
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

export default function CuentasCorrientes() {
    const { user } = useAuth();
    const esAdmin = canAccess(user, 'admin');

    const [cuentas, setCuentas] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [success, setSuccess] = useState('');

    const [modalMov, setModalMov] = useState(false);
    const [cuentaMov, setCuentaMov] = useState(null);
    const [tipoMov, setTipoMov] = useState('haber');

    const [modalNueva, setModalNueva] = useState(false);
    const [clientesOpts, setClientesOpts] = useState([]);
    const [nuevaClienteId, setNuevaClienteId] = useState('');
    const [nuevaLimite, setNuevaLimite] = useState('');
    const [nuevaActiva, setNuevaActiva] = useState(true);
    const [guardandoNueva, setGuardandoNueva] = useState(false);
    const [nuevaError, setNuevaError] = useState('');

    const fetchCuentas = useCallback(async () => {
        try {
            setLoading(true);
            setError('');
            const response = await axios.get('cuentas-corrientes', { params: { per_page: 100 } });
            const raw = response.data.data ?? response.data;
            setCuentas(Array.isArray(raw) ? raw : []);
        } catch (e) {
            console.error(e);
            setError('No se pudieron cargar las cuentas corrientes. ¿Tenés permisos de administrador?');
            setCuentas([]);
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        fetchCuentas();
    }, [fetchCuentas]);

    const abrirClientesParaNueva = async () => {
        setError('');
        setNuevaError('');
        setNuevaClienteId('');
        setNuevaLimite('');
        setNuevaActiva(true);
        try {
            const [cuentasRes, clientesRes] = await Promise.all([
                axios.get('cuentas-corrientes', { params: { per_page: 100 } }),
                axios.get('clientes', { params: { all: true } }),
            ]);
            const cuentasRaw = cuentasRes.data.data ?? cuentasRes.data;
            const conCuenta = new Set((Array.isArray(cuentasRaw) ? cuentasRaw : []).map((c) => c.cliente_id));
            const lista = clientesRes.data || [];
            const sinCuenta = lista.filter((c) => !conCuenta.has(c.id) && c.activo !== false);
            setClientesOpts(sinCuenta);
            setModalNueva(true);
        } catch (e) {
            console.error(e);
            setError('No se pudo cargar la lista de clientes.');
        }
    };

    const crearCuenta = async (e) => {
        e.preventDefault();
        if (!nuevaClienteId) {
            setNuevaError('Seleccioná un cliente.');
            return;
        }
        const lim = parseFloat(nuevaLimite);
        if (!Number.isFinite(lim) || lim < 0) {
            setNuevaError('El límite de crédito debe ser un número mayor o igual a 0.');
            return;
        }
        try {
            setGuardandoNueva(true);
            setNuevaError('');
            await axios.post('cuentas-corrientes', {
                cliente_id: parseInt(nuevaClienteId, 10),
                limite_credito: lim,
                activa: nuevaActiva,
            });
            setSuccess('Cuenta corriente creada correctamente.');
            setModalNueva(false);
            fetchCuentas();
            setTimeout(() => setSuccess(''), 4000);
        } catch (e) {
            const msg = e.response?.data?.message;
            setNuevaError(typeof msg === 'string' ? msg : 'No se pudo crear la cuenta.');
        } finally {
            setGuardandoNueva(false);
        }
    };

    const abrirModalPago = (cuenta, tipo) => {
        setCuentaMov(cuenta);
        setTipoMov(tipo);
        setModalMov(true);
    };

    if (loading && cuentas.length === 0) {
        return (
            <div className="p-6">
                <p className="text-gray-600">Cargando cuentas corrientes…</p>
            </div>
        );
    }

    const cuentasList = cuentas;

    return (
        <div className="p-4 sm:p-6">
            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <h1 className="text-2xl sm:text-3xl font-bold text-gray-900">Cuentas corrientes</h1>
                {esAdmin && (
                    <button
                        type="button"
                        onClick={abrirClientesParaNueva}
                        className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm whitespace-nowrap"
                    >
                        + Nueva cuenta
                    </button>
                )}
            </div>

            {error && !modalNueva && (
                <div className="mb-4 p-3 bg-red-50 border border-red-200 text-red-800 rounded-md text-sm">{error}</div>
            )}
            {success && <div className="mb-4 p-3 bg-green-50 border border-green-200 text-green-800 rounded-md text-sm">{success}</div>}

            <div className="bg-white rounded-lg shadow border border-gray-100 overflow-hidden">
                <div className="overflow-x-auto">
                    <table className="min-w-full divide-y divide-gray-200 text-sm">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cliente</th>
                                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                                <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Deuda</th>
                                <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Límite</th>
                                <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Disponible</th>
                                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-200">
                            {cuentasList.length === 0 ? (
                                <tr>
                                    <td colSpan={6} className="px-4 py-8 text-center text-gray-500">
                                        No hay cuentas corrientes. {esAdmin && 'Creá una con “Nueva cuenta”.'}
                                    </td>
                                </tr>
                            ) : (
                                cuentasList.map((cuenta) => {
                                    const disp = creditoDisponible(cuenta);
                                    const sal = parseNum(cuenta.saldo);
                                    const nombre = cuenta.cliente
                                        ? `${cuenta.cliente.nombre || ''} ${cuenta.cliente.apellido || ''}`.trim()
                                        : '—';
                                    return (
                                        <tr key={cuenta.id} className="hover:bg-gray-50">
                                            <td className="px-4 py-3 font-medium text-gray-900">{nombre}</td>
                                            <td className="px-4 py-3">
                                                <span
                                                    className={`inline-flex px-2 py-0.5 rounded text-xs font-medium ${
                                                        cuenta.activa ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-700'
                                                    }`}
                                                >
                                                    {cuenta.activa ? 'Activa' : 'Inactiva'}
                                                </span>
                                            </td>
                                            <td className="px-4 py-3 text-right font-semibold">
                                                <span className={sal > 0 ? 'text-amber-800' : sal < 0 ? 'text-blue-700' : 'text-green-700'}>
                                                    ${sal.toFixed(2)}
                                                </span>
                                            </td>
                                            <td className="px-4 py-3 text-right text-gray-700">${parseNum(cuenta.limite_credito).toFixed(2)}</td>
                                            <td className="px-4 py-3 text-right text-gray-700">
                                                {disp === null ? <span className="text-gray-400">—</span> : `$${disp.toFixed(2)}`}
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="flex flex-wrap gap-2">
                                                    <Link
                                                        to={`/cuentas-corrientes/${cuenta.id}`}
                                                        className="text-blue-600 hover:text-blue-800 whitespace-nowrap"
                                                    >
                                                        Ver cuenta
                                                    </Link>
                                                    {esAdmin && cuenta.activa && (
                                                        <>
                                                            <button
                                                                type="button"
                                                                onClick={() => abrirModalPago(cuenta, 'haber')}
                                                                className="text-green-700 hover:text-green-900 whitespace-nowrap text-left"
                                                            >
                                                                Pago
                                                            </button>
                                                            <button
                                                                type="button"
                                                                onClick={() => abrirModalPago(cuenta, 'debe')}
                                                                className="text-amber-700 hover:text-amber-900 whitespace-nowrap text-left"
                                                            >
                                                                Cargo
                                                            </button>
                                                        </>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    );
                                })
                            )}
                        </tbody>
                    </table>
                </div>
            </div>

            <MovimientoCuentaCorrienteModal
                cuentaId={cuentaMov?.id}
                tituloCliente={
                    cuentaMov?.cliente
                        ? `${cuentaMov.cliente.nombre || ''} ${cuentaMov.cliente.apellido || ''}`.trim()
                        : ''
                }
                open={modalMov && !!cuentaMov}
                tipoInicial={tipoMov}
                onClose={() => {
                    setModalMov(false);
                    setCuentaMov(null);
                }}
                onGuardado={fetchCuentas}
            />

            {modalNueva && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" onClick={() => setModalNueva(false)}>
                    <div
                        className="bg-white rounded-lg shadow-xl w-full max-w-md"
                        onClick={(e) => e.stopPropagation()}
                    >
                        <div className="border-b px-4 py-3 flex justify-between items-center">
                            <h3 className="text-lg font-semibold">Nueva cuenta corriente</h3>
                            <button type="button" onClick={() => setModalNueva(false)} className="text-gray-500 hover:text-gray-800 text-xl">
                                ×
                            </button>
                        </div>
                        <form onSubmit={crearCuenta} className="p-4 space-y-4">
                            {nuevaError && (
                                <div className="text-sm text-red-700 bg-red-50 border border-red-200 rounded px-3 py-2">{nuevaError}</div>
                            )}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Cliente *</label>
                                <select
                                    value={nuevaClienteId}
                                    onChange={(e) => setNuevaClienteId(e.target.value)}
                                    className="w-full border border-gray-300 rounded-md px-3 py-2"
                                    required
                                >
                                    <option value="">Seleccionar…</option>
                                    {clientesOpts.map((c) => (
                                        <option key={c.id} value={c.id}>
                                            {c.nombre} {c.apellido}
                                            {c.dni ? ` — DNI ${c.dni}` : ''}
                                        </option>
                                    ))}
                                </select>
                                {clientesOpts.length === 0 && (
                                    <p className="text-xs text-amber-700 mt-1">No hay clientes sin cuenta corriente.</p>
                                )}
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Límite de crédito *</label>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    value={nuevaLimite}
                                    onChange={(e) => setNuevaLimite(e.target.value)}
                                    placeholder="0 = sin tope en el sistema de ventas"
                                    className="w-full border border-gray-300 rounded-md px-3 py-2"
                                    required
                                />
                                <p className="text-xs text-gray-500 mt-1">0 permite comprar en cuenta sin tope (según reglas de venta).</p>
                            </div>
                            <div className="flex items-center gap-2">
                                <input
                                    id="nueva-activa"
                                    type="checkbox"
                                    checked={nuevaActiva}
                                    onChange={(e) => setNuevaActiva(e.target.checked)}
                                />
                                <label htmlFor="nueva-activa" className="text-sm text-gray-700">
                                    Cuenta activa
                                </label>
                            </div>
                            <div className="flex justify-end gap-2 pt-2">
                                <button
                                    type="button"
                                    onClick={() => setModalNueva(false)}
                                    className="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50"
                                >
                                    Cancelar
                                </button>
                                <button
                                    type="submit"
                                    disabled={guardandoNueva || clientesOpts.length === 0}
                                    className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50"
                                >
                                    {guardandoNueva ? 'Creando…' : 'Crear cuenta'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
}
