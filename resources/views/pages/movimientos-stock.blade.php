@extends('layouts.app')

@section('title', 'Movimientos de Stock - Inventario Inteligente')
@section('page-title', 'Movimientos de Stock')

@section('content')
<div x-data="movimientosStock()" x-init="init()" class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold">Movimientos de Stock</h1>
        <button @click="openModal()" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
            Nuevo Movimiento
        </button>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <template x-if="loading">
            <div class="p-8 text-center text-gray-500">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <p class="mt-2">Cargando movimientos...</p>
            </div>
        </template>
        
        <template x-if="!loading">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Producto</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cantidad</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Motivo</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="mov in movimientos" :key="mov.id">
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="new Date(mov.created_at).toLocaleString()"></td>
                                <td class="px-6 py-4 text-sm" x-text="mov.producto?.nombre || '-'"></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 rounded-full text-xs" 
                                          :class="mov.tipo === 'entrada' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                          x-text="mov.tipo === 'entrada' ? 'Entrada' : 'Salida'"></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium" x-text="mov.cantidad"></td>
                                <td class="px-6 py-4 text-sm text-gray-500" x-text="mov.motivo || '-'"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </template>
    </div>

    <!-- Modal -->
    <div x-show="showModal" x-cloak class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50 p-4" @click.away="closeModal()">
        <div class="bg-white rounded-lg w-full max-w-md" @click.stop>
            <div class="px-6 py-4 border-b">
                <h3 class="text-lg font-bold">Nuevo Movimiento</h3>
            </div>
            <form @submit.prevent="save()" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Producto *</label>
                    <select x-model="formData.producto_id" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                        <option value="">Seleccionar...</option>
                        <template x-for="prod in productos" :key="prod.id">
                            <option :value="prod.id" x-text="prod.nombre"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo *</label>
                    <select x-model="formData.tipo" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                        <option value="entrada">Entrada</option>
                        <option value="salida">Salida</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cantidad *</label>
                    <input type="number" x-model.number="formData.cantidad" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Motivo</label>
                    <input type="text" x-model="formData.motivo" class="w-full px-3 py-2 border border-gray-300 rounded-md">
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
function movimientosStock() {
    return {
        movimientos: [],
        productos: [],
        loading: true,
        showModal: false,
        formData: { producto_id: '', tipo: 'entrada', cantidad: 0, motivo: '', observaciones: '' },
        
        async init() {
            await Promise.all([this.fetch(), this.fetchProductos()]);
        },
        
        async fetch() {
            try {
                this.loading = true;
                const token = localStorage.getItem('token');
                const response = await axios.get('/api/movimientos-stock', {
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                this.movimientos = response.data?.data || response.data || [];
            } catch (error) {
                console.error('Error:', error);
            } finally {
                this.loading = false;
            }
        },
        
        async fetchProductos() {
            try {
                const token = localStorage.getItem('token');
                const response = await axios.get('/api/productos', {
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                this.productos = response.data?.data || response.data || [];
            } catch (error) {
                console.error('Error:', error);
            }
        },
        
        openModal() {
            this.showModal = true;
            this.formData = { producto_id: '', tipo: 'entrada', cantidad: 0, motivo: '', observaciones: '' };
        },
        
        closeModal() {
            this.showModal = false;
        },
        
        async save() {
            try {
                const token = localStorage.getItem('token');
                await axios.post('/api/movimientos-stock', this.formData, {
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                await this.fetch();
                this.closeModal();
            } catch (error) {
                alert(error.response?.data?.message || 'Error al crear movimiento');
            }
        }
    }
}
</script>
@endpush
@endsection
