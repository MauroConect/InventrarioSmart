@extends('layouts.app')

@section('title', 'Movimientos de Stock - Inventario Inteligente')
@section('page-title', 'Movimientos de Stock')

@section('content')
<div x-data="movimientosStock()" x-init="init()" class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">Movimientos de Stock</h1>
        <button @click="openModal()" class="w-full sm:w-auto bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
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

        <template x-if="!loading && movimientos.length === 0">
            <div class="p-8 text-center text-gray-500">
                <p class="text-lg">No hay movimientos registrados</p>
            </div>
        </template>

        <template x-if="!loading && movimientos.length > 0">
            <div>
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
                                              :class="tipoBadgeClass(mov)"
                                              x-text="tipoLabel(mov)"></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium" x-text="mov.cantidad"></td>
                                    <td class="px-6 py-4 text-sm text-gray-500" x-text="mov.motivo || '-'"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <div
                    x-show="lastPage > 1"
                    x-cloak
                    class="flex flex-col sm:flex-row justify-between items-center gap-3 px-4 py-3 bg-gray-50 border-t border-gray-200"
                >
                    <p class="text-sm text-gray-600">
                        <span x-text="total ? ('Mostrando ' + (from || 0) + '–' + (to || 0) + ' de ' + total) : ''"></span>
                    </p>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="goToPage(currentPage - 1)"
                            :disabled="currentPage <= 1"
                            :class="currentPage <= 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-200'"
                            class="px-3 py-1.5 text-sm border border-gray-300 rounded-md bg-white"
                        >
                            Anterior
                        </button>
                        <span class="text-sm text-gray-700 tabular-nums">
                            Página <span x-text="currentPage"></span> de <span x-text="lastPage"></span>
                        </span>
                        <button
                            type="button"
                            @click="goToPage(currentPage + 1)"
                            :disabled="currentPage >= lastPage"
                            :class="currentPage >= lastPage ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-200'"
                            class="px-3 py-1.5 text-sm border border-gray-300 rounded-md bg-white"
                        >
                            Siguiente
                        </button>
                    </div>
                </div>
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
                    <input type="number" x-model.number="formData.cantidad" min="1" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Motivo *</label>
                    <input type="text" x-model="formData.motivo" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
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
        currentPage: 1,
        lastPage: 1,
        total: 0,
        from: 0,
        to: 0,
        formData: { producto_id: '', tipo: 'entrada', cantidad: 0, motivo: '', observaciones: '' },

        async init() {
            await Promise.all([this.fetch(), this.fetchProductos()]);
        },

        esVenta(mov) {
            const motivo = String(mov?.motivo || '');
            return motivo.startsWith('Venta ');
        },

        tipoLabel(mov) {
            if (this.esVenta(mov)) return 'Venta';
            if (mov.tipo === 'entrada') return 'Entrada';
            if (mov.tipo === 'ajuste') return 'Ajuste';
            return 'Salida';
        },

        tipoBadgeClass(mov) {
            if (this.esVenta(mov)) return 'bg-blue-100 text-blue-800';
            if (mov.tipo === 'entrada') return 'bg-green-100 text-green-800';
            if (mov.tipo === 'ajuste') return 'bg-yellow-100 text-yellow-800';
            return 'bg-red-100 text-red-800';
        },

        goToPage(page) {
            const p = parseInt(page, 10);
            if (isNaN(p) || p < 1 || p > this.lastPage) return;
            this.currentPage = p;
            this.fetch();
        },

        async fetch() {
            try {
                this.loading = true;
                const token = localStorage.getItem('token');
                const response = await axios.get('/api/movimientos-stock', {
                    params: { page: this.currentPage },
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                const body = response.data;
                if (body && Array.isArray(body.data) && body.last_page !== undefined) {
                    this.movimientos = body.data;
                    this.currentPage = body.current_page || 1;
                    this.lastPage = body.last_page || 1;
                    this.total = body.total || 0;
                    this.from = body.from ?? 0;
                    this.to = body.to ?? 0;
                } else {
                    this.movimientos = Array.isArray(body?.data) ? body.data : (Array.isArray(body) ? body : []);
                    this.lastPage = 1;
                    this.total = this.movimientos.length;
                    this.from = this.movimientos.length ? 1 : 0;
                    this.to = this.movimientos.length;
                }
            } catch (error) {
                console.error('Error:', error);
                this.movimientos = [];
                this.lastPage = 1;
                this.total = 0;
            } finally {
                this.loading = false;
            }
        },

        async fetchProductos() {
            try {
                const token = localStorage.getItem('token');
                const response = await axios.get('/api/productos', {
                    params: { all: 'true' },
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
                this.currentPage = 1;
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
