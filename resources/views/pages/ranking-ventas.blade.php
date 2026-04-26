@extends('layouts.app')

@section('title', 'Ranking de ventas - Danielles')
@section('page-title', 'Ranking de ventas')

@section('content')
<div x-data="rankingVentasAdmin()" x-init="init()" class="space-y-6">
    <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Ranking de ventas</h1>
            <p class="text-sm text-gray-600 mt-1">Productos más vendidos, vendedores, clientes y cajas (solo ventas cerradas o completadas).</p>
        </div>
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Desde</label>
                <input type="date" x-model="fechaInicio" class="px-3 py-2 border border-gray-300 rounded-md text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Hasta</label>
                <input type="date" x-model="fechaFin" class="px-3 py-2 border border-gray-300 rounded-md text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Top</label>
                <select x-model.number="limite" class="px-3 py-2 border border-gray-300 rounded-md text-sm">
                    <option value="15">15</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            </div>
            <button
                type="button"
                @click="fetch()"
                :disabled="loading"
                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 text-sm"
            >
                <span x-show="!loading">Actualizar</span>
                <span x-show="loading" x-cloak>Cargando…</span>
            </button>
        </div>
    </div>

    <div x-show="error" x-cloak class="p-4 bg-red-100 border border-red-400 text-red-700 rounded text-sm" x-text="error"></div>

    <template x-if="!loading && data">
        <div class="space-y-6">
            <p class="text-sm text-gray-500" x-show="data.periodo">
                Período: <span class="font-medium text-gray-800" x-text="data.periodo.fecha_inicio + ' — ' + data.periodo.fecha_fin"></span>
            </p>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-lg shadow p-4 border border-gray-100">
                    <p class="text-xs font-semibold text-gray-500 uppercase">Ventas</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1" x-text="data.resumen?.cantidad_ventas ?? 0"></p>
                </div>
                <div class="bg-white rounded-lg shadow p-4 border border-gray-100">
                    <p class="text-xs font-semibold text-gray-500 uppercase">Monto total</p>
                    <p class="text-2xl font-bold text-emerald-700 mt-1" x-text="'$' + fmt(data.resumen?.monto_total)"></p>
                </div>
                <div class="bg-white rounded-lg shadow p-4 border border-gray-100">
                    <p class="text-xs font-semibold text-gray-500 uppercase">Ticket promedio</p>
                    <p class="text-2xl font-bold text-blue-700 mt-1" x-text="'$' + fmt(data.resumen?.ticket_promedio)"></p>
                </div>
                <div class="bg-white rounded-lg shadow p-4 border border-gray-100">
                    <p class="text-xs font-semibold text-gray-500 uppercase">Unidades vendidas</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1" x-text="data.resumen?.unidades_vendidas ?? 0"></p>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                <div class="bg-white rounded-lg shadow overflow-hidden border border-gray-100">
                    <div class="px-4 py-3 border-b bg-gray-50">
                        <h2 class="text-lg font-semibold text-gray-800">Productos más vendidos</h2>
                        <p class="text-xs text-gray-500">Por cantidad de unidades</p>
                    </div>
                    <div class="overflow-x-auto max-h-[28rem] overflow-y-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50 sticky top-0">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">#</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Producto</th>
                                    <th class="px-3 py-2 text-right text-xs font-medium text-gray-500">Unid.</th>
                                    <th class="px-3 py-2 text-right text-xs font-medium text-gray-500">Total $</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <template x-for="(p, i) in (data.productos || [])" :key="p.id">
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-3 py-2 text-gray-500" x-text="i + 1"></td>
                                        <td class="px-3 py-2">
                                            <span class="font-medium text-gray-900" x-text="p.nombre"></span>
                                            <span class="block text-xs text-gray-500" x-text="p.codigo || '—'"></span>
                                        </td>
                                        <td class="px-3 py-2 text-right font-medium" x-text="p.cantidad_vendida"></td>
                                        <td class="px-3 py-2 text-right" x-text="'$' + fmt(p.total_vendido)"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                        <p x-show="!(data.productos || []).length" class="p-6 text-center text-gray-500 text-sm">Sin datos en el período.</p>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow overflow-hidden border border-gray-100">
                    <div class="px-4 py-3 border-b bg-gray-50">
                        <h2 class="text-lg font-semibold text-gray-800">Vendedores</h2>
                        <p class="text-xs text-gray-500">Por monto facturado</p>
                    </div>
                    <div class="overflow-x-auto max-h-[28rem] overflow-y-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50 sticky top-0">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">#</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Vendedor</th>
                                    <th class="px-3 py-2 text-right text-xs font-medium text-gray-500">Ventas</th>
                                    <th class="px-3 py-2 text-right text-xs font-medium text-gray-500">Total $</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <template x-for="(v, i) in (data.vendedores || [])" :key="v.usuario_id ?? 's' + i">
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-3 py-2 text-gray-500" x-text="i + 1"></td>
                                        <td class="px-3 py-2">
                                            <span class="font-medium text-gray-900" x-text="v.nombre"></span>
                                            <span class="block text-xs text-gray-500 truncate max-w-[12rem]" x-text="v.email || ''"></span>
                                        </td>
                                        <td class="px-3 py-2 text-right" x-text="v.cantidad_ventas"></td>
                                        <td class="px-3 py-2 text-right font-medium text-emerald-700" x-text="'$' + fmt(v.monto_total)"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                        <p x-show="!(data.vendedores || []).length" class="p-6 text-center text-gray-500 text-sm">Sin datos en el período.</p>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow overflow-hidden border border-gray-100">
                    <div class="px-4 py-3 border-b bg-gray-50">
                        <h2 class="text-lg font-semibold text-gray-800">Clientes</h2>
                        <p class="text-xs text-gray-500">Por monto total de compras</p>
                    </div>
                    <div class="overflow-x-auto max-h-[28rem] overflow-y-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50 sticky top-0">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">#</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Cliente</th>
                                    <th class="px-3 py-2 text-right text-xs font-medium text-gray-500">Compras</th>
                                    <th class="px-3 py-2 text-right text-xs font-medium text-gray-500">Total $</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <template x-for="(c, i) in (data.clientes || [])" :key="c.id">
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-3 py-2 text-gray-500" x-text="i + 1"></td>
                                        <td class="px-3 py-2 font-medium text-gray-900" x-text="c.nombre"></td>
                                        <td class="px-3 py-2 text-right" x-text="c.cantidad_compras"></td>
                                        <td class="px-3 py-2 text-right" x-text="'$' + fmt(c.monto_total)"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                        <p x-show="!(data.clientes || []).length" class="p-6 text-center text-gray-500 text-sm">Sin clientes con compras en el período.</p>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow overflow-hidden border border-gray-100">
                    <div class="px-4 py-3 border-b bg-gray-50">
                        <h2 class="text-lg font-semibold text-gray-800">Cajas y formas de pago</h2>
                        <p class="text-xs text-gray-500">Montos por caja y distribución por tipo de pago</p>
                    </div>
                    <div class="p-4 space-y-4 max-h-[28rem] overflow-y-auto">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-700 mb-2">Por caja</h3>
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="text-left text-xs text-gray-500 border-b">
                                        <th class="py-1 pr-2">Caja</th>
                                        <th class="py-1 text-right">Ventas</th>
                                        <th class="py-1 text-right">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="c in (data.cajas || [])" :key="c.id">
                                        <tr class="border-b border-gray-50">
                                            <td class="py-2 pr-2" x-text="c.nombre"></td>
                                            <td class="py-2 text-right text-gray-600" x-text="c.cantidad_ventas"></td>
                                            <td class="py-2 text-right font-medium" x-text="'$' + fmt(c.monto_total)"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                            <p x-show="!(data.cajas || []).length" class="text-sm text-gray-500 py-2">Sin datos.</p>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-gray-700 mb-2">Por tipo de pago</h3>
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="text-left text-xs text-gray-500 border-b">
                                        <th class="py-1 pr-2">Tipo</th>
                                        <th class="py-1 text-right">Ops.</th>
                                        <th class="py-1 text-right">Monto</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="t in (data.por_tipo_pago || [])" :key="t.tipo_pago">
                                        <tr class="border-b border-gray-50">
                                            <td class="py-2 pr-2" x-text="t.label"></td>
                                            <td class="py-2 text-right text-gray-600" x-text="t.cantidad"></td>
                                            <td class="py-2 text-right font-medium" x-text="'$' + fmt(t.monto)"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                            <p x-show="!(data.por_tipo_pago || []).length" class="text-sm text-gray-500 py-2">Sin datos.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <template x-if="loading">
        <div class="flex items-center justify-center py-24 text-gray-500">
            <div class="inline-block animate-spin rounded-full h-10 w-10 border-b-2 border-blue-600 mr-3"></div>
            Cargando ranking…
        </div>
    </template>
</div>

@push('scripts')
<script>
function rankingVentasAdmin() {
    const hoy = new Date();
    const pad = (n) => String(n).padStart(2, '0');
    const iso = (d) => d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
    const inicioMes = new Date(hoy.getFullYear(), hoy.getMonth(), 1);

    return {
        loading: true,
        error: '',
        data: null,
        fechaInicio: iso(inicioMes),
        fechaFin: iso(hoy),
        limite: 25,

        fmt(n) {
            const x = parseFloat(n);
            return (isNaN(x) ? 0 : x).toFixed(2);
        },

        async init() {
            await this.fetch();
        },

        async fetch() {
            this.loading = true;
            this.error = '';
            try {
                const response = await axios.get('/api/admin/ranking-ventas', {
                    params: {
                        fecha_inicio: this.fechaInicio,
                        fecha_fin: this.fechaFin,
                        limite: this.limite,
                    },
                });
                this.data = response.data;
            } catch (e) {
                this.data = null;
                this.error = e.response?.data?.message || 'No se pudo cargar el ranking.';
            } finally {
                this.loading = false;
            }
        },
    };
}
</script>
@endpush
@endsection
