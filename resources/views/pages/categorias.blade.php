@extends('layouts.app')

@section('title', 'Categorías - Inventario Inteligente')
@section('page-title', 'Categorías')

@section('content')
<div x-data="categorias()" x-init="init()" class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold">Categorías</h1>
        <button
            @click="openModal()"
            class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
        >
            Nueva Categoría
        </button>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <template x-if="loading">
            <div class="p-8 text-center text-gray-500">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <p class="mt-2">Cargando categorías...</p>
            </div>
        </template>
        
        <template x-if="!loading">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Descripción</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="categoria in categorias" :key="categoria.id">
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900" x-text="categoria.nombre"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="categoria.descripcion || '-'"></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs rounded-full" 
                                          :class="categoria.activa ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                          x-text="categoria.activa ? 'Activa' : 'Inactiva'"></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button @click="edit(categoria)" class="text-blue-600 hover:text-blue-900 mr-3">Editar</button>
                                    <button @click="remove(categoria.id)" class="text-red-600 hover:text-red-900">Eliminar</button>
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
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
         x-cloak
         @click.away="closeModal()">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white" @click.stop>
            <h3 class="text-lg font-bold mb-4" x-text="editing ? 'Editar' : 'Nueva' + ' Categoría'"></h3>
            <form @submit.prevent="save()">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
                    <input type="text" x-model="formData.nombre" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                    <textarea x-model="formData.descripcion" class="w-full px-3 py-2 border border-gray-300 rounded-md" rows="3"></textarea>
                </div>
                <div class="mb-4">
                    <label class="flex items-center">
                        <input type="checkbox" x-model="formData.activa" class="mr-2">
                        <span class="text-sm text-gray-700">Activa</span>
                    </label>
                </div>
                <div class="flex justify-end">
                    <button type="button" @click="closeModal()" class="mr-2 px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50">Cancelar</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function categorias() {
    return {
        categorias: [],
        loading: true,
        showModal: false,
        editing: null,
        formData: { nombre: '', descripcion: '', activa: true },
        
        async init() {
            await this.fetch();
        },
        
        async fetch() {
            try {
                this.loading = true;
                const response = await axios.get('/api/categorias', {
                    withCredentials: true
                });
                this.categorias = response.data?.data || response.data || [];
            } catch (error) {
                console.error('Error al cargar categorías:', error);
                alert('Error al cargar categorías: ' + (error.response?.data?.message || error.message));
            } finally {
                this.loading = false;
            }
        },
        
        openModal() {
            this.editing = null;
            this.formData = { nombre: '', descripcion: '', activa: true };
            this.showModal = true;
        },
        
        edit(categoria) {
            this.editing = categoria.id;
            this.formData = { ...categoria };
            this.showModal = true;
        },
        
        closeModal() {
            this.showModal = false;
            this.editing = null;
            this.formData = { nombre: '', descripcion: '', activa: true };
        },
        
        async save() {
            try {
                if (this.editing) {
                    await axios.put(`/api/categorias/${this.editing}`, this.formData, {
                        withCredentials: true
                    });
                } else {
                    await axios.post('/api/categorias', this.formData, {
                        withCredentials: true
                    });
                }
                await this.fetch();
                this.closeModal();
            } catch (error) {
                console.error('Error al guardar:', error);
                alert('Error al guardar la categoría: ' + (error.response?.data?.message || error.message));
            }
        },
        
        async remove(id) {
            if (!confirm('¿Está seguro de eliminar esta categoría?')) return;
            try {
                await axios.delete(`/api/categorias/${id}`, {
                    withCredentials: true
                });
                await this.fetch();
            } catch (error) {
                console.error('Error al eliminar:', error);
                alert('Error al eliminar la categoría: ' + (error.response?.data?.message || error.message));
            }
        }
    }
}
</script>
@endpush
@endsection
