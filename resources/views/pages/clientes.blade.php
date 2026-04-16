@extends('layouts.app')

@section('title', 'Clientes - El Cristo')
@section('page-title', 'Clientes')

@section('content')
<div x-data="clientes(@json(Auth::user()->hasPermission('clientes.manage')))" x-init="init()" class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
        <h1 class="text-2xl sm:text-3xl font-bold">Clientes</h1>
        <button
            type="button"
            x-show="canManage"
            x-cloak
            @click="openModal()"
            class="w-full sm:w-auto bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
        >
            Nuevo Cliente
        </button>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <template x-if="loading">
            <div class="p-8 text-center text-gray-500">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <p class="mt-2">Cargando clientes...</p>
            </div>
        </template>
        
        <template x-if="!loading">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase hidden sm:table-cell">DNI</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase hidden lg:table-cell">CUIT</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase hidden md:table-cell">Teléfono</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase" x-show="canManage">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="cliente in clientes" :key="cliente.id">
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-900" x-text="cliente.nombre + ' ' + cliente.apellido"></div>
                                    <div class="text-sm text-gray-500 sm:hidden" x-text="'DNI: ' + cliente.dni"></div>
                                    <div class="text-sm text-gray-500 sm:hidden md:hidden" x-text="'Tel: ' + (cliente.telefono || '-')"></div>
                                </td>
                                <td class="px-6 py-4 hidden sm:table-cell" x-text="cliente.dni"></td>
                                <td class="px-6 py-4 hidden lg:table-cell text-sm" x-text="cliente.cuit || '-'"></td>
                                <td class="px-6 py-4 hidden md:table-cell" x-text="cliente.telefono || '-'"></td>
                                <td class="px-6 py-4" x-show="canManage">
                                    <div class="flex flex-col sm:flex-row gap-2">
                                        <button type="button" @click="edit(cliente)" class="text-blue-600 hover:text-blue-800 text-sm">Editar</button>
                                        <button type="button" @click="remove(cliente.id)" class="text-red-600 hover:text-red-800 text-sm">Eliminar</button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </template>
    </div>

    <!-- Modal -->
    <div x-show="showModal && canManage"
         x-cloak
         class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50 p-4"
         @click.away="closeModal()">
        <div class="bg-white rounded-lg w-full max-w-md max-h-[90vh] overflow-y-auto" @click.stop>
            <div class="sticky top-0 bg-white border-b px-6 py-4">
                <h3 class="text-lg font-bold" x-text="editing ? 'Editar' : 'Nuevo' + ' Cliente'"></h3>
            </div>
            <form @submit.prevent="save()" class="p-6 space-y-3">
                <input type="text" x-model="formData.nombre" placeholder="Nombre" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                <input type="text" x-model="formData.apellido" placeholder="Apellido" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                <input type="text" x-model="formData.dni" placeholder="DNI" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                <input type="text" x-model="formData.cuit" placeholder="CUIT (11 digitos, opcional - Factura A)" maxlength="11" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                <input type="text" x-model="formData.telefono" placeholder="Teléfono" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                <input type="email" x-model="formData.email" placeholder="Email" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                <textarea x-model="formData.direccion" placeholder="Dirección" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500" rows="3"></textarea>
                <div class="flex flex-col sm:flex-row justify-end gap-2 pt-3 border-t">
                    <button type="button" @click="closeModal()" class="w-full sm:w-auto px-4 py-2 border rounded hover:bg-gray-50">Cancelar</button>
                    <button type="submit" class="w-full sm:w-auto px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function clientes(canManage) {
    return {
        canManage: !!canManage,
        clientes: [],
        loading: true,
        showModal: false,
        editing: null,
        formData: { nombre: '', apellido: '', dni: '', cuit: '', telefono: '', email: '', direccion: '', activo: true },

        async init() {
            await this.fetch();
        },

        async fetch() {
            try {
                this.loading = true;
                const response = await axios.get('/api/clientes');
                this.clientes = response.data?.data || response.data || [];
            } catch (error) {
                console.error('Error:', error);
            } finally {
                this.loading = false;
            }
        },

        openModal() {
            if (!this.canManage) return;
            this.editing = null;
            this.formData = { nombre: '', apellido: '', dni: '', cuit: '', telefono: '', email: '', direccion: '', activo: true };
            this.showModal = true;
        },
        
        edit(cliente) {
            this.editing = cliente.id;
            this.formData = {
                nombre: cliente.nombre ?? '',
                apellido: cliente.apellido ?? '',
                dni: cliente.dni ?? '',
                cuit: cliente.cuit ?? '',
                telefono: cliente.telefono ?? '',
                email: cliente.email ?? '',
                direccion: cliente.direccion ?? '',
                activo: cliente.activo !== false && cliente.activo !== 0,
            };
            this.showModal = true;
        },
        
        closeModal() {
            this.showModal = false;
            this.editing = null;
        },
        
        payloadCliente() {
            return {
                nombre: this.formData.nombre,
                apellido: this.formData.apellido,
                dni: this.formData.dni,
                cuit: this.formData.cuit || null,
                telefono: this.formData.telefono || null,
                email: this.formData.email || null,
                direccion: this.formData.direccion || null,
                activo: this.formData.activo !== false && this.formData.activo !== 0,
            };
        },

        mensajeErrorGuardado(error) {
            const d = error.response?.data;
            if (d?.errors && typeof d.errors === 'object') {
                const first = Object.values(d.errors).flat().find(Boolean);
                if (first) return String(first);
            }
            return d?.message || error.message || 'Error al guardar';
        },

        async save() {
            if (!this.canManage) return;
            try {
                const body = this.payloadCliente();
                if (this.editing) {
                    await axios.put('/api/clientes/' + this.editing, body);
                } else {
                    await axios.post('/api/clientes', body);
                }
                await this.fetch();
                this.closeModal();
            } catch (error) {
                console.error(error);
                alert(this.mensajeErrorGuardado(error));
            }
        },

        async remove(id) {
            if (!this.canManage) return;
            if (!confirm('¿Está seguro de eliminar este cliente?')) return;
            try {
                await axios.delete('/api/clientes/' + id);
                await this.fetch();
            } catch (error) {
                alert('Error al eliminar');
            }
        }
    }
}
</script>
@endpush
@endsection
