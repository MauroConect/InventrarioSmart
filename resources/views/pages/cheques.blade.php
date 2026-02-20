@extends('layouts.app')

@section('title', 'Cheques - Inventario Inteligente')
@section('page-title', 'Cheques')

@section('content')
<div x-data="cheques()" x-init="init()" class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
        <h1 class="text-2xl sm:text-3xl font-bold">Cheques</h1>
        <button @click="openModal()" class="w-full sm:w-auto bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
            Nuevo Cheque
        </button>
    </div>

    <div class="bg-white rounded-lg shadow p-4 mb-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs text-gray-600 mb-1">Buscar</label>
                <input type="text" x-model="filtros.search" @input.debounce.500ms="fetch()" placeholder="Nº cheque, banco..." class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
            </div>
            <div>
                <label class="block text-xs text-gray-600 mb-1">Estado</label>
                <select x-model="filtros.estado" @change="fetch()" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                    <option value="">Todos</option>
                    <option value="pendiente">Pendiente</option>
                    <option value="cobrado">Cobrado</option>
                    <option value="rechazado">Rechazado</option>
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-600 mb-1">Tipo</label>
                <select x-model="filtros.tipo" @change="fetch()" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                    <option value="">Todos</option>
                    <option value="recibido">Recibido</option>
                    <option value="emitido">Emitido</option>
                </select>
            </div>
            <div class="flex items-end">
                <button @click="fetch()" class="w-full px-4 py-2 bg-gray-200 rounded hover:bg-gray-300 text-sm">Filtrar</button>
            </div>
        </div>
    </div>

    <div x-show="error" x-cloak class="p-4 bg-red-100 border border-red-400 text-red-700 rounded" x-text="error"></div>
    <div x-show="success" x-cloak class="p-4 bg-green-100 border border-green-400 text-green-700 rounded" x-text="success"></div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <template x-if="loading">
            <div class="p-8 text-center text-gray-500">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <p class="mt-2">Cargando cheques...</p>
            </div>
        </template>
        
        <template x-if="!loading">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nº Cheque</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Banco</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Monto</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha Vencimiento</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="cheque in cheques" :key="cheque.id">
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium" x-text="cheque.numero_cheque"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm" x-text="cheque.banco"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium" x-text="'$' + parseFloat(cheque.monto || 0).toFixed(2)"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm" x-text="new Date(cheque.fecha_vencimiento).toLocaleDateString()"></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 rounded-full text-xs" 
                                          :class="{
                                              'bg-green-100 text-green-800': cheque.estado === 'cobrado',
                                              'bg-red-100 text-red-800': cheque.estado === 'rechazado',
                                              'bg-yellow-100 text-yellow-800': cheque.estado === 'pendiente'
                                          }"
                                          x-text="cheque.estado"></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button x-show="cheque.estado === 'pendiente'" @click="marcarCobrado(cheque.id)" class="text-green-600 hover:text-green-900 mr-3">Marcar Cobrado</button>
                                    <button @click="edit(cheque)" class="text-blue-600 hover:text-blue-900 mr-3">Editar</button>
                                    <button @click="remove(cheque.id)" class="text-red-600 hover:text-red-900">Eliminar</button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </template>
    </div>

    <!-- Modal -->
    <div x-show="showModal" x-cloak class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50 p-4" @click.away="closeModal()">
        <div class="bg-white rounded-lg w-full max-w-2xl max-h-[90vh] overflow-y-auto" @click.stop>
            <div class="sticky top-0 bg-white border-b px-6 py-4">
                <h3 class="text-lg font-bold" x-text="editing ? 'Editar' : 'Nuevo' + ' Cheque'"></h3>
            </div>
            <form @submit.prevent="save()" class="p-6 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nº Cheque *</label>
                        <input type="text" x-model="formData.numero_cheque" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Banco *</label>
                        <input type="text" x-model="formData.banco" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Ingreso *</label>
                        <input type="date" x-model="formData.fecha_ingreso" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Vencimiento *</label>
                        <input type="date" x-model="formData.fecha_vencimiento" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Monto *</label>
                        <input type="number" step="0.01" x-model.number="formData.monto" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo *</label>
                        <select x-model="formData.tipo" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                            <option value="recibido">Recibido</option>
                            <option value="emitido">Emitido</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cliente</label>
                        <select x-model="formData.cliente_id" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            <option value="">Seleccionar...</option>
                            <template x-for="cliente in clientes" :key="cliente.id">
                                <option :value="cliente.id" x-text="cliente.nombre + ' ' + cliente.apellido"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Proveedor</label>
                        <select x-model="formData.proveedor_id" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            <option value="">Seleccionar...</option>
                            <template x-for="prov in proveedores" :key="prov.id">
                                <option :value="prov.id" x-text="prov.nombre"></option>
                            </template>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                    <textarea x-model="formData.observaciones" class="w-full px-3 py-2 border border-gray-300 rounded-md" rows="3"></textarea>
                </div>
                <div class="flex justify-end gap-2 pt-4 border-t">
                    <button type="button" @click="closeModal()" class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50">Cancelar</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function cheques() {
    return {
        cheques: [],
        clientes: [],
        proveedores: [],
        cajas: [],
        loading: true,
        showModal: false,
        editing: null,
        error: '',
        success: '',
        filtros: { estado: '', tipo: '', search: '' },
        formData: {
            numero_cheque: '', banco: '', fecha_ingreso: new Date().toISOString().split('T')[0],
            fecha_vencimiento: '', monto: 0, tipo: 'recibido', estado: 'pendiente',
            cliente_id: '', proveedor_id: '', caja_id: '', observaciones: ''
        },
        
        async init() {
            await Promise.all([this.fetch(), this.fetchDatos()]);
        },
        
        async fetch() {
            try {
                this.loading = true;
                this.error = '';
                const token = localStorage.getItem('token');
                const params = {};
                if (this.filtros.estado) params.estado = this.filtros.estado;
                if (this.filtros.tipo) params.tipo = this.filtros.tipo;
                if (this.filtros.search) params.search = this.filtros.search;
                
                const response = await axios.get('/api/cheques', {
                    params,
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                this.cheques = response.data?.data || response.data || [];
            } catch (error) {
                console.error('Error:', error);
                this.error = 'Error al cargar cheques';
            } finally {
                this.loading = false;
            }
        },
        
        async fetchDatos() {
            try {
                const token = localStorage.getItem('token');
                const [clientesRes, proveedoresRes, cajasRes] = await Promise.all([
                    axios.get('/api/clientes', { headers: { 'Authorization': `Bearer ${token}` } }),
                    axios.get('/api/proveedores', { headers: { 'Authorization': `Bearer ${token}` } }),
                    axios.get('/api/cajas', { headers: { 'Authorization': `Bearer ${token}` } })
                ]);
                this.clientes = (clientesRes.data?.data || clientesRes.data || []).filter(c => c.activo !== false);
                this.proveedores = (proveedoresRes.data?.data || proveedoresRes.data || []).filter(p => p.activo !== false);
                this.cajas = cajasRes.data?.data || cajasRes.data || [];
            } catch (error) {
                console.error('Error:', error);
            }
        },
        
        openModal() {
            this.editing = null;
            this.formData = {
                numero_cheque: '', banco: '', fecha_ingreso: new Date().toISOString().split('T')[0],
                fecha_vencimiento: '', monto: 0, tipo: 'recibido', estado: 'pendiente',
                cliente_id: '', proveedor_id: '', caja_id: '', observaciones: ''
            };
            this.showModal = true;
        },
        
        edit(cheque) {
            this.editing = cheque.id;
            this.formData = { ...cheque };
            this.showModal = true;
        },
        
        closeModal() {
            this.showModal = false;
            this.editing = null;
        },
        
        async save() {
            try {
                const token = localStorage.getItem('token');
                if (this.editing) {
                    await axios.put(`/api/cheques/${this.editing}`, this.formData, {
                        headers: { 'Authorization': `Bearer ${token}` }
                    });
                } else {
                    await axios.post('/api/cheques', this.formData, {
                        headers: { 'Authorization': `Bearer ${token}` }
                    });
                }
                await this.fetch();
                this.closeModal();
            } catch (error) {
                this.error = error.response?.data?.message || 'Error al guardar';
            }
        },
        
        async marcarCobrado(id) {
            if (!confirm('¿Marcar este cheque como cobrado?')) return;
            try {
                const token = localStorage.getItem('token');
                await axios.post(`/api/cheques/${id}/marcar-cobrado`, {}, {
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                this.success = 'Cheque marcado como cobrado';
                await this.fetch();
                setTimeout(() => this.success = '', 3000);
            } catch (error) {
                this.error = error.response?.data?.message || 'Error al marcar como cobrado';
            }
        },
        
        async remove(id) {
            if (!confirm('¿Está seguro de eliminar este cheque?')) return;
            try {
                const token = localStorage.getItem('token');
                await axios.delete(`/api/cheques/${id}`, {
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                await this.fetch();
            } catch (error) {
                this.error = error.response?.data?.message || 'Error al eliminar';
            }
        }
    }
}
</script>
@endpush
@endsection
