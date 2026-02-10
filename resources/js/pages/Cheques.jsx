import React, { useState, useEffect } from 'react';
import axios from 'axios';

export default function Cheques() {
    const [cheques, setCheques] = useState([]);
    const [clientes, setClientes] = useState([]);
    const [proveedores, setProveedores] = useState([]);
    const [cajas, setCajas] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showModal, setShowModal] = useState(false);
    const [editing, setEditing] = useState(null);
    const [vista, setVista] = useState('lista'); // 'lista', 'calendario', 'vencimientos'
    const [vistaCalendario, setVistaCalendario] = useState('mes'); // 'mes' o 'dia'
    const [fechaActual, setFechaActual] = useState(new Date());
    const [chequesCalendario, setChequesCalendario] = useState({});
    const [error, setError] = useState('');
    const [success, setSuccess] = useState('');
    const [filtros, setFiltros] = useState({
        estado: '',
        tipo: '',
        search: '',
        proximos_vencer: false,
        vencidos: false,
    });

    const [formData, setFormData] = useState({
        numero_cheque: '',
        banco: '',
        fecha_ingreso: new Date().toISOString().split('T')[0],
        fecha_vencimiento: '',
        monto: 0,
        tipo: 'recibido',
        estado: 'pendiente',
        cliente_id: '',
        proveedor_id: '',
        caja_id: '',
        fecha_cobro: '',
        observaciones: '',
    });

    useEffect(() => {
        fetchClientes();
        fetchProveedores();
        fetchCajas();
        if (vista === 'calendario') {
            fetchChequesCalendario();
        } else if (vista === 'vencimientos') {
            fetchChequesProximosVencer();
        } else {
            fetchCheques();
        }
    }, [vista, filtros]);

    useEffect(() => {
        if (vista === 'calendario') {
            fetchChequesCalendario();
        }
    }, [fechaActual, vistaCalendario]);

    const fetchCheques = async () => {
        try {
            setLoading(true);
            setError('');
            const params = {};
            if (filtros.estado) params.estado = filtros.estado;
            if (filtros.tipo) params.tipo = filtros.tipo;
            if (filtros.search) params.search = filtros.search;
            if (filtros.proximos_vencer) params.proximos_vencer = true;
            if (filtros.vencidos) params.vencidos = true;

            const response = await axios.get('/cheques', { params });
            const chequesData = response.data?.data || response.data || [];
            setCheques(Array.isArray(chequesData) ? chequesData : []);
        } catch (error) {
            console.error('Error al cargar cheques:', error);
            setError('Error al cargar los cheques');
            setCheques([]);
        } finally {
            setLoading(false);
        }
    };

    const fetchChequesCalendario = async () => {
        try {
            setLoading(true);
            const params = {
                mes: fechaActual.getMonth() + 1,
                ano: fechaActual.getFullYear(),
            };

            const response = await axios.get('/cheques-por-mes', { params });
            setChequesCalendario(response.data || {});
        } catch (error) {
            console.error('Error al cargar cheques del calendario:', error);
            setError('Error al cargar cheques del calendario');
        } finally {
            setLoading(false);
        }
    };

    const fetchChequesProximosVencer = async () => {
        try {
            setLoading(true);
            const response = await axios.get('/cheques-proximos-vencer', { params: { dias: 30 } });
            setCheques(Array.isArray(response.data) ? response.data : []);
        } catch (error) {
            console.error('Error al cargar cheques próximos a vencer:', error);
            setError('Error al cargar cheques próximos a vencer');
            setCheques([]);
        } finally {
            setLoading(false);
        }
    };

    const fetchClientes = async () => {
        try {
            const response = await axios.get('/clientes');
            const clientesData = response.data?.data || response.data || [];
            setClientes(Array.isArray(clientesData) ? clientesData.filter(c => c.activo !== false) : []);
        } catch (error) {
            console.error('Error al cargar clientes:', error);
        }
    };

    const fetchProveedores = async () => {
        try {
            const response = await axios.get('/proveedores');
            const proveedoresData = response.data?.data || response.data || [];
            setProveedores(Array.isArray(proveedoresData) ? proveedoresData.filter(p => p.activo !== false) : []);
        } catch (error) {
            console.error('Error al cargar proveedores:', error);
        }
    };

    const fetchCajas = async () => {
        try {
            const response = await axios.get('/cajas');
            const cajasData = response.data?.data || response.data || [];
            setCajas(Array.isArray(cajasData) ? cajasData : []);
        } catch (error) {
            console.error('Error al cargar cajas:', error);
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            setError('');
            setSuccess('');

            const dataToSend = { ...formData };
            if (!dataToSend.cliente_id) dataToSend.cliente_id = null;
            if (!dataToSend.proveedor_id) dataToSend.proveedor_id = null;
            if (!dataToSend.caja_id) dataToSend.caja_id = null;
            if (!dataToSend.fecha_cobro) dataToSend.fecha_cobro = null;

            if (editing) {
                await axios.put(`/cheques/${editing}`, dataToSend);
                setSuccess('Cheque actualizado correctamente');
            } else {
                await axios.post('/cheques', dataToSend);
                setSuccess('Cheque creado correctamente');
            }

            setShowModal(false);
            setEditing(null);
            resetForm();

            if (vista === 'calendario') {
                fetchChequesCalendario();
            } else if (vista === 'vencimientos') {
                fetchChequesProximosVencer();
            } else {
                fetchCheques();
            }

            setTimeout(() => setSuccess(''), 3000);
        } catch (error) {
            console.error('Error al guardar cheque:', error);
            const errorMessage = error.response?.data?.message || 'Error al guardar el cheque';
            setError(errorMessage);
        }
    };

    const resetForm = () => {
        setFormData({
            numero_cheque: '',
            banco: '',
            fecha_ingreso: new Date().toISOString().split('T')[0],
            fecha_vencimiento: '',
            monto: 0,
            tipo: 'recibido',
            estado: 'pendiente',
            cliente_id: '',
            proveedor_id: '',
            caja_id: '',
            fecha_cobro: '',
            observaciones: '',
        });
    };

    const handleEdit = (cheque) => {
        setEditing(cheque.id);
        setFormData({
            numero_cheque: cheque.numero_cheque || '',
            banco: cheque.banco || '',
            fecha_ingreso: cheque.fecha_ingreso ? cheque.fecha_ingreso.split('T')[0] : new Date().toISOString().split('T')[0],
            fecha_vencimiento: cheque.fecha_vencimiento ? cheque.fecha_vencimiento.split('T')[0] : '',
            monto: cheque.monto || 0,
            tipo: cheque.tipo || 'recibido',
            estado: cheque.estado || 'pendiente',
            cliente_id: cheque.cliente_id || '',
            proveedor_id: cheque.proveedor_id || '',
            caja_id: cheque.caja_id || '',
            fecha_cobro: cheque.fecha_cobro ? cheque.fecha_cobro.split('T')[0] : '',
            observaciones: cheque.observaciones || '',
        });
        setShowModal(true);
    };

    const handleDelete = async (id) => {
        if (!window.confirm('¿Está seguro de eliminar este cheque?')) return;
        try {
            await axios.delete(`/cheques/${id}`);
            setSuccess('Cheque eliminado correctamente');
            if (vista === 'calendario') {
                fetchChequesCalendario();
            } else if (vista === 'vencimientos') {
                fetchChequesProximosVencer();
            } else {
                fetchCheques();
            }
            setTimeout(() => setSuccess(''), 3000);
        } catch (error) {
            console.error('Error al eliminar cheque:', error);
            setError('Error al eliminar el cheque');
        }
    };

    const handleMarcarCobrado = async (id) => {
        try {
            await axios.post(`/cheques/${id}/marcar-cobrado`);
            setSuccess('Cheque marcado como cobrado');
            if (vista === 'calendario') {
                fetchChequesCalendario();
            } else if (vista === 'vencimientos') {
                fetchChequesProximosVencer();
            } else {
                fetchCheques();
            }
            setTimeout(() => setSuccess(''), 3000);
        } catch (error) {
            console.error('Error al marcar cheque como cobrado:', error);
            setError('Error al marcar cheque como cobrado');
        }
    };

    // Funciones para el calendario
    const obtenerDiasDelMes = () => {
        const año = fechaActual.getFullYear();
        const mes = fechaActual.getMonth();
        const primerDia = new Date(año, mes, 1);
        const ultimoDia = new Date(año, mes + 1, 0);
        const diasEnMes = ultimoDia.getDate();
        const diaInicio = primerDia.getDay();

        const dias = [];
        for (let i = 0; i < diaInicio; i++) {
            dias.push(null);
        }
        for (let i = 1; i <= diasEnMes; i++) {
            dias.push(new Date(año, mes, i));
        }
        return dias;
    };

    const obtenerChequesPorFecha = (fecha) => {
        if (!fecha) return [];
        const fechaStr = fecha.toISOString().split('T')[0];
        return chequesCalendario[fechaStr] || [];
    };

    const cambiarMes = (direccion) => {
        const nuevaFecha = new Date(fechaActual);
        nuevaFecha.setMonth(fechaActual.getMonth() + direccion);
        setFechaActual(nuevaFecha);
    };

    const obtenerNombreMes = () => {
        return fechaActual.toLocaleString('es-ES', { month: 'long', year: 'numeric' });
    };

    const obtenerDiasHastaVencimiento = (fechaVencimiento) => {
        const hoy = new Date();
        const vencimiento = new Date(fechaVencimiento);
        const diferencia = Math.ceil((vencimiento - hoy) / (1000 * 60 * 60 * 24));
        return diferencia;
    };

    const obtenerColorEstado = (cheque) => {
        const dias = obtenerDiasHastaVencimiento(cheque.fecha_vencimiento);
        
        if (cheque.estado === 'cobrado') return 'bg-green-100 text-green-800';
        if (cheque.estado === 'vencido' || cheque.estado === 'rechazado') return 'bg-red-100 text-red-800';
        if (dias < 0) return 'bg-red-100 text-red-800';
        if (dias <= 3) return 'bg-orange-100 text-orange-800';
        if (dias <= 7) return 'bg-yellow-100 text-yellow-800';
        return 'bg-blue-100 text-blue-800';
    };

    const chequesList = Array.isArray(cheques) ? cheques : [];

    return (
        <div className="p-3 sm:p-4 lg:p-6">
            <div className="flex flex-col gap-3 sm:gap-0 mb-4 sm:mb-6">
                <h1 className="text-2xl sm:text-3xl font-bold text-gray-800 mb-3 sm:mb-0">Gestión de Cheques</h1>
                <div className="flex flex-wrap gap-2">
                    <button
                        onClick={() => setVista('lista')}
                        className={`px-3 sm:px-4 py-2 rounded text-sm ${vista === 'lista' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700'}`}
                    >
                        Lista
                    </button>
                    <button
                        onClick={() => setVista('calendario')}
                        className={`px-3 sm:px-4 py-2 rounded text-sm ${vista === 'calendario' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700'}`}
                    >
                        Calendario
                    </button>
                    <button
                        onClick={() => setVista('vencimientos')}
                        className={`px-3 sm:px-4 py-2 rounded text-sm ${vista === 'vencimientos' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700'}`}
                    >
                        Próximos Vencimientos
                    </button>
                    <button
                        onClick={() => {
                            setEditing(null);
                            resetForm();
                            setShowModal(true);
                        }}
                        className="bg-green-600 text-white px-3 sm:px-4 py-2 rounded hover:bg-green-700 text-sm whitespace-nowrap"
                    >
                        + Nuevo Cheque
                    </button>
                </div>
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

            {/* Vista Lista */}
            {vista === 'lista' && (
                <div>
                    <div className="bg-white p-3 sm:p-4 rounded-lg shadow mb-4">
                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                            <input
                                type="text"
                                placeholder="Buscar por número, banco, cliente..."
                                value={filtros.search}
                                onChange={(e) => setFiltros({ ...filtros, search: e.target.value })}
                                className="px-3 py-2 border border-gray-300 rounded-md text-sm"
                            />
                            <select
                                value={filtros.estado}
                                onChange={(e) => setFiltros({ ...filtros, estado: e.target.value })}
                                className="px-3 py-2 border border-gray-300 rounded-md text-sm"
                            >
                                <option value="">Todos los estados</option>
                                <option value="pendiente">Pendiente</option>
                                <option value="cobrado">Cobrado</option>
                                <option value="vencido">Vencido</option>
                                <option value="rechazado">Rechazado</option>
                                <option value="depositado">Depositado</option>
                            </select>
                            <select
                                value={filtros.tipo}
                                onChange={(e) => setFiltros({ ...filtros, tipo: e.target.value })}
                                className="px-3 py-2 border border-gray-300 rounded-md text-sm"
                            >
                                <option value="">Todos los tipos</option>
                                <option value="recibido">Recibido</option>
                                <option value="emitido">Emitido</option>
                            </select>
                            <div className="flex flex-col sm:flex-row gap-2">
                                <label className="flex items-center">
                                    <input
                                        type="checkbox"
                                        checked={filtros.proximos_vencer}
                                        onChange={(e) => setFiltros({ ...filtros, proximos_vencer: e.target.checked, vencidos: false })}
                                        className="mr-2"
                                    />
                                    <span className="text-sm">Próximos a vencer</span>
                                </label>
                                <label className="flex items-center">
                                    <input
                                        type="checkbox"
                                        checked={filtros.vencidos}
                                        onChange={(e) => setFiltros({ ...filtros, vencidos: e.target.checked, proximos_vencer: false })}
                                        className="mr-2"
                                    />
                                    <span className="text-sm">Vencidos</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div className="bg-white rounded-lg shadow overflow-hidden">
                        {loading ? (
                            <div className="p-8 text-center">
                                <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                                <p className="mt-2">Cargando cheques...</p>
                            </div>
                        ) : chequesList.length === 0 ? (
                            <div className="p-8 text-center text-gray-500">
                                <p>No hay cheques registrados</p>
                            </div>
                        ) : (
                            <div className="overflow-x-auto -mx-3 sm:mx-0">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nº Cheque</th>
                                            <th className="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Banco</th>
                                            <th className="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Monto</th>
                                            <th className="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase hidden lg:table-cell">Fecha Ingreso</th>
                                            <th className="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase hidden md:table-cell">Fecha Vencimiento</th>
                                            <th className="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Días</th>
                                            <th className="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase hidden lg:table-cell">Tipo</th>
                                            <th className="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase hidden xl:table-cell">Cliente/Proveedor</th>
                                            <th className="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase hidden sm:table-cell">Estado</th>
                                            <th className="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white divide-y divide-gray-200">
                                        {chequesList.map((cheque) => {
                                            const dias = obtenerDiasHastaVencimiento(cheque.fecha_vencimiento);
                                            return (
                                                <tr key={cheque.id} className="hover:bg-gray-50">
                                                    <td className="px-3 sm:px-6 py-4 whitespace-nowrap text-sm font-medium">{cheque.numero_cheque}</td>
                                                    <td className="px-3 sm:px-6 py-4 whitespace-nowrap text-sm">{cheque.banco}</td>
                                                    <td className="px-3 sm:px-6 py-4 whitespace-nowrap text-sm font-medium">${parseFloat(cheque.monto || 0).toFixed(2)}</td>
                                                    <td className="px-3 sm:px-6 py-4 whitespace-nowrap text-sm hidden lg:table-cell">{new Date(cheque.fecha_ingreso).toLocaleDateString()}</td>
                                                    <td className="px-3 sm:px-6 py-4 whitespace-nowrap text-sm hidden md:table-cell">{new Date(cheque.fecha_vencimiento).toLocaleDateString()}</td>
                                                    <td className="px-3 sm:px-6 py-4 whitespace-nowrap text-sm">
                                                        <span className={dias < 0 ? 'text-red-600 font-bold' : dias <= 3 ? 'text-orange-600 font-bold' : 'text-gray-600'}>
                                                            {dias < 0 ? `Vencido (${Math.abs(dias)} días)` : `${dias} días`}
                                                        </span>
                                                    </td>
                                                    <td className="px-3 sm:px-6 py-4 whitespace-nowrap text-sm hidden lg:table-cell">{cheque.tipo}</td>
                                                    <td className="px-3 sm:px-6 py-4 text-sm hidden xl:table-cell">
                                                        {cheque.cliente ? `${cheque.cliente.nombre} ${cheque.cliente.apellido}` : 
                                                         cheque.proveedor ? cheque.proveedor.nombre : '-'}
                                                    </td>
                                                    <td className="px-3 sm:px-6 py-4 whitespace-nowrap hidden sm:table-cell">
                                                        <span className={`px-2 py-1 text-xs rounded-full ${obtenerColorEstado(cheque)}`}>
                                                            {cheque.estado}
                                                        </span>
                                                    </td>
                                                    <td className="px-3 sm:px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                        <div className="flex flex-col sm:flex-row gap-1 sm:gap-0">
                                                            <button onClick={() => handleEdit(cheque)} className="text-blue-600 hover:text-blue-900 text-left sm:text-center">
                                                                Editar
                                                            </button>
                                                            {cheque.estado === 'pendiente' && (
                                                                <button onClick={() => handleMarcarCobrado(cheque.id)} className="text-green-600 hover:text-green-900 text-left sm:text-center sm:ml-3">
                                                                    Cobrar
                                                                </button>
                                                            )}
                                                            <button onClick={() => handleDelete(cheque.id)} className="text-red-600 hover:text-red-900 text-left sm:text-center sm:ml-3">
                                                                Eliminar
                                                            </button>
                                                        </div>
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
            )}

            {/* Vista Calendario */}
            {vista === 'calendario' && (
                <div className="bg-white rounded-lg shadow p-3 sm:p-4 lg:p-6">
                    <div className="flex flex-col sm:flex-row justify-between items-center gap-3 sm:gap-0 mb-4">
                        <button onClick={() => cambiarMes(-1)} className="w-full sm:w-auto px-3 sm:px-4 py-2 bg-gray-200 rounded hover:bg-gray-300 text-sm">
                            ← Mes Anterior
                        </button>
                        <h2 className="text-lg sm:text-2xl font-bold capitalize text-center">{obtenerNombreMes()}</h2>
                        <button onClick={() => cambiarMes(1)} className="w-full sm:w-auto px-3 sm:px-4 py-2 bg-gray-200 rounded hover:bg-gray-300 text-sm">
                            Mes Siguiente →
                        </button>
                    </div>

                    {loading ? (
                        <div className="p-8 text-center">
                            <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                        </div>
                    ) : (
                        <div className="grid grid-cols-7 gap-1 sm:gap-2">
                            {['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'].map((dia) => (
                                <div key={dia} className="text-center font-semibold text-gray-600 py-1 sm:py-2 text-xs sm:text-sm">
                                    {dia}
                                </div>
                            ))}
                            {obtenerDiasDelMes().map((fecha, index) => {
                                const chequesDelDia = fecha ? obtenerChequesPorFecha(fecha) : [];
                                return (
                                    <div
                                        key={index}
                                        className={`min-h-[60px] sm:min-h-[100px] border border-gray-200 p-1 sm:p-2 ${fecha ? 'bg-white' : 'bg-gray-50'}`}
                                    >
                                        {fecha && (
                                            <>
                                                <div className="font-semibold text-gray-700 mb-1">{fecha.getDate()}</div>
                                                <div className="space-y-1">
                                                    {chequesDelDia.slice(0, 2).map((cheque) => (
                                                        <div
                                                            key={cheque.id}
                                                            className={`text-xs p-1 rounded ${obtenerColorEstado(cheque)} cursor-pointer`}
                                                            onClick={() => handleEdit(cheque)}
                                                            title={`${cheque.numero_cheque} - $${cheque.monto}`}
                                                        >
                                                            ${parseFloat(cheque.monto).toFixed(0)}
                                                        </div>
                                                    ))}
                                                    {chequesDelDia.length > 2 && (
                                                        <div className="text-xs text-gray-500">
                                                            +{chequesDelDia.length - 2} más
                                                        </div>
                                                    )}
                                                </div>
                                            </>
                                        )}
                                    </div>
                                );
                            })}
                        </div>
                    )}
                </div>
            )}

            {/* Vista Próximos Vencimientos */}
            {vista === 'vencimientos' && (
                <div className="bg-white rounded-lg shadow overflow-hidden">
                    <div className="p-4 bg-yellow-50 border-b">
                        <h2 className="text-xl font-semibold text-yellow-800">Cheques Próximos a Vencer (30 días)</h2>
                    </div>
                    {loading ? (
                        <div className="p-8 text-center">
                            <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                        </div>
                    ) : chequesList.length === 0 ? (
                        <div className="p-8 text-center text-gray-500">
                            <p>No hay cheques próximos a vencer</p>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nº Cheque</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Banco</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Monto</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha Vencimiento</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Días Restantes</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cliente/Proveedor</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {chequesList.map((cheque) => {
                                        const dias = obtenerDiasHastaVencimiento(cheque.fecha_vencimiento);
                                        return (
                                            <tr key={cheque.id} className="hover:bg-gray-50">
                                                <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">{cheque.numero_cheque}</td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm">{cheque.banco}</td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">${parseFloat(cheque.monto || 0).toFixed(2)}</td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm">{new Date(cheque.fecha_vencimiento).toLocaleDateString()}</td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <span className={`px-2 py-1 text-xs rounded-full font-bold ${
                                                        dias < 0 ? 'bg-red-100 text-red-800' :
                                                        dias <= 3 ? 'bg-orange-100 text-orange-800' :
                                                        dias <= 7 ? 'bg-yellow-100 text-yellow-800' :
                                                        'bg-green-100 text-green-800'
                                                    }`}>
                                                        {dias < 0 ? `Vencido (${Math.abs(dias)} días)` : `${dias} días`}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4 text-sm">
                                                    {cheque.cliente ? `${cheque.cliente.nombre} ${cheque.cliente.apellido}` : 
                                                     cheque.proveedor ? cheque.proveedor.nombre : '-'}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                    <button onClick={() => handleEdit(cheque)} className="text-blue-600 hover:text-blue-900 mr-3">
                                                        Editar
                                                    </button>
                                                    <button onClick={() => handleMarcarCobrado(cheque.id)} className="text-green-600 hover:text-green-900">
                                                        Cobrar
                                                    </button>
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            )}

            {/* Modal Crear/Editar Cheque */}
            {showModal && (
                <div className="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center p-3 sm:p-4">
                    <div className="relative bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                        <div className="sticky top-0 bg-white border-b px-4 sm:px-6 py-4">
                            <h3 className="text-lg sm:text-xl font-bold text-gray-800">{editing ? 'Editar' : 'Nuevo'} Cheque</h3>
                        </div>
                        <form onSubmit={handleSubmit} className="p-4 sm:p-6 space-y-4">
                            {error && (
                                <div className="p-3 bg-red-100 border border-red-400 text-red-700 rounded text-sm">
                                    {error}
                                </div>
                            )}
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Número de Cheque *</label>
                                    <input
                                        type="text"
                                        value={formData.numero_cheque}
                                        onChange={(e) => setFormData({ ...formData, numero_cheque: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        required
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Banco *</label>
                                    <input
                                        type="text"
                                        value={formData.banco}
                                        onChange={(e) => setFormData({ ...formData, banco: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        required
                                    />
                                </div>
                            </div>
                            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Fecha Ingreso *</label>
                                    <input
                                        type="date"
                                        value={formData.fecha_ingreso}
                                        onChange={(e) => setFormData({ ...formData, fecha_ingreso: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-md"
                                        required
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Fecha Vencimiento *</label>
                                    <input
                                        type="date"
                                        value={formData.fecha_vencimiento}
                                        onChange={(e) => setFormData({ ...formData, fecha_vencimiento: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-md"
                                        required
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Monto *</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        value={formData.monto}
                                        onChange={(e) => setFormData({ ...formData, monto: parseFloat(e.target.value) || 0 })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-md"
                                        required
                                    />
                                </div>
                            </div>
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Tipo *</label>
                                    <select
                                        value={formData.tipo}
                                        onChange={(e) => setFormData({ ...formData, tipo: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        required
                                    >
                                        <option value="recibido">Recibido</option>
                                        <option value="emitido">Emitido</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Estado *</label>
                                    <select
                                        value={formData.estado}
                                        onChange={(e) => setFormData({ ...formData, estado: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        required
                                    >
                                        <option value="pendiente">Pendiente</option>
                                        <option value="cobrado">Cobrado</option>
                                        <option value="vencido">Vencido</option>
                                        <option value="rechazado">Rechazado</option>
                                        <option value="depositado">Depositado</option>
                                    </select>
                                </div>
                            </div>
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Cliente</label>
                                    <select
                                        value={formData.cliente_id}
                                        onChange={(e) => setFormData({ ...formData, cliente_id: e.target.value, proveedor_id: '' })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-md"
                                    >
                                        <option value="">Seleccionar cliente...</option>
                                        {clientes.map((cliente) => (
                                            <option key={cliente.id} value={cliente.id}>
                                                {cliente.nombre} {cliente.apellido}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Proveedor</label>
                                    <select
                                        value={formData.proveedor_id}
                                        onChange={(e) => setFormData({ ...formData, proveedor_id: e.target.value, cliente_id: '' })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-md"
                                    >
                                        <option value="">Seleccionar proveedor...</option>
                                        {proveedores.map((proveedor) => (
                                            <option key={proveedor.id} value={proveedor.id}>
                                                {proveedor.nombre}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Caja (opcional)</label>
                                <select
                                    value={formData.caja_id}
                                    onChange={(e) => setFormData({ ...formData, caja_id: e.target.value })}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md"
                                >
                                    <option value="">Seleccionar caja...</option>
                                    {cajas.map((caja) => (
                                        <option key={caja.id} value={caja.id}>
                                            Caja {caja.id} - {caja.estado}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            {formData.estado === 'cobrado' && (
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Fecha de Cobro</label>
                                    <input
                                        type="date"
                                        value={formData.fecha_cobro}
                                        onChange={(e) => setFormData({ ...formData, fecha_cobro: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-md"
                                    />
                                </div>
                            )}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                                <textarea
                                    value={formData.observaciones}
                                    onChange={(e) => setFormData({ ...formData, observaciones: e.target.value })}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md"
                                    rows="3"
                                />
                            </div>
                            <div className="flex flex-col sm:flex-row justify-end gap-2 pt-4 border-t">
                                <button
                                    type="button"
                                    onClick={() => {
                                        setShowModal(false);
                                        setEditing(null);
                                        resetForm();
                                        setError('');
                                    }}
                                    className="w-full sm:w-auto px-6 py-2 border border-gray-300 rounded-md hover:bg-gray-50"
                                >
                                    Cancelar
                                </button>
                                <button
                                    type="submit"
                                    className="w-full sm:w-auto px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
                                >
                                    {editing ? 'Actualizar' : 'Guardar'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
}
