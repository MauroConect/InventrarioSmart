@extends('layouts.app')

@section('title', 'Deudas de Clientes - Inventario Inteligente')
@section('page-title', 'Deudas de Clientes')

@section('content')
<div x-data="deudasClientes()" x-init="init()" class="space-y-6">
    <h1 class="text-3xl font-bold">Deudas de Clientes</h1>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <template x-if="loading">
            <div class="p-8 text-center text-gray-500">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <p class="mt-2">Cargando deudas...</p>
            </div>
        </template>
        
        <template x-if="!loading">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cliente</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Monto Total</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pendiente</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="deuda in deudas" :key="deuda.id">
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4" x-text="(deuda.cliente?.nombre || '') + ' ' + (deuda.cliente?.apellido || '')"></td>
                                <td class="px-6 py-4" x-text="'$' + parseFloat(deuda.monto_total || 0).toFixed(2)"></td>
                                <td class="px-6 py-4 font-bold" x-text="'$' + parseFloat(deuda.monto_pendiente || 0).toFixed(2)"></td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 rounded-full text-xs" 
                                          :class="{
                                              'bg-green-100 text-green-800': deuda.estado === 'pagada',
                                              'bg-red-100 text-red-800': deuda.estado === 'vencida',
                                              'bg-yellow-100 text-yellow-800': deuda.estado !== 'pagada' && deuda.estado !== 'vencida'
                                          }"
                                          x-text="deuda.estado"></span>
                                </td>
                                <td class="px-6 py-4">
                                    <button x-show="deuda.monto_pendiente > 0" @click="registrarPago(deuda)" class="text-green-600 hover:text-green-900">Registrar Pago</button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </template>
    </div>

    <!-- Modal Pago -->
    <div x-show="showModal" x-cloak class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50 p-4" @click.away="closeModal()">
        <div class="bg-white rounded-lg w-full max-w-md" @click.stop>
            <div class="px-6 py-4 border-b">
                <h3 class="text-lg font-bold">Registrar Pago</h3>
            </div>
            <form @submit.prevent="guardarPago()" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Monto a Pagar *</label>
                    <input type="number" step="0.01" x-model.number="montoPago" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                </div>
                <div class="flex justify-end gap-2 pt-4 border-t">
                    <button type="button" @click="closeModal()" class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50">Cancelar</button>
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">Registrar</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function deudasClientes() {
    return {
        deudas: [],
        loading: true,
        showModal: false,
        deudaSeleccionada: null,
        montoPago: 0,
        
        async init() {
            await this.fetch();
        },
        
        async fetch() {
            try {
                this.loading = true;
                const token = localStorage.getItem('token');
                const response = await axios.get('/api/deudas-clientes', {
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                this.deudas = response.data?.data || response.data || [];
            } catch (error) {
                console.error('Error:', error);
            } finally {
                this.loading = false;
            }
        },
        
        registrarPago(deuda) {
            this.deudaSeleccionada = deuda;
            this.montoPago = 0;
            this.showModal = true;
        },
        
        closeModal() {
            this.showModal = false;
            this.deudaSeleccionada = null;
            this.montoPago = 0;
        },
        
        async guardarPago() {
            if (!this.montoPago || this.montoPago <= 0) {
                alert('Debe ingresar un monto válido');
                return;
            }
            try {
                const token = localStorage.getItem('token');
                await axios.post(`/api/deudas-clientes/${this.deudaSeleccionada.id}/pago`, {
                    monto: this.montoPago
                }, {
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                await this.fetch();
                this.closeModal();
            } catch (error) {
                alert('Error al registrar pago');
            }
        }
    }
}
</script>
@endpush
@endsection
