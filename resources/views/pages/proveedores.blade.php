@extends('layouts.app')

@section('title', 'Proveedores - Inventario Inteligente')
@section('page-title', 'Proveedores')

@section('content')
<div x-data="proveedores()" x-init="init()" class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
        <h1 class="text-2xl sm:text-3xl font-bold">Proveedores</h1>
        <button
            @click="openModal()"
            class="w-full sm:w-auto bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
        >
            Nuevo Proveedor
        </button>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <template x-if="loading">
            <div class="p-8 text-center text-gray-500">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <p class="mt-2">Cargando proveedores...</p>
            </div>
        </template>
        
        <template x-if="!loading">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase hidden sm:table-cell">CUIT</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase hidden md:table-cell">Teléfono</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="prov in proveedores" :key="prov.id">
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-900" x-text="prov.nombre"></div>
                                    <div class="text-sm text-gray-500 sm:hidden" x-text="'CUIT: ' + (prov.cuit || '-')"></div>
                                    <div class="text-sm text-gray-500 sm:hidden md:hidden" x-text="'Tel: ' + (prov.telefono || '-')"></div>
                                </td>
                                <td class="px-6 py-4 hidden sm:table-cell" x-text="prov.cuit || '-'"></td>
                                <td class="px-6 py-4 hidden md:table-cell" x-text="prov.telefono || '-'"></td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-col sm:flex-row gap-2">
                                        <button @click="edit(prov)" class="text-blue-600 hover:text-blue-800 text-sm">Editar</button>
                                        <button @click="remove(prov.id)" class="text-red-600 hover:text-red-800 text-sm">Eliminar</button>
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
    <div x-show="showModal" 
         x-cloak
         class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50 p-4"
         @click.away="closeModal()">
        <div class="bg-white rounded-lg w-full max-w-md max-h-[90vh] overflow-y-auto" @click.stop>
            <div class="sticky top-0 bg-white border-b px-6 py-4">
                <h3 class="text-lg font-bold" x-text="editing ? 'Editar' : 'Nuevo' + ' Proveedor'"></h3>
            </div>
            <form @submit.prevent="save()" class="p-6 space-y-3">
                <input type="text" x-model="formData.nombre" placeholder="Nombre" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                <input type="text" x-model="formData.razon_social" placeholder="Razón Social" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                <input type="text" x-model="formData.cuit" placeholder="CUIT" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
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
function proveedores() {
    return {
        proveedores: [],
        loading: true,
        showModal: false,
        editing: null,
        formData: { nombre: '', razon_social: '', cuit: '', telefono: '', email: '', direccion: '', activo: true },
        
        async init() {
            await this.fetch();
        },
        
        async fetch() {
            try {
                this.loading = true;
                const token = localStorage.getItem('token');
                const response = await axios.get('/api/proveedores', {
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                this.proveedores = response.data?.data || response.data || [];
            } catch (error) {
                console.error('Error:', error);
            } finally {
                this.loading = false;
            }
        },
        
        openModal() {
            this.editing = null;
            this.formData = { nombre: '', razon_social: '', cuit: '', telefono: '', email: '', direccion: '', activo: true };
            this.showModal = true;
        },
        
        edit(prov) {
            this.editing = prov.id;
            this.formData = { ...prov };
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
                    await axios.put(`/api/proveedores/${this.editing}`, this.formData, {
                        headers: { 'Authorization': `Bearer ${token}` }
                    });
                } else {
                    await axios.post('/api/proveedores', this.formData, {
                        headers: { 'Authorization': `Bearer ${token}` }
                    });
                }
                await this.fetch();
                this.closeModal();
            } catch (error) {
                alert('Error al guardar');
            }
        },
        
        async remove(id) {
            if (!confirm('¿Está seguro de eliminar este proveedor?')) return;
            try {
                const token = localStorage.getItem('token');
                await axios.delete(`/api/proveedores/${id}`, {
                    headers: { 'Authorization': `Bearer ${token}` }
                });
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
