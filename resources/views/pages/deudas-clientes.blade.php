@extends('layouts.app')

@section('title', 'Deudas de Clientes - Danielles')
@section('page-title', 'Deudas de Clientes')

@section('content')
<div x-data="deudasClientes()" x-init="init()" class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
        <h1 class="text-3xl font-bold">Deudas de Clientes</h1>
        <button
            @click="openModalNuevaDeuda()"
            class="w-full sm:w-auto px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
        >
            + Cargar Deuda Manual
        </button>
    </div>

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
    <div x-show="showModal && modalTipo === 'pago'" x-cloak class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50 p-4" @click.away="closeModal()">
        <div class="bg-white rounded-lg w-full max-w-md" @click.stop>
            <div class="px-6 py-4 border-b flex justify-between items-center">
                <h3 class="text-lg font-bold">Registrar Pago</h3>
                <button @click="closeModal()" class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
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

    <!-- Modal Nueva Deuda Manual -->
    <div x-show="showModal && modalTipo === 'nueva'" x-cloak class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50 p-4 overflow-y-auto" @click.away="closeModal()">
        <div class="bg-white rounded-lg w-full max-w-2xl my-8" @click.stop>
            <div class="px-6 py-4 border-b flex justify-between items-center">
                <h3 class="text-lg font-bold">Cargar Deuda Manual</h3>
                <button @click="closeModal()" class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
            </div>
            <form @submit.prevent="guardarNuevaDeuda()" class="p-6 space-y-4">
                <div x-show="error" x-cloak class="p-3 bg-red-100 border border-red-400 text-red-700 rounded text-sm" x-text="error"></div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cliente *</label>
                    <select x-model="formDeuda.cliente_id" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                        <option value="">Seleccionar cliente...</option>
                        <template x-for="cliente in clientes" :key="cliente.id">
                            <option :value="cliente.id" x-text="cliente.nombre + ' ' + (cliente.apellido || '')"></option>
                        </template>
                    </select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Monto Total *</label>
                        <input type="number" step="0.01" x-model.number="formDeuda.monto_total" @input="calcularCuotasRestantes()" class="w-full px-3 py-2 border border-gray-300 rounded-md" required min="0.01">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cuotas Originales</label>
                        <input type="number" x-model.number="formDeuda.cuotas_originales" @input="calcularCuotasRestantes()" class="w-full px-3 py-2 border border-gray-300 rounded-md" min="1">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cuotas Pagadas</label>
                        <input type="number" x-model.number="formDeuda.cuotas_pagadas" @input="calcularCuotasRestantes()" class="w-full px-3 py-2 border border-gray-300 rounded-md" min="0">
                        <p class="text-xs text-gray-500 mt-1">Ingrese cuántas cuotas ya pagó el cliente</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cuotas Restantes</label>
                        <input type="number" x-model.number="formDeuda.cuotas_restantes" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50" readonly>
                        <p class="text-xs text-gray-500 mt-1">Se calcula automáticamente</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de Vencimiento (opcional)</label>
                        <input type="date" x-model="formDeuda.fecha_vencimiento" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Monto Pagado</label>
                        <input type="number" step="0.01" x-model.number="formDeuda.monto_pagado" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50" readonly>
                        <p class="text-xs text-gray-500 mt-1">Se calcula automáticamente si hay cuotas</p>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Observaciones (opcional)</label>
                    <textarea x-model="formDeuda.observaciones" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Notas adicionales sobre esta deuda..."></textarea>
                </div>

                <div class="bg-blue-50 border border-blue-200 rounded-md p-3">
                    <p class="text-sm text-blue-800">
                        <strong>Resumen:</strong> 
                        <span x-show="formDeuda.monto_total">Total: $<span x-text="parseFloat(formDeuda.monto_total || 0).toFixed(2)"></span></span>
                        <span x-show="formDeuda.monto_pagado > 0"> | Pagado: $<span x-text="parseFloat(formDeuda.monto_pagado || 0).toFixed(2)"></span></span>
                        <span x-show="formDeuda.monto_total"> | Pendiente: $<span x-text="(parseFloat(formDeuda.monto_total || 0) - parseFloat(formDeuda.monto_pagado || 0)).toFixed(2)"></span></span>
                    </p>
                </div>

                <div class="flex justify-end gap-2 pt-4 border-t">
                    <button type="button" @click="closeModal()" class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50">Cancelar</button>
                    <button type="submit" :disabled="loadingSubmit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50">
                        <span x-show="!loadingSubmit">Guardar Deuda</span>
                        <span x-show="loadingSubmit" x-cloak>Guardando...</span>
                    </button>
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
        clientes: [],
        loading: true,
        loadingSubmit: false,
        showModal: false,
        modalTipo: 'pago', // 'pago' o 'nueva'
        deudaSeleccionada: null,
        montoPago: 0,
        error: '',
        formDeuda: {
            cliente_id: '',
            monto_total: '',
            cuotas_originales: '',
            cuotas_pagadas: 0,
            cuotas_restantes: '',
            monto_pagado: 0,
            fecha_vencimiento: '',
            observaciones: ''
        },
        
        async init() {
            await Promise.all([this.fetch(), this.fetchClientes()]);
        },
        
        async fetchClientes() {
            try {
                const token = localStorage.getItem('token');
                const response = await axios.get('/api/clientes', {
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                const clientesData = response.data?.data || response.data || [];
                this.clientes = Array.isArray(clientesData) ? clientesData.filter(c => c.activo !== false) : [];
            } catch (error) {
                console.error('Error al cargar clientes:', error);
            }
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
        
        openModalNuevaDeuda() {
            this.modalTipo = 'nueva';
            this.showModal = true;
            this.error = '';
            this.resetFormDeuda();
        },
        
        resetFormDeuda() {
            this.formDeuda = {
                cliente_id: '',
                monto_total: '',
                cuotas_originales: '',
                cuotas_pagadas: 0,
                cuotas_restantes: '',
                monto_pagado: 0,
                fecha_vencimiento: '',
                observaciones: ''
            };
        },
        
        calcularCuotasRestantes() {
            const originales = parseInt(this.formDeuda.cuotas_originales) || 0;
            const pagadas = parseInt(this.formDeuda.cuotas_pagadas) || 0;
            this.formDeuda.cuotas_restantes = Math.max(0, originales - pagadas);
            
            // Calcular monto pagado si hay cuotas
            if (originales > 0 && this.formDeuda.monto_total) {
                const montoTotal = parseFloat(this.formDeuda.monto_total) || 0;
                const montoPorCuota = montoTotal / originales;
                this.formDeuda.monto_pagado = montoPorCuota * pagadas;
            } else {
                this.formDeuda.monto_pagado = 0;
            }
        },
        
        registrarPago(deuda) {
            this.modalTipo = 'pago';
            this.deudaSeleccionada = deuda;
            this.montoPago = 0;
            this.showModal = true;
        },
        
        closeModal() {
            this.showModal = false;
            this.deudaSeleccionada = null;
            this.montoPago = 0;
            this.error = '';
            this.resetFormDeuda();
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
        },
        
        async guardarNuevaDeuda() {
            this.error = '';
            this.loadingSubmit = true;
            
            // Validaciones
            if (!this.formDeuda.cliente_id) {
                this.error = 'Debe seleccionar un cliente';
                this.loadingSubmit = false;
                return;
            }
            
            if (!this.formDeuda.monto_total || parseFloat(this.formDeuda.monto_total) <= 0) {
                this.error = 'Debe ingresar un monto total válido';
                this.loadingSubmit = false;
                return;
            }
            
            // Validar cuotas
            const cuotasOriginales = parseInt(this.formDeuda.cuotas_originales) || 0;
            const cuotasPagadas = parseInt(this.formDeuda.cuotas_pagadas) || 0;
            
            if (cuotasOriginales > 0 && cuotasPagadas > cuotasOriginales) {
                this.error = 'Las cuotas pagadas no pueden ser mayores a las cuotas originales';
                this.loadingSubmit = false;
                return;
            }
            
            try {
                const token = localStorage.getItem('token');
                const payload = {
                    cliente_id: this.formDeuda.cliente_id,
                    monto_total: parseFloat(this.formDeuda.monto_total),
                    cuotas_originales: cuotasOriginales > 0 ? cuotasOriginales : null,
                    cuotas_pagadas: cuotasPagadas > 0 ? cuotasPagadas : null,
                    cuotas_restantes: parseInt(this.formDeuda.cuotas_restantes) || null,
                    fecha_vencimiento: this.formDeuda.fecha_vencimiento || null,
                    observaciones: this.formDeuda.observaciones || null
                };
                
                await axios.post('/api/deudas-clientes', payload, {
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                
                await this.fetch();
                this.closeModal();
                alert('Deuda cargada correctamente');
            } catch (error) {
                this.error = error.response?.data?.message || 'Error al cargar la deuda. Verifique los datos.';
                console.error('Error:', error);
            } finally {
                this.loadingSubmit = false;
            }
        }
    }
}
</script>
@endpush
@endsection
