@extends('layouts.app')

@section('title', 'Cuentas Corrientes - Inventario Inteligente')
@section('page-title', 'Cuentas Corrientes')

@section('content')
<div x-data="cuentasCorrientes()" x-init="init()" class="space-y-6">
    <h1 class="text-3xl font-bold">Cuentas Corrientes</h1>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <template x-if="loading">
            <div class="p-8 text-center text-gray-500">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <p class="mt-2">Cargando cuentas...</p>
            </div>
        </template>
        
        <template x-if="!loading">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cliente</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Saldo</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Límite Crédito</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="cuenta in cuentas" :key="cuenta.id">
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4" x-text="(cuenta.cliente?.nombre || '') + ' ' + (cuenta.cliente?.apellido || '')"></td>
                                <td class="px-6 py-4 font-bold" 
                                    :class="cuenta.saldo < 0 ? 'text-red-600' : 'text-green-600'"
                                    x-text="'$' + parseFloat(cuenta.saldo || 0).toFixed(2)"></td>
                                <td class="px-6 py-4" x-text="'$' + parseFloat(cuenta.limite_credito || 0).toFixed(2)"></td>
                                <td class="px-6 py-4">
                                    <button @click="verMovimientos(cuenta.id)" class="text-blue-600 hover:text-blue-900">Ver Movimientos</button>
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
function cuentasCorrientes() {
    return {
        cuentas: [],
        loading: true,
        
        async init() {
            await this.fetch();
        },
        
        async fetch() {
            try {
                this.loading = true;
                const token = localStorage.getItem('token');
                const response = await axios.get('/api/cuentas-corrientes', {
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                this.cuentas = response.data?.data || response.data || [];
            } catch (error) {
                console.error('Error:', error);
            } finally {
                this.loading = false;
            }
        },
        
        verMovimientos(id) {
            window.location.href = `/cuentas-corrientes/${id}`;
        }
    }
}
</script>
@endpush
@endsection
