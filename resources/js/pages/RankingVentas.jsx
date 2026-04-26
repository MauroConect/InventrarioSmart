import React, { useState, useEffect, useCallback, useMemo } from 'react';
import axios from '../config/axios';

function iso(d) {
    const pad = (n) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
}

function fmt(n) {
    const x = parseFloat(n);
    return (Number.isNaN(x) ? 0 : x).toFixed(2);
}

export default function RankingVentas() {
    const defaults = useMemo(() => {
        const hoy = new Date();
        const inicioMes = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
        return { fechaInicio: iso(inicioMes), fechaFin: iso(hoy) };
    }, []);

    const [fechaInicio, setFechaInicio] = useState(defaults.fechaInicio);
    const [fechaFin, setFechaFin] = useState(defaults.fechaFin);
    const [limite, setLimite] = useState(25);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [data, setData] = useState(null);

    const fetchRanking = useCallback(async () => {
        setLoading(true);
        setError('');
        try {
            const res = await axios.get('/admin/ranking-ventas', {
                params: {
                    fecha_inicio: fechaInicio,
                    fecha_fin: fechaFin,
                    limite,
                },
            });
            setData(res.data);
        } catch (e) {
            setData(null);
            setError(e.response?.data?.message || 'No se pudo cargar el ranking.');
        } finally {
            setLoading(false);
        }
    }, [fechaInicio, fechaFin, limite]);

    useEffect(() => {
        fetchRanking();
    }, [fetchRanking]);

    return (
        <div className="space-y-6">
            <div className="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
                <div>
                    <h1 className="text-2xl sm:text-3xl font-bold text-gray-900">Ranking de ventas</h1>
                    <p className="text-sm text-gray-600 mt-1">
                        Productos, vendedores, clientes y cajas (ventas cerradas o completadas).
                    </p>
                </div>
                <div className="flex flex-wrap items-end gap-3">
                    <div>
                        <label className="block text-xs font-medium text-gray-600 mb-1">Desde</label>
                        <input
                            type="date"
                            value={fechaInicio}
                            onChange={(e) => setFechaInicio(e.target.value)}
                            className="px-3 py-2 border border-gray-300 rounded-md text-sm w-full sm:w-auto"
                        />
                    </div>
                    <div>
                        <label className="block text-xs font-medium text-gray-600 mb-1">Hasta</label>
                        <input
                            type="date"
                            value={fechaFin}
                            onChange={(e) => setFechaFin(e.target.value)}
                            className="px-3 py-2 border border-gray-300 rounded-md text-sm w-full sm:w-auto"
                        />
                    </div>
                    <div>
                        <label className="block text-xs font-medium text-gray-600 mb-1">Top</label>
                        <select
                            value={limite}
                            onChange={(e) => setLimite(Number(e.target.value))}
                            className="px-3 py-2 border border-gray-300 rounded-md text-sm"
                        >
                            <option value={15}>15</option>
                            <option value={25}>25</option>
                            <option value={50}>50</option>
                        </select>
                    </div>
                    <button
                        type="button"
                        onClick={() => fetchRanking()}
                        disabled={loading}
                        className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 text-sm"
                    >
                        {loading ? 'Cargando…' : 'Actualizar'}
                    </button>
                </div>
            </div>

            {error && (
                <div className="p-4 bg-red-100 border border-red-400 text-red-700 rounded text-sm">{error}</div>
            )}

            {loading && !data && (
                <div className="flex items-center justify-center py-24 text-gray-500">
                    <div className="inline-block animate-spin rounded-full h-10 w-10 border-b-2 border-blue-600 mr-3" />
                    Cargando ranking…
                </div>
            )}

            {data && (
                <>
                    <p className="text-sm text-gray-500">
                        Período:{' '}
                        <span className="font-medium text-gray-800">
                            {data.periodo?.fecha_inicio} — {data.periodo?.fecha_fin}
                        </span>
                    </p>

                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        {[
                            { label: 'Ventas', value: data.resumen?.cantidad_ventas ?? 0, color: 'text-gray-900' },
                            {
                                label: 'Monto total',
                                value: `$${fmt(data.resumen?.monto_total)}`,
                                color: 'text-emerald-700',
                            },
                            {
                                label: 'Ticket promedio',
                                value: `$${fmt(data.resumen?.ticket_promedio)}`,
                                color: 'text-blue-700',
                            },
                            {
                                label: 'Unidades vendidas',
                                value: data.resumen?.unidades_vendidas ?? 0,
                                color: 'text-gray-900',
                            },
                        ].map((c) => (
                            <div
                                key={c.label}
                                className="bg-white rounded-lg shadow p-4 border border-gray-100"
                            >
                                <p className="text-xs font-semibold text-gray-500 uppercase">{c.label}</p>
                                <p className={`text-2xl font-bold mt-1 ${c.color}`}>{c.value}</p>
                            </div>
                        ))}
                    </div>

                    <div className="grid grid-cols-1 xl:grid-cols-2 gap-6">
                        <div className="bg-white rounded-lg shadow border border-gray-100 overflow-hidden">
                            <div className="px-4 py-3 border-b bg-gray-50">
                                <h2 className="text-lg font-semibold text-gray-800">Productos más vendidos</h2>
                                <p className="text-xs text-gray-500">Por unidades</p>
                            </div>
                            <div className="overflow-x-auto max-h-[28rem] overflow-y-auto">
                                <table className="min-w-full divide-y divide-gray-200 text-sm">
                                    <thead className="bg-gray-50 sticky top-0">
                                        <tr>
                                            <th className="px-3 py-2 text-left text-xs font-medium text-gray-500">#</th>
                                            <th className="px-3 py-2 text-left text-xs font-medium text-gray-500">Producto</th>
                                            <th className="px-3 py-2 text-right text-xs font-medium text-gray-500">Unid.</th>
                                            <th className="px-3 py-2 text-right text-xs font-medium text-gray-500">Total $</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-100">
                                        {(data.productos || []).map((p, i) => (
                                            <tr key={p.id} className="hover:bg-gray-50">
                                                <td className="px-3 py-2 text-gray-500">{i + 1}</td>
                                                <td className="px-3 py-2">
                                                    <span className="font-medium text-gray-900">{p.nombre}</span>
                                                    <span className="block text-xs text-gray-500">{p.codigo || '—'}</span>
                                                </td>
                                                <td className="px-3 py-2 text-right font-medium">{p.cantidad_vendida}</td>
                                                <td className="px-3 py-2 text-right">${fmt(p.total_vendido)}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                                {!(data.productos || []).length && (
                                    <p className="p-6 text-center text-gray-500 text-sm">Sin datos en el período.</p>
                                )}
                            </div>
                        </div>

                        <div className="bg-white rounded-lg shadow border border-gray-100 overflow-hidden">
                            <div className="px-4 py-3 border-b bg-gray-50">
                                <h2 className="text-lg font-semibold text-gray-800">Vendedores</h2>
                                <p className="text-xs text-gray-500">Por monto total</p>
                            </div>
                            <div className="overflow-x-auto max-h-[28rem] overflow-y-auto">
                                <table className="min-w-full divide-y divide-gray-200 text-sm">
                                    <thead className="bg-gray-50 sticky top-0">
                                        <tr>
                                            <th className="px-3 py-2 text-left text-xs font-medium text-gray-500">#</th>
                                            <th className="px-3 py-2 text-left text-xs font-medium text-gray-500">Vendedor</th>
                                            <th className="px-3 py-2 text-right text-xs font-medium text-gray-500">Ventas</th>
                                            <th className="px-3 py-2 text-right text-xs font-medium text-gray-500">Total $</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-100">
                                        {(data.vendedores || []).map((v, i) => (
                                            <tr
                                                key={v.usuario_id != null ? `u-${v.usuario_id}` : `n-${i}`}
                                                className="hover:bg-gray-50"
                                            >
                                                <td className="px-3 py-2 text-gray-500">{i + 1}</td>
                                                <td className="px-3 py-2">
                                                    <span className="font-medium text-gray-900">{v.nombre}</span>
                                                    {v.email ? (
                                                        <span className="block text-xs text-gray-500 truncate max-w-[14rem]">
                                                            {v.email}
                                                        </span>
                                                    ) : null}
                                                </td>
                                                <td className="px-3 py-2 text-right">{v.cantidad_ventas}</td>
                                                <td className="px-3 py-2 text-right font-medium text-emerald-700">
                                                    ${fmt(v.monto_total)}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                                {!(data.vendedores || []).length && (
                                    <p className="p-6 text-center text-gray-500 text-sm">Sin datos en el período.</p>
                                )}
                            </div>
                        </div>

                        <div className="bg-white rounded-lg shadow border border-gray-100 overflow-hidden">
                            <div className="px-4 py-3 border-b bg-gray-50">
                                <h2 className="text-lg font-semibold text-gray-800">Clientes</h2>
                                <p className="text-xs text-gray-500">Por monto total de compras</p>
                            </div>
                            <div className="overflow-x-auto max-h-[28rem] overflow-y-auto">
                                <table className="min-w-full divide-y divide-gray-200 text-sm">
                                    <thead className="bg-gray-50 sticky top-0">
                                        <tr>
                                            <th className="px-3 py-2 text-left text-xs font-medium text-gray-500">#</th>
                                            <th className="px-3 py-2 text-left text-xs font-medium text-gray-500">Cliente</th>
                                            <th className="px-3 py-2 text-right text-xs font-medium text-gray-500">Compras</th>
                                            <th className="px-3 py-2 text-right text-xs font-medium text-gray-500">Total $</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-100">
                                        {(data.clientes || []).map((c, i) => (
                                            <tr key={c.id} className="hover:bg-gray-50">
                                                <td className="px-3 py-2 text-gray-500">{i + 1}</td>
                                                <td className="px-3 py-2 font-medium text-gray-900">{c.nombre}</td>
                                                <td className="px-3 py-2 text-right">{c.cantidad_compras}</td>
                                                <td className="px-3 py-2 text-right">${fmt(c.monto_total)}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                                {!(data.clientes || []).length && (
                                    <p className="p-6 text-center text-gray-500 text-sm">
                                        Sin clientes con compras en el período.
                                    </p>
                                )}
                            </div>
                        </div>

                        <div className="bg-white rounded-lg shadow border border-gray-100 overflow-hidden">
                            <div className="px-4 py-3 border-b bg-gray-50">
                                <h2 className="text-lg font-semibold text-gray-800">Cajas y formas de pago</h2>
                            </div>
                            <div className="p-4 space-y-4 max-h-[28rem] overflow-y-auto">
                                <div>
                                    <h3 className="text-sm font-semibold text-gray-700 mb-2">Por caja</h3>
                                    <table className="min-w-full text-sm">
                                        <thead>
                                            <tr className="text-left text-xs text-gray-500 border-b">
                                                <th className="py-1 pr-2">Caja</th>
                                                <th className="py-1 text-right">Ventas</th>
                                                <th className="py-1 text-right">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {(data.cajas || []).map((c) => (
                                                <tr key={c.id} className="border-b border-gray-50">
                                                    <td className="py-2 pr-2">{c.nombre}</td>
                                                    <td className="py-2 text-right text-gray-600">{c.cantidad_ventas}</td>
                                                    <td className="py-2 text-right font-medium">${fmt(c.monto_total)}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                    {!(data.cajas || []).length && (
                                        <p className="text-sm text-gray-500 py-2">Sin datos.</p>
                                    )}
                                </div>
                                <div>
                                    <h3 className="text-sm font-semibold text-gray-700 mb-2">Por tipo de pago</h3>
                                    <table className="min-w-full text-sm">
                                        <thead>
                                            <tr className="text-left text-xs text-gray-500 border-b">
                                                <th className="py-1 pr-2">Tipo</th>
                                                <th className="py-1 text-right">Ops.</th>
                                                <th className="py-1 text-right">Monto</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {(data.por_tipo_pago || []).map((t) => (
                                                <tr key={t.tipo_pago} className="border-b border-gray-50">
                                                    <td className="py-2 pr-2">{t.label}</td>
                                                    <td className="py-2 text-right text-gray-600">{t.cantidad}</td>
                                                    <td className="py-2 text-right font-medium">${fmt(t.monto)}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                    {!(data.por_tipo_pago || []).length && (
                                        <p className="text-sm text-gray-500 py-2">Sin datos.</p>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>
                </>
            )}
        </div>
    );
}
