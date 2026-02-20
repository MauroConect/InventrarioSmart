@extends('layouts.app')

@section('title', 'Dashboard - Inventario Inteligente')
@section('page-title', 'Dashboard')

@section('content')
<div x-data="dashboard()" x-init="init()" class="space-y-6">
    <!-- Estadísticas principales -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
        <div class="bg-white p-4 sm:p-6 rounded-lg shadow">
            <h3 class="text-base sm:text-lg font-semibold text-gray-600">Caja Abierta</h3>
            <template x-if="loading">
                <div class="animate-pulse h-8 bg-gray-200 rounded mt-2"></div>
            </template>
            <template x-if="!loading">
                <div>
                    <p class="text-xl sm:text-2xl font-bold text-blue-600" x-text="'$' + (stats.caja_abierta || 0).toFixed(2)"></p>
                    <p x-show="!stats.tiene_caja_abierta" class="text-xs text-gray-500 mt-1">No hay caja abierta</p>
                </div>
            </template>
        </div>
        
        <div class="bg-white p-4 sm:p-6 rounded-lg shadow">
            <h3 class="text-base sm:text-lg font-semibold text-gray-600">Productos</h3>
            <template x-if="loading">
                <div class="animate-pulse h-8 bg-gray-200 rounded mt-2"></div>
            </template>
            <template x-if="!loading">
                <div>
                    <p class="text-xl sm:text-2xl font-bold text-green-600" x-text="stats.total_productos || 0"></p>
                    <p x-show="(stats.productos_stock_bajo || 0) > 0" class="text-xs text-orange-600 mt-1" 
                       x-text="(stats.productos_stock_bajo || 0) + ' con stock bajo'"></p>
                </div>
            </template>
        </div>
        
        <div class="bg-white p-4 sm:p-6 rounded-lg shadow">
            <h3 class="text-base sm:text-lg font-semibold text-gray-600">Clientes</h3>
            <template x-if="loading">
                <div class="animate-pulse h-8 bg-gray-200 rounded mt-2"></div>
            </template>
            <template x-if="!loading">
                <p class="text-xl sm:text-2xl font-bold text-purple-600" x-text="stats.total_clientes || 0"></p>
            </template>
        </div>
        
        <div class="bg-white p-4 sm:p-6 rounded-lg shadow">
            <h3 class="text-base sm:text-lg font-semibold text-gray-600">Ventas Hoy</h3>
            <template x-if="loading">
                <div class="animate-pulse h-8 bg-gray-200 rounded mt-2"></div>
            </template>
            <template x-if="!loading">
                <div>
                    <p class="text-xl sm:text-2xl font-bold text-orange-600" x-text="stats.ventas_hoy || 0"></p>
                    <p class="text-xs text-gray-500 mt-1" x-text="'$' + (stats.monto_ventas_hoy || 0).toFixed(2)"></p>
                </div>
            </template>
        </div>
    </div>

    <!-- Estadísticas adicionales -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
        <div class="bg-white p-4 sm:p-6 rounded-lg shadow">
            <h3 class="text-base sm:text-lg font-semibold text-gray-600">Ventas del Mes</h3>
            <template x-if="loading">
                <div class="animate-pulse h-8 bg-gray-200 rounded mt-2"></div>
            </template>
            <template x-if="!loading">
                <div>
                    <p class="text-xl sm:text-2xl font-bold text-indigo-600" x-text="stats.ventas_mes || 0"></p>
                    <p class="text-xs text-gray-500 mt-1" x-text="'$' + (stats.monto_ventas_mes || 0).toFixed(2)"></p>
                </div>
            </template>
        </div>
        
        <div class="bg-white p-4 sm:p-6 rounded-lg shadow">
            <h3 class="text-base sm:text-lg font-semibold text-gray-600">Deudas Pendientes</h3>
            <template x-if="loading">
                <div class="animate-pulse h-8 bg-gray-200 rounded mt-2"></div>
            </template>
            <template x-if="!loading">
                <p class="text-xl sm:text-2xl font-bold text-red-600" x-text="'$' + (stats.deudas_pendientes || 0).toFixed(2)"></p>
            </template>
        </div>
        
        <div class="bg-white p-4 sm:p-6 rounded-lg shadow">
            <h3 class="text-base sm:text-lg font-semibold text-gray-600">Stock Bajo</h3>
            <template x-if="loading">
                <div class="animate-pulse h-8 bg-gray-200 rounded mt-2"></div>
            </template>
            <template x-if="!loading">
                <p class="text-xl sm:text-2xl font-bold text-yellow-600" x-text="stats.productos_stock_bajo || 0"></p>
            </template>
        </div>
        
        <div class="bg-white p-4 sm:p-6 rounded-lg shadow">
            <h3 class="text-base sm:text-lg font-semibold text-gray-600">Cheques Próximos</h3>
            <template x-if="loading">
                <div class="animate-pulse h-8 bg-gray-200 rounded mt-2"></div>
            </template>
            <template x-if="!loading">
                <p class="text-xl sm:text-2xl font-bold text-amber-600" x-text="stats.cheques_proximos || 0"></p>
            </template>
        </div>
    </div>

    <!-- Cheques Próximos a Vencer -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-4 border-b flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
            <h2 class="text-lg sm:text-xl font-semibold text-gray-800">Cheques Próximos a Vencer (30 días)</h2>
            <a href="{{ route('cheques.index') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium whitespace-nowrap">
                Ver todos →
            </a>
        </div>
        <template x-if="loadingCheques">
            <div class="p-8 text-center">
                <div class="inline-block animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
                <p class="mt-2 text-sm text-gray-500">Cargando cheques...</p>
            </div>
        </template>
        <template x-if="!loadingCheques && cheques.length === 0">
            <div class="p-8 text-center text-gray-500">
                <p>No hay cheques próximos a vencer</p>
            </div>
        </template>
        <template x-if="!loadingCheques && cheques.length > 0">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nº Cheque</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Banco</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Monto</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha Vencimiento</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Días</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="cheque in cheques" :key="cheque.id">
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900" x-text="cheque.numero_cheque"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="cheque.banco"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900" x-text="'$' + parseFloat(cheque.monto || 0).toFixed(2)"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="new Date(cheque.fecha_vencimiento).toLocaleDateString()"></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs rounded-full font-bold" 
                                          :class="getDiasClass(getDiasHastaVencimiento(cheque.fecha_vencimiento))"
                                          x-text="getDiasText(getDiasHastaVencimiento(cheque.fecha_vencimiento))"></span>
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
function dashboard() {
    return {
        loading: true,
        loadingCheques: true,
        stats: {
            caja_abierta: 0,
            total_productos: 0,
            total_clientes: 0,
            ventas_hoy: 0,
            monto_ventas_hoy: 0,
            productos_stock_bajo: 0,
            ventas_mes: 0,
            monto_ventas_mes: 0,
            deudas_pendientes: 0,
            cheques_proximos: 0,
            tiene_caja_abierta: false,
        },
        cheques: [],
        
        async init() {
            await Promise.all([
                this.fetchEstadisticas(),
                this.fetchCheques()
            ]);
            
            // Actualizar cada 30 segundos
            setInterval(() => this.fetchEstadisticas(), 30000);
            setInterval(() => this.fetchCheques(), 30000);
        },
        
        async fetchEstadisticas() {
            try {
                this.loading = true;
                const token = localStorage.getItem('token');
                const response = await axios.get('/api/dashboard/estadisticas', {
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                if (response.data) {
                    this.stats = {
                        caja_abierta: response.data.caja_abierta || 0,
                        total_productos: response.data.total_productos || 0,
                        total_clientes: response.data.total_clientes || 0,
                        ventas_hoy: response.data.ventas_hoy || 0,
                        monto_ventas_hoy: response.data.monto_ventas_hoy || 0,
                        productos_stock_bajo: response.data.productos_stock_bajo || 0,
                        ventas_mes: response.data.ventas_mes || 0,
                        monto_ventas_mes: response.data.monto_ventas_mes || 0,
                        deudas_pendientes: response.data.deudas_pendientes || 0,
                        cheques_proximos: response.data.cheques_proximos || 0,
                        tiene_caja_abierta: response.data.tiene_caja_abierta || false,
                    };
                }
            } catch (error) {
                console.error('Error al cargar estadísticas:', error);
            } finally {
                this.loading = false;
            }
        },
        
        async fetchCheques() {
            try {
                this.loadingCheques = true;
                const token = localStorage.getItem('token');
                const response = await axios.get('/api/cheques-proximos-vencer', {
                    params: { dias: 30 },
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                this.cheques = Array.isArray(response.data) ? response.data.slice(0, 5) : [];
            } catch (error) {
                console.error('Error al cargar cheques:', error);
            } finally {
                this.loadingCheques = false;
            }
        },
        
        getDiasHastaVencimiento(fecha) {
            const hoy = new Date();
            const vencimiento = new Date(fecha);
            return Math.ceil((vencimiento - hoy) / (1000 * 60 * 60 * 24));
        },
        
        getDiasClass(dias) {
            if (dias < 0) return 'bg-red-100 text-red-800';
            if (dias <= 3) return 'bg-orange-100 text-orange-800';
            if (dias <= 7) return 'bg-yellow-100 text-yellow-800';
            return 'bg-green-100 text-green-800';
        },
        
        getDiasText(dias) {
            if (dias < 0) return `Vencido (${Math.abs(dias)} días)`;
            return `${dias} días`;
        }
    }
}
</script>
@endpush
@endsection
