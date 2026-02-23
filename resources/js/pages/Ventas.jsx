import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import axios from 'axios';

export default function Ventas() {
    const navigate = useNavigate();
    const [ventas, setVentas] = useState([]);
    const [clientes, setClientes] = useState([]);
    const [productos, setProductos] = useState([]);
    const [cajasAbiertas, setCajasAbiertas] = useState([]);
    const [cajaSeleccionada, setCajaSeleccionada] = useState(null);

    const [loadingLista, setLoadingLista] = useState(true);
    const [loadingFormDatos, setLoadingFormDatos] = useState(true);
    const [loadingSubmit, setLoadingSubmit] = useState(false);

    const [showModal, setShowModal] = useState(false);
    const [error, setError] = useState('');
    const [success, setSuccess] = useState('');

    const [clienteId, setClienteId] = useState('');
    const [tipoPago, setTipoPago] = useState('efectivo');
    const [montoTarjeta, setMontoTarjeta] = useState('');
    const [montoEfectivo, setMontoEfectivo] = useState('');
    const [cuotas, setCuotas] = useState('');
    const [recargoCuotas, setRecargoCuotas] = useState('');
    const [descuento, setDescuento] = useState(0);
    const [items, setItems] = useState([
        { producto_id: '', cantidad: 1 },
    ]);
    const [adjuntos, setAdjuntos] = useState([]);

    useEffect(() => {
        fetchVentas();
        fetchDatosFormulario();
    }, []);

    const fetchVentas = async () => {
        try {
            setLoadingLista(true);
            const response = await axios.get('/ventas');
            setVentas(response.data.data || response.data);
        } catch (error) {
            console.error('Error al cargar ventas:', error);
        } finally {
            setLoadingLista(false);
        }
    };

    const fetchDatosFormulario = async () => {
        try {
            setLoadingFormDatos(true);

            const [clientesRes, productosRes, cajasRes] = await Promise.all([
                axios.get('/clientes'),
                axios.get('/productos'),
                axios.get('/cajas', { params: { estado: 'abierta' } }),
            ]);

            const clientesData = clientesRes.data?.data || clientesRes.data || [];
            const productosData = productosRes.data?.data || productosRes.data || [];
            const cajasData = cajasRes.data?.data || cajasRes.data || [];

            setClientes(Array.isArray(clientesData) ? clientesData.filter(c => c.activo !== false) : []);
            setProductos(Array.isArray(productosData) ? productosData.filter(p => p.activo !== false) : []);
            const cajasOpen = Array.isArray(cajasData) ? cajasData.filter(c => c.estado === 'abierta') : [];
            setCajasAbiertas(cajasOpen);
            // Seleccionar la primera caja abierta por defecto si hay alguna
            if (cajasOpen.length > 0 && !cajaSeleccionada) {
                setCajaSeleccionada(cajasOpen[0]);
            } else if (cajasOpen.length === 0) {
                setCajaSeleccionada(null);
            }
        } catch (error) {
            console.error('Error al cargar datos para nueva venta:', error);
            setError('Error al cargar datos para nueva venta. Verifica la conexión o que exista una caja abierta.');
        } finally {
            setLoadingFormDatos(false);
        }
    };

    const resetForm = () => {
        setClienteId('');
        setTipoPago('efectivo');
        setMontoTarjeta('');
        setMontoEfectivo('');
        setCuotas('');
        setRecargoCuotas('');
        setDescuento(0);
        setItems([{ producto_id: '', cantidad: 1 }]);
        setAdjuntos([]);
        setError('');
    };

    const handleAddItem = () => {
        setItems([...items, { producto_id: '', cantidad: 1 }]);
    };

    const handleRemoveItem = (index) => {
        if (items.length === 1) return;
        const nuevos = items.filter((_, i) => i !== index);
        setItems(nuevos);
    };

    const handleChangeItem = (index, field, value) => {
        const nuevos = [...items];
        nuevos[index] = { ...nuevos[index], [field]: field === 'cantidad' ? parseInt(value) || 0 : value };
        setItems(nuevos);
    };

    const handleAdjuntosChange = (e) => {
        const files = Array.from(e.target.files || []);
        setAdjuntos(files);
    };

    const obtenerProducto = (id) => productos.find(p => String(p.id) === String(id));

    const calcularSubtotal = (item) => {
        const prod = obtenerProducto(item.producto_id);
        if (!prod) return 0;
        return (parseFloat(prod.precio_venta || 0) * (parseInt(item.cantidad) || 0)) || 0;
    };

    const calcularTotal = () => {
        const totalBruto = items.reduce((acc, item) => acc + calcularSubtotal(item), 0);
        return totalBruto - (parseFloat(descuento) || 0);
    };

    const handleSubmitVenta = async (e) => {
        e.preventDefault();
        try {
            setError('');
            setSuccess('');

            if (!cajaSeleccionada) {
                setError('Debe seleccionar una caja abierta para registrar una venta.');
                return;
            }

            const itemsValidos = items
                .filter(item => item.producto_id && (parseInt(item.cantidad) || 0) > 0)
                .map(item => ({
                    producto_id: item.producto_id,
                    cantidad: parseInt(item.cantidad) || 0,
                }));

            if (itemsValidos.length === 0) {
                setError('Debe agregar al menos un producto con cantidad mayor a 0.');
                return;
            }

            setLoadingSubmit(true);

            // Validar montos mixtos si es necesario
            if (tipoPago === 'mixto') {
                const montoTarjetaNum = parseFloat(montoTarjeta) || 0;
                const montoEfectivoNum = parseFloat(montoEfectivo) || 0;
                const totalFinal = calcularTotal();
                const tieneCuotas = cuotas && parseFloat(cuotas) > 0;
                
                // Debe tener al menos un monto o cuotas
                if (montoTarjetaNum <= 0 && montoEfectivoNum <= 0 && !tieneCuotas) {
                    setError('Debe especificar al menos un monto en tarjeta, efectivo o cuotas.');
                    setLoadingSubmit(false);
                    return;
                }
                
                // Si hay montos, validar que no excedan el total
                const sumaMontos = montoTarjetaNum + montoEfectivoNum;
                if (sumaMontos > 0 && sumaMontos > totalFinal + 0.01) {
                    setError(`La suma de monto en tarjeta ($${montoTarjetaNum.toFixed(2)}) y monto en efectivo ($${montoEfectivoNum.toFixed(2)}) no puede exceder el total final ($${totalFinal.toFixed(2)}).`);
                    setLoadingSubmit(false);
                    return;
                }
            }

            // 1) Crear venta (JSON)
            const payload = {
                caja_id: cajaSeleccionada.id,
                cliente_id: clienteId || null,
                tipo_pago: tipoPago,
                descuento: parseFloat(descuento) || 0,
                items: itemsValidos,
            };

            // Agregar campos de pago mixto si aplica
            if (tipoPago === 'mixto') {
                payload.monto_tarjeta = parseFloat(montoTarjeta) || 0;
                payload.monto_efectivo = parseFloat(montoEfectivo) || 0;
                if (cuotas) {
                    payload.cuotas = parseInt(cuotas) || null;
                }
                if (recargoCuotas && parseFloat(recargoCuotas) > 0) {
                    payload.recargo_cuotas = parseFloat(recargoCuotas) || 0;
                }
            } else if (tipoPago === 'tarjeta' && cuotas) {
                payload.cuotas = parseInt(cuotas) || null;
            }

            const ventaRes = await axios.post('/ventas', payload);
            const ventaCreada = ventaRes.data;

            // 2) Adjuntar archivos si hay
            if (adjuntos.length > 0 && ventaCreada?.id) {
                const formData = new FormData();
                adjuntos.forEach((file) => {
                    formData.append('adjuntos[]', file);
                });

                await axios.post(`/ventas/${ventaCreada.id}/adjuntos`, formData, {
                    headers: { 'Content-Type': 'multipart/form-data' },
                });
            }

            setSuccess('Venta registrada correctamente');
            setShowModal(false);
            resetForm();
            fetchVentas();

            // Ir al detalle de la venta recién creada
            if (ventaCreada?.id) {
                navigate(`/ventas/${ventaCreada.id}`);
            }

            setTimeout(() => setSuccess(''), 3000);
        } catch (error) {
            console.error('Error al registrar venta:', error);
            const errorMessage = error.response?.data?.message || 'Error al registrar la venta. Verifique los datos.';
            setError(errorMessage);
        } finally {
            setLoadingSubmit(false);
        }
    };

    const ventasList = Array.isArray(ventas) ? ventas : (ventas.data || []);

    if (loadingLista && loadingFormDatos) {
        return (
            <div className="p-6">
                <div className="flex items-center justify-center h-64">
                    <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                    <p className="ml-2 text-gray-600">Cargando ventas...</p>
                </div>
            </div>
        );
    }

    return (
        <div className="p-3 sm:p-4 lg:p-6">
            <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 sm:gap-0 mb-4 sm:mb-6">
                <h1 className="text-2xl sm:text-3xl font-bold">Ventas</h1>
                <button
                    onClick={() => {
                        setShowModal(true);
                        setError('');
                        setSuccess('');
                    }}
                    disabled={cajasAbiertas.length === 0 || loadingFormDatos}
                    className={`w-full sm:w-auto px-4 py-2 rounded text-white whitespace-nowrap ${cajasAbiertas.length === 0 || loadingFormDatos ? 'bg-gray-400 cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700'}`}
                >
                    + Nueva Venta
                </button>
            </div>

            {cajasAbiertas.length === 0 && !loadingFormDatos && (
                <div className="mb-4 p-4 bg-yellow-100 border border-yellow-400 text-yellow-800 rounded">
                    No hay ninguna caja abierta. Debe abrir una caja para poder registrar ventas.
                </div>
            )}

            {cajasAbiertas.length > 0 && (
                <div className="mb-4 p-4 bg-blue-50 border border-blue-200 rounded">
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                        Seleccionar Caja para Registrar Ventas:
                    </label>
                    <select
                        value={cajaSeleccionada?.id || ''}
                        onChange={(e) => {
                            const caja = cajasAbiertas.find(c => c.id === parseInt(e.target.value));
                            setCajaSeleccionada(caja || null);
                        }}
                        className="w-full md:w-auto px-3 py-2 border border-gray-300 rounded-md bg-white"
                    >
                        {cajasAbiertas.map((caja) => (
                            <option key={caja.id} value={caja.id}>
                                {caja.nombre || `Caja #${caja.id}`} - Apertura: {new Date(caja.fecha_apertura).toLocaleString()}
                            </option>
                        ))}
                    </select>
                </div>
            )}

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

            <div className="bg-white rounded-lg shadow overflow-hidden">
                <div className="overflow-x-auto -mx-3 sm:mx-0">
                    <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nº Factura</th>
                                <th className="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase hidden sm:table-cell">Fecha</th>
                                <th className="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase hidden md:table-cell">Cliente</th>
                                <th className="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                                <th className="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase hidden lg:table-cell">Tipo Pago</th>
                                <th className="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                            </tr>
                        </thead>
                        <tbody className="bg-white divide-y divide-gray-200">
                            {ventasList.length === 0 ? (
                                <tr>
                                    <td colSpan="6" className="px-3 sm:px-6 py-4 text-center text-gray-500">
                                        No hay ventas registradas.
                                    </td>
                                </tr>
                            ) : (
                                ventasList.map((venta) => (
                                    <tr key={venta.id} className="hover:bg-gray-50">
                                        <td className="px-3 sm:px-6 py-4 font-medium text-sm">{venta.numero_factura}</td>
                                        <td className="px-3 sm:px-6 py-4 text-sm hidden sm:table-cell">{new Date(venta.fecha).toLocaleString()}</td>
                                        <td className="px-3 sm:px-6 py-4 text-sm hidden md:table-cell">{venta.cliente ? `${venta.cliente.nombre} ${venta.cliente.apellido}` : 'Sin cliente'}</td>
                                        <td className="px-3 sm:px-6 py-4 font-bold text-sm">${parseFloat(venta.total_final || 0).toFixed(2)}</td>
                                        <td className="px-3 sm:px-6 py-4 text-sm hidden lg:table-cell">{venta.tipo_pago}</td>
                                        <td className="px-3 sm:px-6 py-4">
                                            <button
                                                onClick={() => navigate(`/ventas/${venta.id}`)}
                                                className="text-blue-600 hover:text-blue-900 text-sm"
                                            >
                                                Ver Detalle
                                            </button>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
            </div>

            {/* Modal Nueva Venta */}
            {showModal && (
                <div className="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center p-3 sm:p-4">
                    <div className="relative bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] overflow-y-auto">
                        <div className="sticky top-0 bg-white border-b px-4 sm:px-6 py-4 flex justify-between items-center">
                            <h3 className="text-lg sm:text-xl font-bold text-gray-800">Nueva Venta</h3>
                            <button
                                onClick={() => {
                                    setShowModal(false);
                                    resetForm();
                                }}
                                className="text-gray-500 hover:text-gray-700 text-2xl focus:outline-none"
                            >
                                ×
                            </button>
                        </div>

                        <form onSubmit={handleSubmitVenta} className="p-4 sm:p-6 space-y-4">
                            {error && (
                                <div className="p-3 bg-red-100 border border-red-400 text-red-700 rounded text-sm">
                                    {error}
                                </div>
                            )}

                            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Caja</label>
                                    {cajasAbiertas.length > 0 ? (
                                        <select
                                            value={cajaSeleccionada?.id || ''}
                                            onChange={(e) => {
                                                const caja = cajasAbiertas.find(c => c.id === parseInt(e.target.value));
                                                setCajaSeleccionada(caja || null);
                                            }}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-md bg-white"
                                            required
                                        >
                                            {cajasAbiertas.map((caja) => (
                                                <option key={caja.id} value={caja.id}>
                                                    {caja.nombre || `Caja #${caja.id}`} - {new Date(caja.fecha_apertura).toLocaleString()}
                                                </option>
                                            ))}
                                        </select>
                                    ) : (
                                        <div className="px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-sm text-red-600">
                                            Sin cajas abiertas
                                        </div>
                                    )}
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Cliente</label>
                                    <select
                                        value={clienteId}
                                        onChange={(e) => setClienteId(e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-md"
                                    >
                                        <option value="">Cliente genérico / consumidor final</option>
                                        {clientes.map((cliente) => (
                                            <option key={cliente.id} value={cliente.id}>
                                                {cliente.nombre} {cliente.apellido}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Tipo de Pago</label>
                                    <select
                                        value={tipoPago}
                                        onChange={(e) => {
                                            setTipoPago(e.target.value);
                                            // Limpiar campos de pago mixto si cambia el tipo
                                            if (e.target.value !== 'mixto') {
                                                setMontoTarjeta('');
                                                setMontoEfectivo('');
                                                setRecargoCuotas('');
                                            }
                                            if (e.target.value !== 'mixto' && e.target.value !== 'tarjeta') {
                                                setCuotas('');
                                            }
                                        }}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-md"
                                    >
                                        <option value="efectivo">Efectivo</option>
                                        <option value="tarjeta">Tarjeta</option>
                                        <option value="cuenta_corriente">Cuenta Corriente</option>
                                        <option value="mixto">Mixto</option>
                                    </select>
                                </div>
                            </div>

                            {/* Campos para pago mixto */}
                            {tipoPago === 'mixto' && (
                                <div className="bg-blue-50 border border-blue-200 rounded-md p-3 sm:p-4">
                                    <h4 className="font-semibold text-gray-800 mb-3 text-sm sm:text-base">Detalles de Pago Mixto</h4>
                                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                Monto en Tarjeta (opcional)
                                            </label>
                                            <input
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                value={montoTarjeta}
                                                onChange={(e) => setMontoTarjeta(e.target.value)}
                                                placeholder="0.00"
                                                className="w-full px-3 py-2 border border-gray-300 rounded-md"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                Monto en Efectivo (opcional)
                                            </label>
                                            <input
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                value={montoEfectivo}
                                                onChange={(e) => setMontoEfectivo(e.target.value)}
                                                placeholder="0.00"
                                                className="w-full px-3 py-2 border border-gray-300 rounded-md"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                Cuotas (opcional)
                                            </label>
                                            <input
                                                type="number"
                                                min="1"
                                                max="24"
                                                value={cuotas}
                                                onChange={(e) => setCuotas(e.target.value)}
                                                placeholder="Ej: 3, 6, 12"
                                                className="w-full px-3 py-2 border border-gray-300 rounded-md"
                                            />
                                            {cuotas && parseFloat(cuotas) > 0 && (() => {
                                                const montoTarjetaNum = parseFloat(montoTarjeta) || 0;
                                                const montoEfectivoNum = parseFloat(montoEfectivo) || 0;
                                                const totalFinal = calcularTotal();
                                                const montoRestante = totalFinal - montoEfectivoNum - montoTarjetaNum;
                                                const montoCuotas = montoTarjetaNum > 0 ? montoTarjetaNum : (montoRestante > 0 ? montoRestante : 0);
                                                
                                                if (montoCuotas > 0) {
                                                    return (
                                                        <p className="mt-1 text-xs text-gray-600">
                                                            Monto por cuota: <span className="font-semibold text-blue-600">
                                                                ${(montoCuotas / parseFloat(cuotas)).toFixed(2)}
                                                            </span>
                                                        </p>
                                                    );
                                                }
                                                return null;
                                            })()}
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                Interés por cuotas (%)
                                            </label>
                                            <input
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                max="100"
                                                value={recargoCuotas}
                                                onChange={(e) => setRecargoCuotas(e.target.value)}
                                                placeholder="Ej: 5.5, 10, 15"
                                                className="w-full px-3 py-2 border border-gray-300 rounded-md"
                                            />
                                            {recargoCuotas && parseFloat(recargoCuotas) > 0 && cuotas && parseFloat(cuotas) > 0 && (() => {
                                                const montoTarjetaNum = parseFloat(montoTarjeta) || 0;
                                                const montoEfectivoNum = parseFloat(montoEfectivo) || 0;
                                                const totalFinal = calcularTotal();
                                                const montoRestante = totalFinal - montoEfectivoNum - montoTarjetaNum;
                                                const montoCuotas = montoTarjetaNum > 0 ? montoTarjetaNum : (montoRestante > 0 ? montoRestante : 0);
                                                
                                                if (montoCuotas > 0) {
                                                    const recargoTotal = (montoCuotas * parseFloat(recargoCuotas)) / 100;
                                                    const montoConRecargo = montoCuotas + recargoTotal;
                                                    
                                                    return (
                                                        <div className="mt-2 space-y-1">
                                                            <p className="text-xs text-gray-600">
                                                                Interés total: <span className="font-semibold text-orange-600">
                                                                    ${recargoTotal.toFixed(2)}
                                                                </span>
                                                            </p>
                                                            <p className="text-xs text-gray-600">
                                                                Monto con interés: <span className="font-semibold text-green-600">
                                                                    ${montoConRecargo.toFixed(2)}
                                                                </span>
                                                            </p>
                                                            <p className="text-xs text-gray-600">
                                                                Monto por cuota con interés: <span className="font-semibold text-blue-600">
                                                                    ${(montoConRecargo / parseFloat(cuotas)).toFixed(2)}
                                                                </span>
                                                            </p>
                                                        </div>
                                                    );
                                                }
                                                return null;
                                            })()}
                                        </div>
                                    </div>
                                    <div className="mt-2 text-xs text-gray-600">
                                        <div className="mb-1">
                                            Total a pagar: <span className="font-semibold">${calcularTotal().toFixed(2)}</span>
                                        </div>
                                        {(() => {
                                            const montoTarjetaNum = parseFloat(montoTarjeta) || 0;
                                            const montoEfectivoNum = parseFloat(montoEfectivo) || 0;
                                            const sumaMontos = montoTarjetaNum + montoEfectivoNum;
                                            const totalFinal = calcularTotal();
                                            const restante = totalFinal - sumaMontos;
                                            
                                            if (montoTarjetaNum > 0 || montoEfectivoNum > 0) {
                                                return (
                                                    <div className="space-y-1">
                                                        <div>
                                                            Suma pagada: <span className="font-semibold">${sumaMontos.toFixed(2)}</span>
                                                            {montoTarjetaNum > 0 && <span className="ml-2 text-gray-500">(Tarjeta: ${montoTarjetaNum.toFixed(2)})</span>}
                                                            {montoEfectivoNum > 0 && <span className="ml-2 text-gray-500">(Efectivo: ${montoEfectivoNum.toFixed(2)})</span>}
                                                        </div>
                                                        {restante > 0 && (
                                                            <div className="text-blue-600">
                                                                Restante: <span className="font-semibold">${restante.toFixed(2)}</span>
                                                                {cuotas && parseFloat(cuotas) > 0 && <span className="ml-1">(en {cuotas} cuotas)</span>}
                                                            </div>
                                                        )}
                                                        {restante < -0.01 && (
                                                            <div className="text-red-600">
                                                                Excede: <span className="font-semibold">${Math.abs(restante).toFixed(2)}</span>
                                                            </div>
                                                        )}
                                                    </div>
                                                );
                                            }
                                            return null;
                                        })()}
                                    </div>
                                </div>
                            )}

                            {/* Campo de cuotas para pago con tarjeta (no mixto) */}
                            {tipoPago === 'tarjeta' && (
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Cuotas (opcional)
                                    </label>
                                    <input
                                        type="number"
                                        min="1"
                                        max="24"
                                        value={cuotas}
                                        onChange={(e) => setCuotas(e.target.value)}
                                        placeholder="Ej: 3, 6, 12"
                                        className="w-full px-3 py-2 border border-gray-300 rounded-md"
                                    />
                                    {cuotas && parseFloat(cuotas) > 0 && (
                                        <p className="mt-1 text-xs text-gray-600">
                                            Monto por cuota: <span className="font-semibold text-blue-600">
                                                ${(calcularTotal() / parseFloat(cuotas)).toFixed(2)}
                                            </span>
                                            {' '}(Total: ${calcularTotal().toFixed(2)})
                                        </p>
                                    )}
                                </div>
                            )}

                            <div className="bg-gray-50 border border-gray-200 rounded-md p-4">
                                <div className="flex justify-between items-center mb-3">
                                    <h4 className="font-semibold text-gray-800">Items de la Venta</h4>
                                    <button
                                        type="button"
                                        onClick={handleAddItem}
                                        className="px-3 py-1 bg-blue-600 text-white text-sm rounded hover:bg-blue-700"
                                    >
                                        + Agregar Producto
                                    </button>
                                </div>
                                <div className="overflow-x-auto -mx-2 sm:mx-0">
                                    <table className="min-w-full text-sm">
                                        <thead>
                                            <tr className="text-left text-xs font-medium text-gray-500 uppercase bg-gray-100">
                                                <th className="px-2 sm:px-3 py-2">Producto</th>
                                                <th className="px-2 sm:px-3 py-2 hidden sm:table-cell">Precio</th>
                                                <th className="px-2 sm:px-3 py-2">Cantidad</th>
                                                <th className="px-2 sm:px-3 py-2">Subtotal</th>
                                                <th className="px-2 sm:px-3 py-2"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {items.map((item, index) => {
                                                const prod = obtenerProducto(item.producto_id);
                                                const subtotal = calcularSubtotal(item);
                                                return (
                                                    <tr key={index} className="border-t">
                                                        <td className="px-2 sm:px-3 py-2">
                                                            <select
                                                                value={item.producto_id}
                                                                onChange={(e) => handleChangeItem(index, 'producto_id', e.target.value)}
                                                                className="w-full px-2 py-1 border border-gray-300 rounded-md text-sm"
                                                                required
                                                            >
                                                                <option value="">Seleccionar...</option>
                                                                {productos.map((producto) => (
                                                                    <option key={producto.id} value={producto.id}>
                                                                        {producto.nombre}
                                                                    </option>
                                                                ))}
                                                            </select>
                                                            {prod && (
                                                                <div className="text-xs text-gray-500 sm:hidden mt-1">
                                                                    ${parseFloat(prod.precio_venta || 0).toFixed(2)}
                                                                </div>
                                                            )}
                                                        </td>
                                                        <td className="px-2 sm:px-3 py-2 whitespace-nowrap hidden sm:table-cell">
                                                            {prod ? `$${parseFloat(prod.precio_venta || 0).toFixed(2)}` : '-'}
                                                        </td>
                                                        <td className="px-2 sm:px-3 py-2">
                                                            <input
                                                                type="number"
                                                                min="1"
                                                                value={item.cantidad}
                                                                onChange={(e) => handleChangeItem(index, 'cantidad', e.target.value)}
                                                                className="w-full sm:w-24 px-2 py-1 border border-gray-300 rounded-md text-sm"
                                                                required
                                                            />
                                                        </td>
                                                        <td className="px-2 sm:px-3 py-2 whitespace-nowrap font-semibold text-sm">
                                                            ${subtotal.toFixed(2)}
                                                        </td>
                                                        <td className="px-2 sm:px-3 py-2 text-right">
                                                            {items.length > 1 && (
                                                                <button
                                                                    type="button"
                                                                    onClick={() => handleRemoveItem(index)}
                                                                    className="text-red-600 hover:text-red-800 text-xs"
                                                                >
                                                                    Quitar
                                                                </button>
                                                            )}
                                                        </td>
                                                    </tr>
                                                );
                                            })}
                                        </tbody>
                                    </table>
                                </div>
                                <div className="mt-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                                    <div className="flex items-center gap-2">
                                        <label className="text-sm font-medium text-gray-700">Descuento:</label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            value={descuento}
                                            onChange={(e) => setDescuento(e.target.value)}
                                            className="w-32 px-2 py-1 border border-gray-300 rounded-md"
                                        />
                                    </div>
                                    <div className="text-right">
                                        <div className="text-sm text-gray-600">
                                            Total bruto:{' '}
                                            <span className="font-medium">
                                                ${items.reduce((acc, item) => acc + calcularSubtotal(item), 0).toFixed(2)}
                                            </span>
                                        </div>
                                        <div className="text-lg font-bold text-gray-900">
                                            Total final: ${calcularTotal().toFixed(2)}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Adjuntar archivos (opcional)
                                </label>
                                <input
                                    type="file"
                                    multiple
                                    onChange={handleAdjuntosChange}
                                    className="block w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                />
                                {adjuntos.length > 0 && (
                                    <p className="mt-1 text-xs text-gray-500">
                                        {adjuntos.length} archivo(s) seleccionado(s)
                                    </p>
                                )}
                            </div>

                            <div className="flex flex-col sm:flex-row justify-end gap-2 pt-4 border-t">
                                <button
                                    type="button"
                                    onClick={() => {
                                        setShowModal(false);
                                        resetForm();
                                    }}
                                    className="w-full sm:w-auto px-6 py-2 border border-gray-300 rounded-md hover:bg-gray-50"
                                    disabled={loadingSubmit}
                                >
                                    Cancelar
                                </button>
                                <button
                                    type="submit"
                                    className="w-full sm:w-auto px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed"
                                    disabled={loadingSubmit || !cajaSeleccionada}
                                >
                                    {loadingSubmit ? 'Guardando...' : 'Confirmar Venta'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
}
