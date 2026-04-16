@extends('layouts.app')

@section('title', 'Auditoría - El Cristo')
@section('page-title', 'Auditoría')

@section('content')
<div x-data="auditoriaAdmin()" x-init="init()" class="space-y-6">
    <div class="flex flex-col gap-2">
        <h1 class="text-2xl sm:text-3xl font-bold">Auditoría del sistema</h1>
        <p class="text-sm text-gray-600 max-w-3xl">
            Línea de tiempo unificada (últimos registros por módulo): aperturas y cierres de caja, movimientos de caja,
            ventas y cambios detectados en ventas, facturación AFIP, stock, cuenta corriente y cheques.
            Los cambios de pago (efectivo, transferencia, etc.) solo aparecen como «Venta actualizada» si hubo un guardado
            posterior al alta; no se guarda historial campo a campo en la base actual.
        </p>
    </div>

    <div class="bg-white rounded-lg shadow p-4 sm:p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Desde</label>
                <input type="date" x-model="filtros.desde" class="w-full px-3 py-2 border border-gray-300 rounded-md">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Hasta</label>
                <input type="date" x-model="filtros.hasta" class="w-full px-3 py-2 border border-gray-300 rounded-md">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Categoría</label>
                <select x-model="filtros.categoria" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    <option value="todas">Todas</option>
                    <option value="caja">Caja (apertura, cierre, movimientos)</option>
                    <option value="venta">Ventas y facturación</option>
                    <option value="stock">Stock</option>
                    <option value="cuenta_corriente">Cuenta corriente</option>
                    <option value="cheque">Cheques</option>
                </select>
            </div>
            <div class="flex gap-2">
                <button type="button" @click="aplicarFiltros()" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    Aplicar
                </button>
                <button type="button" @click="limpiarFiltros()" class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50">
                    Limpiar
                </button>
            </div>
        </div>
    </div>

    <div x-show="error" x-cloak class="p-4 bg-red-100 border border-red-400 text-red-700 rounded" x-text="error"></div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <template x-if="loading">
            <div class="p-8 text-center text-gray-500">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <p class="mt-2">Cargando eventos…</p>
            </div>
        </template>

        <template x-if="!loading">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase whitespace-nowrap">Fecha / hora</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase whitespace-nowrap">Categoría</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase whitespace-nowrap">Tipo</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Evento</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Detalle</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase whitespace-nowrap">Usuario</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase whitespace-nowrap">Enlace</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr x-show="!loading && eventos.length === 0">
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500">No hay eventos en el rango seleccionado.</td>
                        </tr>
                        <template x-for="(ev, idx) in eventos" :key="idx">
                            <tr class="hover:bg-gray-50 align-top">
                                <td class="px-4 py-3 text-gray-800 whitespace-nowrap" x-text="formatFecha(ev.occurred_at)"></td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-0.5 text-xs rounded-full font-medium bg-slate-100 text-slate-800" x-text="labelCategoria(ev.categoria)"></span>
                                </td>
                                <td class="px-4 py-3 text-gray-600 font-mono text-xs" x-text="ev.tipo"></td>
                                <td class="px-4 py-3 font-medium text-gray-900" x-text="ev.titulo"></td>
                                <td class="px-4 py-3 text-gray-700 max-w-md" x-text="ev.detalle"></td>
                                <td class="px-4 py-3 text-gray-600 whitespace-nowrap" x-text="ev.usuario || '—'"></td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <template x-if="ev.meta && ev.meta.venta_id">
                                        <a :href="'/ventas/' + ev.meta.venta_id" class="text-blue-600 hover:underline">Venta</a>
                                    </template>
                                    <template x-if="!ev.meta || !ev.meta.venta_id">
                                        <span class="text-gray-400">—</span>
                                    </template>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
                <div
                    x-show="pagination.last_page > 1"
                    class="flex flex-col sm:flex-row justify-between items-center gap-3 px-4 py-3 bg-gray-50 border-t border-gray-200"
                >
                    <p class="text-sm text-gray-600" x-text="paginationText()"></p>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            :disabled="page <= 1"
                            @click="setPage(page - 1)"
                            class="px-3 py-1.5 text-sm border border-gray-300 rounded-md bg-white disabled:opacity-50"
                        >Anterior</button>
                        <span class="text-sm text-gray-700 tabular-nums" x-text="'Página ' + page + ' de ' + pagination.last_page"></span>
                        <button
                            type="button"
                            :disabled="page >= pagination.last_page"
                            @click="setPage(page + 1)"
                            class="px-3 py-1.5 text-sm border border-gray-300 rounded-md bg-white disabled:opacity-50"
                        >Siguiente</button>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>

@push('scripts')
<script>
function auditoriaAdmin() {
    return {
        loading: true,
        error: '',
        eventos: [],
        page: 1,
        filtros: { desde: '', hasta: '', categoria: 'todas' },
        pagination: { current_page: 1, last_page: 1, total: 0, per_page: 40, from: 0, to: 0 },

        authHeaders() {
            const t = localStorage.getItem('token');
            return t ? { Authorization: 'Bearer ' + t } : {};
        },

        paginationText() {
            const p = this.pagination;
            if (!p.total) return '';
            return 'Mostrando ' + (p.from || 0) + '–' + (p.to || 0) + ' de ' + p.total;
        },

        labelCategoria(c) {
            const m = {
                caja: 'Caja',
                venta: 'Venta',
                stock: 'Stock',
                cuenta_corriente: 'C. corriente',
                cheque: 'Cheque',
            };
            return m[c] || c;
        },

        formatFecha(iso) {
            if (!iso) return '—';
            try {
                return new Date(iso).toLocaleString('es-AR');
            } catch (e) {
                return iso;
            }
        },

        async init() {
            await this.fetch();
        },

        async setPage(n) {
            this.page = n;
            await this.fetch();
        },

        async aplicarFiltros() {
            this.page = 1;
            await this.fetch();
        },

        limpiarFiltros() {
            this.filtros = { desde: '', hasta: '', categoria: 'todas' };
            this.page = 1;
            this.fetch();
        },

        async fetch() {
            try {
                this.loading = true;
                this.error = '';
                const params = {
                    page: this.page,
                    per_page: this.pagination.per_page || 40,
                    categoria: this.filtros.categoria || 'todas',
                };
                if (this.filtros.desde) params.desde = this.filtros.desde;
                if (this.filtros.hasta) params.hasta = this.filtros.hasta;

                const response = await axios.get('/api/auditoria/timeline', {
                    params,
                    headers: this.authHeaders(),
                });
                const body = response.data;
                this.eventos = Array.isArray(body.data) ? body.data : [];
                this.pagination = {
                    current_page: body.current_page || 1,
                    last_page: body.last_page || 1,
                    total: body.total || 0,
                    per_page: body.per_page || 40,
                    from: body.from ?? 0,
                    to: body.to ?? 0,
                };
            } catch (e) {
                this.error = e.response?.data?.message || 'Error al cargar la auditoría.';
                this.eventos = [];
            } finally {
                this.loading = false;
            }
        },
    };
}
</script>
@endpush
@endsection
