@extends('layouts.app')

@section('title', 'Facturacion - El Cristo')
@section('page-title', 'Facturacion')

@section('content')
<div x-data="facturacion()" x-init="init()" class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
        <h1 class="text-2xl sm:text-3xl font-bold">Ventas pendientes de facturar</h1>
        <div class="flex flex-wrap items-center gap-3">
            <div>
                <label class="block text-xs text-gray-500 mb-1">Filtrar por estado</label>
                <select x-model="filtroEstado" @change="fetchPendientes()" class="px-3 py-2 border border-gray-300 rounded-md text-sm bg-white">
                    <option value="todos">Pendientes y con error</option>
                    <option value="pendiente">Solo pendientes</option>
                    <option value="error">Solo con error</option>
                </select>
            </div>
        <button
            @click="facturarSeleccionadas()"
            :disabled="facturando || seleccionadas.length === 0"
            class="px-4 py-2 rounded text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50"
        >
            <span x-show="!facturando">Facturar seleccionadas</span>
            <span x-show="facturando" x-cloak>Facturando...</span>
        </button>
        </div>
    </div>

    <div x-show="error" x-cloak class="p-4 bg-red-100 border border-red-400 text-red-700 rounded" x-text="error"></div>
    <div x-show="success" x-cloak class="p-4 bg-green-100 border border-green-400 text-green-700 rounded" x-text="success"></div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <template x-if="loading">
            <div class="p-8 text-center text-gray-500">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <p class="mt-2">Cargando ventas...</p>
            </div>
        </template>

        <template x-if="!loading">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                <input type="checkbox" @change="toggleAll($event.target.checked)">
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Venta</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cliente</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="venta in ventas" :key="venta.id">
                            <tr>
                                <td class="px-6 py-4">
                                    <input type="checkbox" :value="venta.id" x-model="seleccionadas">
                                </td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900" x-text="'#' + venta.id"></td>
                                <td class="px-6 py-4 text-sm text-gray-500" x-text="new Date(venta.created_at).toLocaleString()"></td>
                                <td class="px-6 py-4 text-sm text-gray-700" x-text="venta.cliente ? (venta.cliente.nombre + ' ' + venta.cliente.apellido) : 'Consumidor Final'"></td>
                                <td class="px-6 py-4 text-sm font-medium" x-text="'$' + parseFloat(venta.total_final || venta.total || 0).toFixed(2)"></td>
                                <td class="px-6 py-4 text-sm">
                                    <span
                                        class="inline-block px-2 py-1 rounded text-xs font-medium"
                                        :class="{
                                            'bg-green-100 text-green-800': (venta.estado_facturacion || 'pendiente') === 'facturada',
                                            'bg-yellow-100 text-yellow-800': (venta.estado_facturacion || 'pendiente') === 'pendiente',
                                            'bg-red-100 text-red-800': venta.estado_facturacion === 'error'
                                        }"
                                        x-text="venta.estado_facturacion || 'pendiente'"
                                    ></span>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <button @click="facturarUna(venta.id)" class="text-indigo-600 hover:text-indigo-900">Facturar</button>
                                    <a :href="'/ventas/' + venta.id" class="ml-3 text-blue-600 hover:text-blue-900">Ver</a>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </template>
    </div>
</div>

@push('scripts')
<script>
function facturacion() {
    return {
        loading: true,
        facturando: false,
        ventas: [],
        seleccionadas: [],
        error: '',
        success: '',
        filtroEstado: 'todos',

        async init() {
            await this.fetchPendientes();
        },

        async fetchPendientes() {
            try {
                this.loading = true;
                this.error = '';
                const token = localStorage.getItem('token');
                const params = {};
                if (this.filtroEstado && this.filtroEstado !== 'todos') {
                    params.estado_facturacion = this.filtroEstado;
                }
                const response = await axios.get('/api/ventas-pendientes-facturacion', {
                    params,
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                this.ventas = response.data?.data || [];
            } catch (error) {
                this.error = error.response?.data?.message || 'No se pudieron cargar las ventas pendientes.';
            } finally {
                this.loading = false;
            }
        },

        toggleAll(checked) {
            this.seleccionadas = checked ? this.ventas.map(v => v.id) : [];
        },

        async facturarUna(ventaId) {
            try {
                this.facturando = true;
                this.error = '';
                this.success = '';
                const token = localStorage.getItem('token');
                const response = await axios.post(`/api/ventas/${ventaId}/facturar-afip`, {}, {
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                this.success = response.data?.message || 'Venta facturada.';
                await this.fetchPendientes();
            } catch (error) {
                this.error = error.response?.data?.error || error.response?.data?.message || 'No se pudo facturar la venta.';
            } finally {
                this.facturando = false;
            }
        },

        async facturarSeleccionadas() {
            if (this.seleccionadas.length === 0) return;

            try {
                this.facturando = true;
                this.error = '';
                this.success = '';
                const token = localStorage.getItem('token');
                const response = await axios.post('/api/ventas-facturar-lote', {
                    venta_ids: this.seleccionadas
                }, {
                    headers: { 'Authorization': `Bearer ${token}` }
                });

                const ok = (response.data?.resultados || []).filter(r => r.ok).length;
                const fail = (response.data?.resultados || []).filter(r => !r.ok).length;
                this.success = `Facturacion por lote finalizada. OK: ${ok}, Error: ${fail}.`;
                this.seleccionadas = [];
                await this.fetchPendientes();
            } catch (error) {
                this.error = error.response?.data?.message || 'No se pudo facturar el lote.';
            } finally {
                this.facturando = false;
            }
        }
    }
}
</script>
@endpush
@endsection
