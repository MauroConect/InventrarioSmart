@extends('layouts.app')

@section('title', 'Comandas')
@section('page-title', 'Comandas')

@section('content')
<div x-data="comandas()" x-init="init()" class="space-y-4">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold">Comandas abiertas</h1>
            <p class="text-sm text-gray-600">Heladería, bar y restaurant: abrí, cargá y cobrá al cerrar.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <input x-model="etiqueta" placeholder="Mesa 3, Barra, Takeaway…" class="rounded-md border px-3 py-2 text-sm">
            <button type="button" @click="abrirComanda()" :disabled="!cajaId || loading" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:bg-gray-400">Nueva comanda</button>
        </div>
    </div>

    <div x-show="cajasAbiertas.length === 0" x-cloak class="rounded border border-yellow-300 bg-yellow-50 p-3 text-yellow-900">Abrí una caja para operar comandas.</div>
    <div x-show="cajasAbiertas.length > 0" class="flex items-center gap-2 text-sm">
        <span class="text-gray-600">Caja</span>
        <select x-model="cajaId" class="rounded-md border px-3 py-2">
            <template x-for="caja in cajasAbiertas" :key="caja.id">
                <option :value="caja.id" x-text="(caja.nombre || 'Caja #' + caja.id)"></option>
            </template>
        </select>
    </div>
    <div x-show="error" x-cloak class="rounded border border-red-300 bg-red-50 p-3 text-red-800" x-text="error"></div>
    <div x-show="success" x-cloak class="rounded border border-green-300 bg-green-50 p-3 text-green-800" x-text="success"></div>

    <div class="grid gap-4 lg:grid-cols-3">
        <section class="rounded-lg bg-white shadow lg:col-span-1">
            <h2 class="border-b px-4 py-3 font-semibold">En curso (<span x-text="comandas.length"></span>)</h2>
            <div class="max-h-[70vh] divide-y overflow-y-auto">
                <template x-if="comandas.length === 0">
                    <p class="px-4 py-8 text-center text-sm text-gray-500">No hay comandas abiertas.</p>
                </template>
                <template x-for="comanda in comandas" :key="comanda.id">
                    <button type="button" @click="seleccionar(comanda.id)" class="block w-full px-4 py-3 text-left hover:bg-slate-50" :class="seleccionada?.id === comanda.id ? 'bg-blue-50' : ''">
                        <div class="flex justify-between gap-2">
                            <span class="font-semibold" x-text="comanda.etiqueta || comanda.numero_factura"></span>
                            <span class="text-sm" x-text="'$' + Number(comanda.total_final || 0).toFixed(2)"></span>
                        </div>
                        <p class="text-xs text-gray-500" x-text="(comanda.items || []).length + ' ítems · ' + new Date(comanda.created_at).toLocaleTimeString()"></p>
                    </button>
                </template>
            </div>
        </section>

        <section class="rounded-lg bg-white shadow lg:col-span-2">
            <template x-if="!seleccionada">
                <p class="px-6 py-16 text-center text-gray-500">Elegí una comanda o abrí una nueva.</p>
            </template>
            <template x-if="seleccionada">
                <div class="p-4 sm:p-6 space-y-4">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div>
                            <h2 class="text-xl font-bold" x-text="seleccionada.etiqueta || seleccionada.numero_factura"></h2>
                            <p class="text-sm text-gray-500" x-text="seleccionada.numero_factura"></p>
                        </div>
                        <div class="flex gap-2">
                            <button type="button" @click="anular()" class="rounded-md border border-red-300 px-3 py-2 text-sm text-red-700 hover:bg-red-50">Anular</button>
                            <button type="button" @click="showCierre = true" class="rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white hover:bg-green-700">Cobrar y cerrar</button>
                        </div>
                    </div>

                    <div class="flex flex-col gap-2 sm:flex-row">
                        <input x-model="busqueda" placeholder="Buscar producto o código" class="flex-1 rounded-md border px-3 py-2" @keydown.enter.prevent="agregarDesdeBusqueda()">
                        <input type="number" :min="weightedEnabled ? 0.001 : 1" :step="weightedEnabled ? 0.001 : 1" x-model.number="cantidad" class="w-28 rounded-md border px-3 py-2" title="Cantidad">
                        <button type="button" @click="agregarDesdeBusqueda()" class="rounded-md bg-slate-800 px-4 py-2 text-sm text-white">Agregar</button>
                    </div>
                    <div x-show="sugeridos.length" class="max-h-40 overflow-y-auto rounded-md border bg-slate-50">
                        <template x-for="prod in sugeridos" :key="prod.id">
                            <button type="button" @click="agregarProducto(prod)" class="block w-full px-3 py-2 text-left text-sm hover:bg-white" x-text="prod.nombre + (prod.codigo ? ' · ' + prod.codigo : '') + ' · $' + Number(prod.precio_venta).toFixed(2)"></button>
                        </template>
                    </div>

                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b text-left text-xs uppercase text-gray-500">
                                <th class="py-2">Producto</th>
                                <th class="py-2">Cant.</th>
                                <th class="py-2 text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="item in (seleccionada.items || [])" :key="item.id">
                                <tr class="border-b">
                                    <td class="py-2" x-text="item.producto ? item.producto.nombre : 'Producto'"></td>
                                    <td class="py-2" x-text="item.cantidad"></td>
                                    <td class="py-2 text-right" x-text="'$' + Number(item.subtotal).toFixed(2)"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                    <p class="text-right text-lg font-bold">Total <span x-text="'$' + Number(seleccionada.total_final || 0).toFixed(2)"></span></p>
                </div>
            </template>
        </section>
    </div>

    <div x-show="showCierre" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="showCierre = false">
        <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
            <h3 class="text-lg font-bold">Cerrar comanda</h3>
            <label class="mt-4 block text-sm font-medium">Medio de pago</label>
            <select x-model="tipoPago" class="mt-1 w-full rounded-md border px-3 py-2">
                <option value="efectivo">Efectivo</option>
                <option value="tarjeta">Tarjeta</option>
                <option value="transferencia">Transferencia</option>
                            <option value="mixto">Mixto</option>
                <option value="cuenta_corriente" x-show="creditEnabled">Cuenta corriente</option>
            </select>
            <div x-show="tipoPago === 'mixto'" class="mt-3 grid grid-cols-3 gap-2">
                <input type="number" step="0.01" x-model.number="montoEfectivo" placeholder="Efectivo" class="rounded-md border px-2 py-2 text-sm">
                <input type="number" step="0.01" x-model.number="montoTarjeta" placeholder="Tarjeta" class="rounded-md border px-2 py-2 text-sm">
                <input type="number" step="0.01" x-model.number="montoTransferencia" placeholder="Transf." class="rounded-md border px-2 py-2 text-sm">
            </div>
            <div class="mt-5 flex justify-end gap-2">
                <button type="button" @click="showCierre = false" class="rounded-md border px-4 py-2 text-sm">Cancelar</button>
                <button type="button" @click="cerrarComanda()" class="rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white">Confirmar cobro</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function comandas() {
    return {
        comandas: [],
        seleccionada: null,
        cajasAbiertas: [],
        cajaId: '',
        etiqueta: '',
        productos: [],
        busqueda: '',
        cantidad: 1,
        weightedEnabled: !!(window.tenantModules && window.tenantModules.weighted),
        creditEnabled: !!(window.tenantModules && window.tenantModules.credit),
        error: '',
        success: '',
        loading: false,
        showCierre: false,
        tipoPago: 'efectivo',
        montoEfectivo: '',
        montoTarjeta: '',
        montoTransferencia: '',
        get sugeridos() {
            const q = (this.busqueda || '').toLowerCase().trim();
            if (q.length < 1) return [];
            return this.productos.filter((p) =>
                (p.nombre || '').toLowerCase().includes(q) || (p.codigo || '').toLowerCase().includes(q)
            ).slice(0, 8);
        },
        authHeaders() {
            const t = localStorage.getItem('token');
            return t ? { Authorization: 'Bearer ' + t } : {};
        },
        async init() {
            await Promise.all([this.cargarCajas(), this.cargarProductos(), this.cargarComandas()]);
        },
        async cargarCajas() {
            const res = await axios.get('/api/cajas', { params: { estado: 'abierta' }, headers: this.authHeaders() });
            this.cajasAbiertas = (res.data?.data || res.data || []).filter((c) => c.estado === 'abierta');
            if (this.cajasAbiertas.length && !this.cajaId) this.cajaId = String(this.cajasAbiertas[0].id);
        },
        async cargarProductos() {
            const res = await axios.get('/api/productos', { params: { all: 'true' }, headers: this.authHeaders() });
            this.productos = Array.isArray(res.data) ? res.data : (res.data?.data || []);
        },
        async cargarComandas() {
            const res = await axios.get('/api/ventas/abiertas', { headers: this.authHeaders() });
            this.comandas = res.data?.data || [];
            if (this.seleccionada) {
                this.seleccionada = this.comandas.find((c) => c.id === this.seleccionada.id) || null;
            }
        },
        seleccionar(id) {
            this.seleccionada = this.comandas.find((c) => c.id === id) || null;
            this.error = '';
        },
        async abrirComanda() {
            this.error = '';
            this.loading = true;
            try {
                const res = await axios.post('/api/ventas/abrir', {
                    caja_id: Number(this.cajaId),
                    etiqueta: this.etiqueta || null,
                }, { headers: this.authHeaders() });
                this.etiqueta = '';
                this.success = 'Comanda abierta.';
                await this.cargarComandas();
                this.seleccionar(res.data.id);
            } catch (e) {
                this.error = e.response?.data?.message || 'No se pudo abrir la comanda.';
            } finally {
                this.loading = false;
            }
        },
        agregarDesdeBusqueda() {
            const q = (this.busqueda || '').trim().toLowerCase();
            const prod = this.productos.find((p) => (p.codigo || '').toLowerCase() === q)
                || this.sugeridos[0];
            if (prod) this.agregarProducto(prod);
        },
        async agregarProducto(prod) {
            if (!this.seleccionada) return;
            this.error = '';
            try {
                await axios.post('/api/ventas/' + this.seleccionada.id + '/items', {
                    items: [{ producto_id: prod.id, cantidad: this.cantidad || 1 }],
                }, { headers: this.authHeaders() });
                this.busqueda = '';
                this.cantidad = 1;
                await this.cargarComandas();
            } catch (e) {
                this.error = e.response?.data?.message || 'No se pudo agregar el producto.';
            }
        },
        async cerrarComanda() {
            if (!this.seleccionada) return;
            this.error = '';
            try {
                await axios.post('/api/ventas/' + this.seleccionada.id + '/cerrar', {
                    tipo_pago: this.tipoPago,
                    monto_efectivo: this.montoEfectivo || null,
                    monto_tarjeta: this.montoTarjeta || null,
                    monto_transferencia: this.montoTransferencia || null,
                }, { headers: this.authHeaders() });
                this.showCierre = false;
                this.success = 'Comanda cobrada.';
                this.seleccionada = null;
                await this.cargarComandas();
            } catch (e) {
                this.error = e.response?.data?.message || 'No se pudo cerrar.';
            }
        },
        async anular() {
            if (!this.seleccionada || !confirm('¿Anular esta comanda y devolver el stock?')) return;
            try {
                await axios.post('/api/ventas/' + this.seleccionada.id + '/cancelar', {}, { headers: this.authHeaders() });
                this.success = 'Comanda anulada.';
                this.seleccionada = null;
                await this.cargarComandas();
            } catch (e) {
                this.error = e.response?.data?.message || 'No se pudo anular.';
            }
        },
    };
}
</script>
@endpush
