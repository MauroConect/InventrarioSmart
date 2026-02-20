@extends('layouts.app')

@section('title', 'Detalle de Venta - Inventario Inteligente')
@section('page-title', 'Detalle de Venta')

@section('content')
<div x-data="ventaDetalle()" x-init="init()" class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold">Venta #<span x-text="ventaId"></span></h1>
        <a href="{{ route('ventas.index') }}" class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">Volver</a>
    </div>

    <template x-if="loading">
        <div class="p-8 text-center text-gray-500">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <p class="mt-2">Cargando venta...</p>
        </div>
    </template>

    <template x-if="!loading && venta">
        <div class="space-y-6">
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold mb-4">Información General</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600">Fecha</p>
                        <p class="font-medium" x-text="new Date(venta.created_at).toLocaleString()"></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Cliente</p>
                        <p class="font-medium" x-text="venta.cliente ? (venta.cliente.nombre + ' ' + venta.cliente.apellido) : 'Cliente General'"></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Tipo de Pago</p>
                        <p class="font-medium" x-text="venta.tipo_pago"></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Total</p>
                        <p class="font-medium text-xl" x-text="'$' + parseFloat(venta.total || 0).toFixed(2)"></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold mb-4">Productos</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Producto</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cantidad</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Precio Unitario</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <template x-for="item in venta.items || []" :key="item.id">
                                <tr>
                                    <td class="px-6 py-4 text-sm" x-text="item.producto?.nombre || '-'"></td>
                                    <td class="px-6 py-4 text-sm" x-text="item.cantidad"></td>
                                    <td class="px-6 py-4 text-sm" x-text="'$' + parseFloat(item.precio_unitario || 0).toFixed(2)"></td>
                                    <td class="px-6 py-4 text-sm font-medium" x-text="'$' + parseFloat(item.subtotal || 0).toFixed(2)"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </template>
</div>

@push('scripts')
<script>
function ventaDetalle() {
    return {
        venta: null,
        ventaId: @json($id ?? null),
        loading: true,
        
        async init() {
            if (this.ventaId) {
                await this.fetch();
            }
        },
        
        async fetch() {
            try {
                this.loading = true;
                const token = localStorage.getItem('token');
                const response = await axios.get(`/api/ventas/${this.ventaId}`, {
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                this.venta = response.data;
            } catch (error) {
                console.error('Error:', error);
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>
@endpush
@endsection
