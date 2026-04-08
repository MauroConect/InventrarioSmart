import React, { useState, useEffect, useCallback } from 'react';
import { Navigate } from 'react-router-dom';
import axios from 'axios';
import { useAuth } from '../context/AuthContext';
import { canAccess } from '../utils/permissions';

export default function Usuarios() {
    const { user } = useAuth();
    const [usuarios, setUsuarios] = useState([]);
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [showModal, setShowModal] = useState(false);
    const [editing, setEditing] = useState(null);
    const [page, setPage] = useState(1);
    const [pagination, setPagination] = useState({
        current_page: 1,
        last_page: 1,
        total: 0,
        from: 0,
        to: 0,
    });
    const [error, setError] = useState('');
    const [success, setSuccess] = useState('');
    const [formData, setFormData] = useState({
        name: '',
        email: '',
        password: '',
        role: 'vendedor',
    });

    if (!canAccess(user, 'admin')) {
        return <Navigate to="/" replace />;
    }

    const loadUsuarios = useCallback(async () => {
        try {
            setLoading(true);
            setError('');
            const response = await axios.get('/usuarios', { params: { page } });
            const body = response.data;
            if (body && Array.isArray(body.data) && body.last_page !== undefined) {
                setUsuarios(body.data);
                setPagination({
                    current_page: body.current_page || 1,
                    last_page: body.last_page || 1,
                    total: body.total || 0,
                    from: body.from ?? 0,
                    to: body.to ?? 0,
                });
            } else {
                const rows = Array.isArray(body?.data) ? body.data : Array.isArray(body) ? body : [];
                setUsuarios(rows);
                setPagination({
                    current_page: 1,
                    last_page: 1,
                    total: rows.length,
                    from: rows.length ? 1 : 0,
                    to: rows.length,
                });
            }
        } catch (err) {
            console.error(err);
            setError(err.response?.data?.message || 'Error al cargar usuarios.');
            setUsuarios([]);
        } finally {
            setLoading(false);
        }
    }, [page]);

    useEffect(() => {
        loadUsuarios();
    }, [loadUsuarios]);

    const resetForm = () => {
        setFormData({ name: '', email: '', password: '', role: 'vendedor' });
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setSaving(true);
        setError('');
        setSuccess('');
        try {
            const payload = {
                name: formData.name,
                email: formData.email,
                role: formData.role,
            };
            if (formData.password) {
                payload.password = formData.password;
            }
            if (!editing) {
                if (!formData.password || formData.password.length < 8) {
                    setError('La contraseña es obligatoria y debe tener al menos 8 caracteres.');
                    setSaving(false);
                    return;
                }
                await axios.post('/usuarios', payload);
                setSuccess('Usuario creado correctamente.');
            } else {
                await axios.put(`/usuarios/${editing}`, payload);
                setSuccess('Usuario actualizado correctamente.');
            }
            await loadUsuarios();
            setShowModal(false);
            setEditing(null);
            resetForm();
            setTimeout(() => setSuccess(''), 3500);
        } catch (err) {
            const data = err.response?.data;
            if (data?.errors) {
                const first = Object.values(data.errors)[0];
                setError(Array.isArray(first) ? first[0] : data.message || 'Error al guardar.');
            } else {
                setError(data?.message || 'Error al guardar.');
            }
        } finally {
            setSaving(false);
        }
    };

    const handleDelete = async (id) => {
        if (!window.confirm('¿Eliminar este usuario?')) return;
        setError('');
        setSuccess('');
        try {
            await axios.delete(`/usuarios/${id}`);
            setSuccess('Usuario eliminado.');
            if (usuarios.length === 1 && page > 1) {
                setPage((p) => p - 1);
            } else {
                await loadUsuarios();
            }
            setTimeout(() => setSuccess(''), 3500);
        } catch (err) {
            setError(err.response?.data?.message || 'Error al eliminar.');
        }
    };

    if (loading && usuarios.length === 0 && !error) {
        return (
            <div className="p-6 flex items-center justify-center h-64">
                <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600" />
                <p className="ml-2 text-gray-600">Cargando usuarios...</p>
            </div>
        );
    }

    return (
        <div className="p-3 sm:p-4 lg:p-6">
            <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-6">
                <h1 className="text-2xl sm:text-3xl font-bold text-gray-800">Usuarios</h1>
                <button
                    type="button"
                    onClick={() => {
                        setEditing(null);
                        resetForm();
                        setShowModal(true);
                        setError('');
                    }}
                    className="w-full sm:w-auto bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
                >
                    Nuevo usuario
                </button>
            </div>

            {error && (
                <div className="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">{error}</div>
            )}
            {success && (
                <div className="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">{success}</div>
            )}

            <div className="bg-white rounded-lg shadow overflow-hidden">
                <div className="overflow-x-auto">
                    <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rol</th>
                                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-200">
                            {usuarios.length === 0 ? (
                                <tr>
                                    <td colSpan="4" className="px-4 py-8 text-center text-gray-500">
                                        No hay usuarios para mostrar.
                                    </td>
                                </tr>
                            ) : (
                                usuarios.map((u) => (
                                    <tr key={u.id} className="hover:bg-gray-50">
                                        <td className="px-4 py-3 text-sm font-medium text-gray-900">{u.name}</td>
                                        <td className="px-4 py-3 text-sm text-gray-600">{u.email}</td>
                                        <td className="px-4 py-3 text-sm">
                                            <span
                                                className={`px-2 py-1 text-xs rounded-full font-medium ${
                                                    (u.role || '').toLowerCase() === 'admin'
                                                        ? 'bg-purple-100 text-purple-800'
                                                        : 'bg-gray-100 text-gray-800'
                                                }`}
                                            >
                                                {u.role || 'vendedor'}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3 text-sm">
                                            <div className="flex flex-wrap gap-2">
                                                <button
                                                    type="button"
                                                    onClick={() => {
                                                        setEditing(u.id);
                                                        setFormData({
                                                            name: u.name,
                                                            email: u.email,
                                                            password: '',
                                                            role: (u.role || 'vendedor').toLowerCase(),
                                                        });
                                                        setShowModal(true);
                                                        setError('');
                                                    }}
                                                    className="text-blue-600 hover:text-blue-800"
                                                >
                                                    Editar
                                                </button>
                                                <button
                                                    type="button"
                                                    onClick={() => handleDelete(u.id)}
                                                    className="text-red-600 hover:text-red-800"
                                                >
                                                    Eliminar
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
                {pagination.last_page > 1 && (
                    <div className="flex flex-col sm:flex-row justify-between items-center gap-3 px-4 py-3 bg-gray-50 border-t">
                        <p className="text-sm text-gray-600">
                            {pagination.total > 0
                                ? `Mostrando ${pagination.from}–${pagination.to} de ${pagination.total}`
                                : ''}
                        </p>
                        <div className="flex gap-2 items-center">
                            <button
                                type="button"
                                disabled={page <= 1}
                                onClick={() => setPage((p) => Math.max(1, p - 1))}
                                className="px-3 py-1.5 text-sm border rounded bg-white disabled:opacity-50"
                            >
                                Anterior
                            </button>
                            <span className="text-sm tabular-nums">
                                Página {pagination.current_page} de {pagination.last_page}
                            </span>
                            <button
                                type="button"
                                disabled={page >= pagination.last_page}
                                onClick={() => setPage((p) => Math.min(pagination.last_page, p + 1))}
                                className="px-3 py-1.5 text-sm border rounded bg-white disabled:opacity-50"
                            >
                                Siguiente
                            </button>
                        </div>
                    </div>
                )}
            </div>

            {showModal && (
                <div className="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50 p-4">
                    <div className="bg-white rounded-lg w-full max-w-md max-h-[90vh] overflow-y-auto shadow-xl">
                        <div className="sticky top-0 bg-white border-b px-6 py-4">
                            <h3 className="text-lg font-bold">{editing ? 'Editar usuario' : 'Nuevo usuario'}</h3>
                        </div>
                        <form onSubmit={handleSubmit} className="p-6 space-y-3">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
                                <input
                                    type="text"
                                    value={formData.name}
                                    onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                                    className="w-full px-3 py-2 border rounded-md focus:ring-2 focus:ring-blue-500"
                                    required
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                <input
                                    type="email"
                                    value={formData.email}
                                    onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                                    className="w-full px-3 py-2 border rounded-md focus:ring-2 focus:ring-blue-500"
                                    required
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Contraseña {editing && <span className="text-gray-500 font-normal">(opcional)</span>}
                                    {!editing && <span className="text-red-500"> *</span>}
                                </label>
                                <input
                                    type="password"
                                    value={formData.password}
                                    onChange={(e) => setFormData({ ...formData, password: e.target.value })}
                                    className="w-full px-3 py-2 border rounded-md focus:ring-2 focus:ring-blue-500"
                                    minLength={editing ? undefined : 8}
                                    required={!editing}
                                    autoComplete="new-password"
                                    placeholder="Mínimo 8 caracteres"
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Rol</label>
                                <select
                                    value={formData.role}
                                    onChange={(e) => setFormData({ ...formData, role: e.target.value })}
                                    className="w-full px-3 py-2 border rounded-md focus:ring-2 focus:ring-blue-500"
                                >
                                    <option value="admin">Administrador</option>
                                    <option value="vendedor">Vendedor</option>
                                </select>
                            </div>
                            <div className="flex justify-end gap-2 pt-4 border-t">
                                <button
                                    type="button"
                                    onClick={() => {
                                        setShowModal(false);
                                        setEditing(null);
                                        resetForm();
                                    }}
                                    className="px-4 py-2 border rounded-md hover:bg-gray-50"
                                >
                                    Cancelar
                                </button>
                                <button
                                    type="submit"
                                    disabled={saving}
                                    className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50"
                                >
                                    {saving ? 'Guardando...' : 'Guardar'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
}
