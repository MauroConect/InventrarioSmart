@extends('layouts.app')

@section('title', 'Detalle de Venta - Danielles')
@section('page-title', 'Detalle de Venta')

@section('content')
@php
    $puedeAgregarItemsVenta = auth()->check() && auth()->user()->hasPermission('ventas.create');
    $puedeEliminarVenta = auth()->check() && auth()->user()->hasPermission('ventas.delete');
@endphp
<div x-data="ventaDetalle({{ $puedeAgregarItemsVenta ? 'true' : 'false' }}, {{ $puedeEliminarVenta ? 'true' : 'false' }})" x-init="init()" class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
        <div>
            <h1 class="text-3xl font-bold">Venta #<span x-text="ventaId"></span></h1>
            <div class="mt-2" x-show="venta" x-cloak>
                <span class="text-sm text-gray-600 mr-2">Estado:</span>
                <span
                    class="px-2 py-1 text-xs rounded-full font-medium"
                    :class="(venta.estado || '').toLowerCase() === 'abierta' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'"
                    x-text="venta.estado || 'cerrada'"
                ></span>
            </div>
        </div>
        <div class="flex flex-wrap gap-2 items-center">
            <button
                @click="facturarAfip()"
                x-show="venta && venta.estado_facturacion !== 'facturada'"
                :disabled="facturando"
                class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 disabled:opacity-50"
            >
                <span x-show="!facturando">Facturar AFIP/ARCA</span>
                <span x-show="facturando" x-cloak>Facturando...</span>
            </button>
            <button
                @click="imprimirComprobante()"
                x-show="venta"
                class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 flex items-center gap-2"
            >
                🖨️ Imprimir Comprobante
            </button>
            <button
                type="button"
                x-show="puedeEliminarVenta && venta"
                x-cloak
                @click="eliminarVenta()"
                :disabled="eliminando || (venta.estado_facturacion || 'pendiente') === 'facturada'"
                class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 disabled:opacity-50"
            >
                <span x-show="!eliminando">Eliminar venta</span>
                <span x-show="eliminando" x-cloak>Eliminando…</span>
            </button>
            <a href="{{ route('ventas.index') }}" class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">Volver</a>
        </div>
    </div>
    <div
        class="flex justify-center"
        x-show="venta && (venta.estado || '').toLowerCase() === 'abierta'"
        x-cloak
    >
        <button
            type="button"
            @click="imprimirComprobante()"
            class="px-6 py-2 bg-orange-600 text-white rounded-md hover:bg-orange-700 shadow"
        >
            Cerrar venta
        </button>
    </div>

    <div x-show="error" x-cloak class="p-4 bg-red-100 border border-red-400 text-red-700 rounded" x-text="error"></div>
    <div x-show="success" x-cloak class="p-4 bg-green-100 border border-green-400 text-green-700 rounded" x-text="success"></div>

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
                        <template x-if="puedeAgregarItems && puedeAgregarLineas()">
                            <div class="mt-1 space-y-2">
                                <div class="flex flex-wrap gap-2 items-center">
                                    <select
                                        x-model="editPago.tipo_pago"
                                        @change="onCambioTipoPagoSelect()"
                                        :disabled="guardandoTipoPago"
                                        class="px-3 py-2 border border-gray-300 rounded-md text-sm disabled:bg-gray-100"
                                    >
                                        <option value="efectivo">Efectivo</option>
                                        <option value="tarjeta">Tarjeta</option>
                                        <option value="transferencia">Transferencia</option>
                                        <option value="cuenta_corriente">Cuenta Corriente</option>
                                        <option value="mixto">Mixto</option>
                                    </select>
                                    <button
                                        type="button"
                                        x-show="editPago.tipo_pago === 'mixto' || editPago.tipo_pago === 'tarjeta'"
                                        x-cloak
                                        @click="guardarTipoPago()"
                                        :disabled="guardandoTipoPago"
                                        class="px-3 py-2 bg-blue-600 text-white rounded-md text-sm hover:bg-blue-700 disabled:opacity-50"
                                    >
                                        <span x-show="!guardandoTipoPago">Guardar pago</span>
                                        <span x-show="guardandoTipoPago" x-cloak>Guardando…</span>
                                    </button>
                                    <span x-show="guardandoTipoPago && editPago.tipo_pago !== 'mixto' && editPago.tipo_pago !== 'tarjeta'" x-cloak class="text-xs text-gray-500">Guardando…</span>
                                </div>
                                <div x-show="editPago.tipo_pago === 'mixto'" x-cloak class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                                    <div>
                                        <label class="block text-xs text-gray-600 mb-1">Monto efectivo</label>
                                        <input type="number" step="0.01" min="0" x-model.number="editPago.monto_efectivo" class="w-full px-2 py-1.5 border border-gray-300 rounded-md text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-600 mb-1">Monto tarjeta</label>
                                        <input type="number" step="0.01" min="0" x-model.number="editPago.monto_tarjeta" class="w-full px-2 py-1.5 border border-gray-300 rounded-md text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-600 mb-1">Monto transferencia</label>
                                        <input type="number" step="0.01" min="0" x-model.number="editPago.monto_transferencia" class="w-full px-2 py-1.5 border border-gray-300 rounded-md text-sm">
                                    </div>
                                </div>
                                <div x-show="editPago.tipo_pago === 'tarjeta'" x-cloak class="w-32">
                                    <label class="block text-xs text-gray-600 mb-1">Cuotas</label>
                                    <input type="number" min="1" max="24" x-model.number="editPago.cuotas" class="w-full px-2 py-1.5 border border-gray-300 rounded-md text-sm">
                                </div>
                                <p x-show="editPago.tipo_pago === 'cuenta_corriente' && !venta.cliente_id" x-cloak class="text-xs text-amber-800">
                                    Para cuenta corriente la venta debe tener un cliente asignado.
                                </p>
                            </div>
                        </template>
                        <template x-if="!(puedeAgregarItems && puedeAgregarLineas())">
                            <p class="font-medium" x-text="etiquetaTipoPago(venta.tipo_pago)"></p>
                        </template>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Subtotal ítems</p>
                        <p class="font-medium" x-text="'$' + parseFloat(venta.total || 0).toFixed(2)"></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Total a pagar</p>
                        <p class="font-medium text-xl" x-text="'$' + parseFloat(venta.total_final != null ? venta.total_final : venta.total || 0).toFixed(2)"></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Estado facturacion</p>
                        <span
                            class="inline-block px-2 py-1 rounded text-sm font-medium"
                            :class="{
                                'bg-green-100 text-green-800': (venta.estado_facturacion || 'pendiente') === 'facturada',
                                'bg-yellow-100 text-yellow-800': (venta.estado_facturacion || 'pendiente') === 'pendiente',
                                'bg-red-100 text-red-800': venta.estado_facturacion === 'error'
                            }"
                            x-text="venta.estado_facturacion || 'pendiente'"
                        ></span>
                    </div>
                    <div x-show="venta.cae">
                        <p class="text-sm text-gray-600">CAE</p>
                        <p class="font-medium" x-text="venta.cae"></p>
                    </div>
                    <div x-show="venta.cae_vencimiento">
                        <p class="text-sm text-gray-600">Vencimiento CAE</p>
                        <p class="font-medium" x-text="venta.cae_vencimiento"></p>
                    </div>
                    <div x-show="venta.comprobante_tipo && venta.comprobante_numero">
                        <p class="text-sm text-gray-600">Comprobante AFIP</p>
                        <p class="font-medium">
                            Factura <span x-text="venta.comprobante_tipo"></span>
                            <span x-text="fiscal && fiscal.punto_venta != null ? String(fiscal.punto_venta).padStart(4, '0') : '----'"></span>
                            -
                            <span x-text="venta.comprobante_numero != null ? String(venta.comprobante_numero).padStart(8, '0') : ''"></span>
                        </p>
                    </div>
                </div>
                <div class="mt-4 pt-4 border-t space-y-2 text-sm" x-show="venta.tipo_pago === 'mixto'">
                    <p class="font-medium text-gray-800">Detalle del pago</p>
                    <p x-show="parseFloat(venta.monto_efectivo || 0) > 0"><span class="text-gray-600">Efectivo:</span> <span class="font-medium" x-text="'$' + parseFloat(venta.monto_efectivo || 0).toFixed(2)"></span></p>
                    <p x-show="parseFloat(venta.monto_tarjeta || 0) > 0"><span class="text-gray-600">Tarjeta:</span> <span class="font-medium" x-text="'$' + parseFloat(venta.monto_tarjeta || 0).toFixed(2)"></span></p>
                    <p x-show="parseFloat(venta.monto_transferencia || 0) > 0"><span class="text-gray-600">Transferencia:</span> <span class="font-medium" x-text="'$' + parseFloat(venta.monto_transferencia || 0).toFixed(2)"></span></p>
                </div>
                <div class="mt-4 pt-4 border-t text-sm" x-show="venta.tipo_pago === 'transferencia'">
                    <p class="text-gray-600">Monto transferencia</p>
                    <p class="font-medium" x-text="'$' + parseFloat(venta.monto_transferencia || venta.total_final || 0).toFixed(2)"></p>
                </div>
                <div
                    class="mt-4 pt-4 border-t bg-amber-50 border border-amber-200 rounded-md p-3"
                    x-show="venta && (venta.tipo_pago === 'efectivo' || venta.tipo_pago === 'mixto')"
                    x-cloak
                >
                    <h5 class="font-semibold text-sm text-amber-900 mb-2">Calculadora de Vuelto</h5>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Total a cobrar en efectivo</label>
                            <div class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-sm font-semibold">
                                $<span x-text="(venta.tipo_pago === 'mixto' ? (parseFloat(venta.monto_efectivo || 0) || 0) : (parseFloat(venta.total_final != null ? venta.total_final : venta.total || 0) || 0)).toFixed(2)"></span>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Paga con</label>
                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                x-model.number="pagoCon"
                                placeholder="0.00"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
                            >
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1" x-text="((parseFloat(pagoCon) || 0) - (venta.tipo_pago === 'mixto' ? (parseFloat(venta.monto_efectivo || 0) || 0) : (parseFloat(venta.total_final != null ? venta.total_final : venta.total || 0) || 0))) >= 0 ? 'Vuelto' : 'Falta'"></label>
                            <div
                                class="w-full px-3 py-2 border rounded-md text-sm font-bold"
                                :class="((parseFloat(pagoCon) || 0) - (venta.tipo_pago === 'mixto' ? (parseFloat(venta.monto_efectivo || 0) || 0) : (parseFloat(venta.total_final != null ? venta.total_final : venta.total || 0) || 0))) >= 0
                                    ? 'bg-green-50 border-green-300 text-green-700'
                                    : 'bg-red-50 border-red-300 text-red-700'"
                            >
                                $<span x-text="Math.abs((parseFloat(pagoCon) || 0) - (venta.tipo_pago === 'mixto' ? (parseFloat(venta.monto_efectivo || 0) || 0) : (parseFloat(venta.total_final != null ? venta.total_final : venta.total || 0) || 0))).toFixed(2)"></span>
                            </div>
                        </div>
                    </div>
                    <p x-show="venta.tipo_pago === 'mixto'" x-cloak class="mt-2 text-xs text-amber-800">
                        En pago mixto, el vuelto se calcula solo sobre el monto en efectivo.
                    </p>
                </div>
            </div>

            <div x-show="fiscal && (fiscal.razon_social || fiscal.cuit_emisor)" class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold mb-4">Emisor (AFIP/ARCA)</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div x-show="fiscal.razon_social">
                        <p class="text-gray-600">Razon social</p>
                        <p class="font-medium" x-text="fiscal.razon_social"></p>
                    </div>
                    <div x-show="fiscal.cuit_emisor">
                        <p class="text-gray-600">CUIT</p>
                        <p class="font-medium" x-text="fiscal.cuit_emisor"></p>
                    </div>
                    <div x-show="fiscal.condicion_iva">
                        <p class="text-gray-600">Condicion IVA</p>
                        <p class="font-medium" x-text="fiscal.condicion_iva"></p>
                    </div>
                    <div x-show="fiscal.ambiente">
                        <p class="text-gray-600">Ambiente</p>
                        <p class="font-medium" x-text="fiscal.ambiente"></p>
                    </div>
                </div>
            </div>

            <div x-show="venta.estado_facturacion === 'error' && venta.afip_observaciones" class="bg-red-50 border border-red-200 rounded-lg p-4">
                <p class="text-sm font-semibold text-red-800">Error al facturar</p>
                <p class="text-sm text-red-700 mt-1" x-text="venta.afip_observaciones"></p>
            </div>

            <div
                x-show="puedeAgregarItems && venta"
                x-cloak
                class="bg-white rounded-lg shadow p-6 border border-dashed border-blue-200"
            >
                <h2 class="text-xl font-bold mb-3">Agregar productos</h2>
                <p class="text-sm text-gray-600 mb-4">Elegí un artículo y la cantidad para sumarlo a esta venta (no aplica si ya está facturada en AFIP).</p>
                <div
                    x-show="motivoBloqueoAgregarItems()"
                    class="mb-4 p-3 bg-amber-50 border border-amber-200 text-amber-900 text-sm rounded"
                    x-text="motivoBloqueoAgregarItems()"
                ></div>
                <div class="flex flex-col sm:flex-row flex-wrap gap-3 items-end">
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Producto</label>
                        <select
                            x-model="nuevoItem.producto_id"
                            :disabled="!puedeAgregarLineas()"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm disabled:bg-gray-100 disabled:cursor-not-allowed"
                        >
                            <option value="">Elegir producto</option>
                            <template x-for="p in productos" :key="p.id">
                                <option :value="String(p.id)" x-text="(p.codigo ? p.codigo + ' — ' : '') + p.nombre + ' ($' + parseFloat(p.precio_venta || 0).toFixed(2) + ')'"></option>
                            </template>
                        </select>
                    </div>
                    <div class="w-28">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cantidad</label>
                        <input
                            type="number"
                            min="1"
                            x-model.number="nuevoItem.cantidad"
                            :disabled="!puedeAgregarLineas()"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm disabled:bg-gray-100 disabled:cursor-not-allowed"
                        >
                    </div>
                    <button
                        type="button"
                        @click="agregarLineasVenta()"
                        :disabled="!puedeAgregarLineas() || agregandoItems || !nuevoItem.producto_id || !(parseInt(nuevoItem.cantidad) > 0)"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 text-sm"
                    >
                        <span x-show="!agregandoItems">Agregar a la venta</span>
                        <span x-show="agregandoItems" x-cloak>Guardando...</span>
                    </button>
                </div>
                <p class="text-xs text-amber-700 mt-3" x-show="venta && venta.tipo_pago === 'mixto' && puedeAgregarLineas()">
                    Pago mixto: si cambió el total, revisá que efectivo / tarjeta / transferencia sigan cuadrando.
                </p>
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
                                <th
                                    x-show="puedeAgregarItems && puedeAgregarLineas()"
                                    x-cloak
                                    class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase"
                                >Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <template x-for="item in venta.items || []" :key="item.id">
                                <tr>
                                    <td class="px-6 py-4 text-sm" x-text="item.producto?.nombre || '-'"></td>
                                    <td class="px-6 py-4 text-sm" x-text="item.cantidad"></td>
                                    <td class="px-6 py-4 text-sm" x-text="'$' + parseFloat(item.precio_unitario || 0).toFixed(2)"></td>
                                    <td class="px-6 py-4 text-sm font-medium" x-text="'$' + parseFloat(item.subtotal || 0).toFixed(2)"></td>
                                    <td
                                        x-show="puedeAgregarItems && puedeAgregarLineas()"
                                        x-cloak
                                        class="px-6 py-4 text-sm text-right"
                                    >
                                        <button
                                            type="button"
                                            @click="eliminarItemVenta(item)"
                                            :disabled="eliminandoItemId === item.id"
                                            class="px-2 py-1 bg-red-500 text-white rounded text-sm hover:bg-red-600 disabled:opacity-50"
                                        >
                                            <span x-show="eliminandoItemId !== item.id">Eliminar</span>
                                            <span x-show="eliminandoItemId === item.id" x-cloak>…</span>
                                        </button>
                                    </td>
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
function ventaDetalle(puedeAgregarItems, puedeEliminarVenta) {
    return {
        venta: null,
        fiscal: null,
        ventaId: @json($id ?? null),
        loading: true,
        facturando: false,
        agregandoItems: false,
        eliminando: false,
        eliminandoItemId: null,
        error: '',
        success: '',
        puedeAgregarItems: puedeAgregarItems === true,
        puedeEliminarVenta: puedeEliminarVenta === true,
        productos: [],
        nuevoItem: { producto_id: '', cantidad: 1 },
        pagoCon: '',
        guardandoTipoPago: false,
        editPago: {
            tipo_pago: 'efectivo',
            monto_efectivo: 0,
            monto_tarjeta: 0,
            monto_transferencia: 0,
            cuotas: null,
        },
        ventasIndexUrl: @json(url('/ventas')),
        
        etiquetaTipoPago(tipo) {
            const m = { efectivo: 'Efectivo', tarjeta: 'Tarjeta', transferencia: 'Transferencia', cuenta_corriente: 'Cuenta Corriente', mixto: 'Mixto' };
            return m[tipo] || tipo || '-';
        },

        motivoBloqueoAgregarItems() {
            if (!this.venta) return 'Cargando…';
            if (this.venta.estado === 'cancelada') {
                return 'No se pueden agregar productos a una venta cancelada.';
            }
            if (this.venta.estado === 'cerrada') {
                return 'No se pueden agregar productos a una venta cerrada.';
            }
            if ((this.venta.estado_facturacion || 'pendiente') === 'facturada') {
                return 'No se pueden agregar productos a una venta ya facturada.';
            }
            return null;
        },
        puedeAgregarLineas() {
            return this.motivoBloqueoAgregarItems() === null;
        },

        syncEditPagoFromVenta() {
            if (!this.venta) return;
            this.editPago = {
                tipo_pago: this.venta.tipo_pago || 'efectivo',
                monto_efectivo: parseFloat(this.venta.monto_efectivo || 0) || 0,
                monto_tarjeta: parseFloat(this.venta.monto_tarjeta || 0) || 0,
                monto_transferencia: parseFloat(this.venta.monto_transferencia || 0) || 0,
                cuotas: this.venta.cuotas ? parseInt(this.venta.cuotas, 10) : null,
            };
        },

        onCambioTipoPagoSelect() {
            this.pagoCon = '';
            // Mixto requiere montos: el usuario completa y pulsa Guardar.
            if (this.editPago.tipo_pago === 'mixto') {
                return;
            }
            this.guardarTipoPago();
        },

        async guardarTipoPago() {
            if (!this.puedeAgregarLineas() || !this.editPago.tipo_pago) return;
            try {
                this.guardandoTipoPago = true;
                this.error = '';
                this.success = '';
                const token = localStorage.getItem('token');
                const payload = { tipo_pago: this.editPago.tipo_pago };
                if (this.editPago.tipo_pago === 'mixto') {
                    payload.monto_efectivo = parseFloat(this.editPago.monto_efectivo) || 0;
                    payload.monto_tarjeta = parseFloat(this.editPago.monto_tarjeta) || 0;
                    payload.monto_transferencia = parseFloat(this.editPago.monto_transferencia) || 0;
                }
                if (this.editPago.tipo_pago === 'tarjeta' && this.editPago.cuotas) {
                    payload.cuotas = parseInt(this.editPago.cuotas, 10);
                }
                const response = await axios.patch(
                    `/api/ventas/${this.ventaId}/tipo-pago`,
                    payload,
                    { headers: token ? { Authorization: `Bearer ${token}` } : {} }
                );
                this.venta = response.data;
                this.syncEditPagoFromVenta();
                this.success = 'Tipo de pago actualizado.';
                setTimeout(() => { this.success = ''; }, 3500);
            } catch (error) {
                this.error = error.response?.data?.message || 'No se pudo cambiar el tipo de pago.';
                this.syncEditPagoFromVenta();
            } finally {
                this.guardandoTipoPago = false;
            }
        },

        async cerrarVenta({ silencioso = false } = {}) {
            try {
                if (!silencioso) {
                    this.error = '';
                    this.success = '';
                }

                if (!this.venta) {
                    await this.fetch();
                }

                const estadoActual = (this.venta?.estado || '').toLowerCase();
                if (estadoActual === 'cancelada') {
                    if (!silencioso) this.error = 'No se puede cerrar una venta cancelada.';
                    return false;
                }
                if (estadoActual === 'cerrada') {
                    if (!silencioso) this.success = 'La venta ya está cerrada.';
                    return true;
                }

                const token = localStorage.getItem('token');
                const response = await axios.post(`/api/ventas/${this.ventaId}/cerrar`, {}, {
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                await this.fetch();
                if (!silencioso) {
                    this.success = response.data?.message || 'Venta cerrada correctamente.';
                }
                return true;
            } catch (error) {
                if (!silencioso) {
                    this.error = error.response?.data?.message || 'No se pudo cerrar la venta.';
                } else {
                    this.error = 'No se pudo cerrar la venta antes de imprimir el ticket.';
                }
                return false;
            }
        },

        async eliminarVenta() {
            if (!this.puedeEliminarVenta || !this.ventaId) return;
            if ((this.venta?.estado_facturacion || 'pendiente') === 'facturada') {
                this.error = 'No se puede eliminar una venta ya facturada en AFIP/ARCA.';
                return;
            }
            const label = this.venta?.numero_factura || ('#' + this.ventaId);
            if (!confirm('¿Eliminar la venta ' + label + '? Se revertirá el stock y los cargos en cuenta corriente si los hubiera.')) {
                return;
            }
            this.eliminando = true;
            this.error = '';
            this.success = '';
            try {
                const token = localStorage.getItem('token');
                await axios.delete('/api/ventas/' + this.ventaId, {
                    headers: token ? { Authorization: 'Bearer ' + token } : {},
                });
                this.success = 'Venta eliminada. Redirigiendo…';
                setTimeout(() => {
                    window.location.href = this.ventasIndexUrl;
                }, 600);
            } catch (error) {
                this.error = error.response?.data?.message || 'No se pudo eliminar la venta.';
            } finally {
                this.eliminando = false;
            }
        },

        async init() {
            if (this.ventaId) {
                const tasks = [this.fetch(), this.fetchFiscal()];
                if (this.puedeAgregarItems) {
                    tasks.push(this.fetchProductos());
                }
                await Promise.all(tasks);
            }
        },

        async fetchProductos() {
            try {
                const token = localStorage.getItem('token');
                const response = await axios.get('/api/productos', {
                    params: { all: 'true' },
                    headers: token ? { Authorization: `Bearer ${token}` } : {},
                });
                this.productos = Array.isArray(response.data) ? response.data : [];
            } catch (e) {
                this.productos = [];
            }
        },

        async agregarLineasVenta() {
            const pid = this.nuevoItem.producto_id;
            const cant = parseInt(this.nuevoItem.cantidad, 10);
            if (!pid || cant < 1) return;
            try {
                this.agregandoItems = true;
                this.error = '';
                this.success = '';
                const token = localStorage.getItem('token');
                const response = await axios.post(
                    `/api/ventas/${this.ventaId}/items`,
                    {
                        items: [{ producto_id: parseInt(pid, 10), cantidad: cant }],
                    },
                    { headers: { Authorization: `Bearer ${token}` } }
                );
                this.venta = response.data;
                this.syncEditPagoFromVenta();
                this.success = 'Productos agregados correctamente.';
                this.nuevoItem = { producto_id: '', cantidad: 1 };
                setTimeout(() => { this.success = ''; }, 3500);
            } catch (error) {
                this.error = error.response?.data?.message || 'No se pudieron agregar los productos.';
            } finally {
                this.agregandoItems = false;
            }
        },

        async eliminarItemVenta(item) {
            if (!item?.id || !this.puedeAgregarLineas()) return;
            const nombre = item.producto?.nombre || ('ítem #' + item.id);
            if (!confirm('¿Eliminar "' + nombre + '" de esta venta? Se devolverá el stock.')) {
                return;
            }
            try {
                this.eliminandoItemId = item.id;
                this.error = '';
                this.success = '';
                const token = localStorage.getItem('token');
                const response = await axios.delete(
                    `/api/ventas/${this.ventaId}/items/${item.id}`,
                    { headers: token ? { Authorization: `Bearer ${token}` } : {} }
                );
                this.venta = response.data;
                this.syncEditPagoFromVenta();
                this.success = 'Producto eliminado de la venta.';
                setTimeout(() => { this.success = ''; }, 3500);
            } catch (error) {
                this.error = error.response?.data?.message || 'No se pudo eliminar el producto.';
            } finally {
                this.eliminandoItemId = null;
            }
        },

        async fetchFiscal() {
            try {
                const token = localStorage.getItem('token');
                const response = await axios.get('/api/configuracion-fiscal-comprobante', {
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                this.fiscal = response.data;
            } catch (e) {
                this.fiscal = null;
            }
        },
        
        async fetch() {
            try {
                this.loading = true;
                this.error = '';
                const token = localStorage.getItem('token');
                const response = await axios.get(`/api/ventas/${this.ventaId}`, {
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                this.venta = response.data;
                this.syncEditPagoFromVenta();
            } catch (error) {
                this.error = error.response?.data?.message || 'Error al cargar la venta.';
            } finally {
                this.loading = false;
            }
        },

        async facturarAfip() {
            try {
                this.facturando = true;
                this.error = '';
                this.success = '';
                const token = localStorage.getItem('token');
                const response = await axios.post(`/api/ventas/${this.ventaId}/facturar-afip`, {}, {
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                this.success = response.data?.message || 'Venta facturada correctamente.';
                await this.fetch();
                await this.fetchFiscal();
            } catch (error) {
                this.error = error.response?.data?.error || error.response?.data?.message || 'No se pudo facturar la venta.';
            } finally {
                this.facturando = false;
            }
        },
        
        async imprimirComprobante() {
            if (!this.venta) return;

            if ((this.venta.estado || '').toLowerCase() === 'abierta') {
                const ventaCerrada = await this.cerrarVenta({ silencioso: true });
                if (!ventaCerrada) return;
            }

            const f = this.fiscal || {};
            const fechaVenta = new Date(this.venta.created_at || this.venta.fecha).toLocaleString('es-AR');
            const cliente = this.venta.cliente;
            const items = this.venta.items || [];
            const totalBruto = items.reduce((acc, item) => acc + parseFloat(item.subtotal || 0), 0);
            const descuento = parseFloat(this.venta.descuento || 0);
            const totalFinal = parseFloat(this.venta.total_final || this.venta.total || 0);
            const tipoLetra = this.venta.comprobante_tipo || 'B';
            const ptoFmt = f.punto_venta != null ? String(f.punto_venta).padStart(4, '0') : '----';
            const nroFmt = this.venta.comprobante_numero != null ? String(this.venta.comprobante_numero).padStart(8, '0') : '--------';
            const vtoCae = this.venta.cae_vencimiento
                ? new Date(this.venta.cae_vencimiento).toLocaleDateString('es-AR')
                : '';
            const tituloComp = this.venta.estado_facturacion === 'facturada' && this.venta.cae
                ? `FACTURA ${tipoLetra}`
                : 'COMPROBANTE DE VENTA';
            
            const contenidoHTML = `
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset="UTF-8">
                    <title>Comprobante de Venta</title>
                    <style>
                        @media print {
                            @page {
                                size: auto;
                                margin: 3mm;
                            }
                            * {
                                -webkit-print-color-adjust: exact !important;
                                print-color-adjust: exact !important;
                            }
                            html, body {
                                width: auto !important;
                                max-width: 220px !important;
                                margin: 0 auto !important;
                                padding: 0 !important;
                                background: #fff !important;
                                color: #000 !important;
                            }
                            .header h1, .header p, .info-label, .info-value,
                            th, td, .total-row, .total-final, .footer, .numero-factura {
                                color: #000 !important;
                            }
                            .estado-badge {
                                background: #333 !important;
                                color: #fff !important;
                            }
                            .info-row, .total-row {
                                display: table !important;
                                width: 100% !important;
                            }
                            .info-row .info-label, .info-row .info-value,
                            .total-row > span {
                                display: table-cell !important;
                                vertical-align: top !important;
                            }
                            .info-row .info-value, .total-row > span:last-child {
                                text-align: right !important;
                            }
                            .no-print {
                                display: none !important;
                            }
                        }
                        body {
                            font-family: Arial, sans-serif;
                            max-width: 48mm;
                            margin: 0 auto;
                            padding: 2mm;
                            font-size: 10px;
                            background: #fff;
                            color: #000;
                        }
                        .header {
                            text-align: center;
                            border-bottom: 1px dashed #333;
                            padding-bottom: 2mm;
                            margin-bottom: 2mm;
                        }
                        .header h1 {
                            margin: 0;
                            font-size: 12px;
                            color: #333;
                            font-weight: bold;
                        }
                        .header p {
                            margin: 2px 0;
                            color: #666;
                        }
                        .info-section {
                            margin-bottom: 2mm;
                            padding: 2mm 0;
                            border-bottom: 1px dashed #eee;
                        }
                        .info-row {
                            display: flex;
                            justify-content: space-between;
                            margin-bottom: 1mm;
                        }
                        .info-label {
                            font-weight: bold;
                            color: #333;
                        }
                        .info-value {
                            color: #666;
                            text-align: right;
                            max-width: 26mm;
                            overflow: hidden;
                            white-space: nowrap;
                            text-overflow: ellipsis;
                        }
                        table {
                            width: 100%;
                            border-collapse: collapse;
                            margin: 2mm 0;
                        }
                        th {
                            border-bottom: 1px dashed #333;
                            padding: 2px 0;
                            text-align: left;
                            font-size: 9px;
                        }
                        td {
                            padding: 1px 0;
                            font-size: 9px;
                        }
                        .text-right {
                            text-align: right;
                        }
                        .totals {
                            margin-top: 2mm;
                            padding-top: 2mm;
                            border-top: 1px dashed #333;
                        }
                        .total-row {
                            display: flex;
                            justify-content: space-between;
                            margin-bottom: 1mm;
                            font-size: 10px;
                        }
                        .total-final {
                            font-size: 11px;
                            font-weight: bold;
                            color: #333;
                            margin-top: 1mm;
                            padding-top: 1mm;
                            border-top: 1px solid #ddd;
                        }
                        .footer {
                            margin-top: 3mm;
                            text-align: center;
                            color: #666;
                            font-size: 8px;
                            border-top: 1px dashed #ddd;
                            padding-top: 2mm;
                        }
                        .button-container {
                            text-align: center;
                            margin: 5mm 0 0;
                        }
                        button {
                            background-color: #007bff;
                            color: white;
                            border: none;
                            padding: 4px 8px;
                            font-size: 10px;
                            cursor: pointer;
                            border-radius: 4px;
                        }
                        button:hover {
                            background-color: #0056b3;
                        }
                        .numero-factura {
                            font-size: 11px;
                            color: #007bff;
                            font-weight: bold;
                        }
                        .estado-badge {
                            display: inline-block;
                            padding: 2px 6px;
                            border-radius: 20px;
                            font-size: 9px;
                            font-weight: bold;
                            background-color: #28a745;
                            color: white;
                        }
                    </style>
                </head>
                <body>
                    <div class="header">
                        ${f.razon_social ? `<p style="font-weight:bold;font-size:11px;margin:0 0 2px;">${f.razon_social}</p>` : ''}
                        ${f.cuit_emisor ? `<p style="margin:0;font-size:9px;">CUIT: ${f.cuit_emisor}</p>` : ''}
                        ${f.condicion_iva ? `<p style="margin:2px 0 0;font-size:8px;">${f.condicion_iva}</p>` : ''}
                        <h1 style="margin-top:4px;">${tituloComp}</h1>
                        <p class="numero-factura">${this.venta.numero_factura || 'N/A'}</p>
                        ${this.venta.estado_facturacion === 'facturada' && this.venta.cae ? `
                            <p style="font-size:10px;margin:2px 0;">${tipoLetra} ${ptoFmt}-${nroFmt}</p>
                        ` : ''}
                        <p>Fecha: ${fechaVenta}</p>
                        <p class="estado-badge">${this.venta.estado || 'abierta'}</p>
                    </div>

                    <div class="info-section">
                        ${cliente ? `
                            <div class="info-row">
                                <span class="info-label">Cliente:</span>
                                <span class="info-value">${cliente.nombre} ${cliente.apellido || ''}</span>
                            </div>
                            ${cliente.dni ? `
                                <div class="info-row">
                                    <span class="info-label">DNI:</span>
                                    <span class="info-value">${cliente.dni}</span>
                                </div>
                            ` : ''}
                            ${cliente.telefono ? `
                                <div class="info-row">
                                    <span class="info-label">Teléfono:</span>
                                    <span class="info-value">${cliente.telefono}</span>
                                </div>
                            ` : ''}
                            ${cliente.direccion ? `
                                <div class="info-row">
                                    <span class="info-label">Dirección:</span>
                                    <span class="info-value">${cliente.direccion}</span>
                                </div>
                            ` : ''}
                        ` : `
                            <div class="info-row">
                                <span class="info-label">Cliente:</span>
                                <span class="info-value">Consumidor Final</span>
                            </div>
                        `}
                        <div class="info-row">
                            <span class="info-label">Tipo de Pago:</span>
                            <span class="info-value">${this.etiquetaTipoPago(this.venta.tipo_pago)}</span>
                        </div>
                        ${this.venta.caja ? `
                            <div class="info-row">
                                <span class="info-label">Caja:</span>
                                <span class="info-value">${this.venta.caja.nombre || 'Caja #' + this.venta.caja.id}</span>
                            </div>
                        ` : ''}
                    </div>

                    <table>
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Producto</th>
                                <th class="text-right">Cantidad</th>
                                <th class="text-right">Precio Unit.</th>
                                <th class="text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${items.map(item => `
                                <tr>
                                    <td>${item.producto?.codigo || '-'}</td>
                                    <td>${item.producto?.nombre || '-'}</td>
                                    <td class="text-right">${item.cantidad}</td>
                                    <td class="text-right">$${parseFloat(item.precio_unitario || 0).toFixed(2)}</td>
                                    <td class="text-right">$${parseFloat(item.subtotal || 0).toFixed(2)}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>

                    <div class="totals">
                        <div class="total-row">
                            <span>Subtotal:</span>
                            <span>$${totalBruto.toFixed(2)}</span>
                        </div>
                        ${descuento > 0 ? `
                            <div class="total-row">
                                <span>Descuento:</span>
                                <span>-$${descuento.toFixed(2)}</span>
                            </div>
                        ` : ''}
                        <div class="total-row total-final">
                            <span>TOTAL:</span>
                            <span>$${totalFinal.toFixed(2)}</span>
                        </div>
                    </div>

                    ${this.venta.estado_facturacion === 'facturada' && this.venta.cae ? `
                        <div class="info-section" style="border-top:1px dashed #333;padding-top:3mm;">
                            <div class="info-row">
                                <span class="info-label">CAE:</span>
                                <span class="info-value" style="white-space:normal;word-break:break-all;">${this.venta.cae}</span>
                            </div>
                            ${vtoCae ? `
                                <div class="info-row">
                                    <span class="info-label">Vto CAE:</span>
                                    <span class="info-value">${vtoCae}</span>
                                </div>
                            ` : ''}
                            <p style="font-size:7px;margin-top:2mm;text-align:center;color:#444;">Comprobante autorizado por AFIP/ARCA</p>
                        </div>
                    ` : ''}

                    ${this.venta.tipo_pago === 'mixto' ? (() => {
                        const montoTarjeta = parseFloat(this.venta.monto_tarjeta || 0);
                        const montoEfectivo = parseFloat(this.venta.monto_efectivo || 0);
                        const montoTransferencia = parseFloat(this.venta.monto_transferencia || 0);
                        const sumaMontos = montoTarjeta + montoEfectivo + montoTransferencia;
                        const restante = totalFinal - sumaMontos;
                        
                        if (montoTarjeta > 0 || montoEfectivo > 0 || montoTransferencia > 0) {
                            return `
                                <div class="totals" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">
                                    <h3 style="margin: 0 0 10px 0; font-size: 14px; color: #333;">Detalle de Pago:</h3>
                                    ${montoEfectivo > 0 ? `
                                        <div class="total-row">
                                            <span>Efectivo:</span>
                                            <span>$${montoEfectivo.toFixed(2)}</span>
                                        </div>
                                    ` : ''}
                                    ${montoTarjeta > 0 ? `
                                        <div class="total-row">
                                            <span>Tarjeta:</span>
                                            <span>$${montoTarjeta.toFixed(2)}</span>
                                        </div>
                                    ` : ''}
                                    ${montoTransferencia > 0 ? `
                                        <div class="total-row">
                                            <span>Transferencia:</span>
                                            <span>$${montoTransferencia.toFixed(2)}</span>
                                        </div>
                                    ` : ''}
                                    ${this.venta.cuotas && parseInt(this.venta.cuotas) > 0 ? `
                                        <div class="total-row">
                                            <span>Cuotas:</span>
                                            <span>${this.venta.cuotas} cuota(s)</span>
                                        </div>
                                        ${restante > 0 ? `
                                            <div class="total-row">
                                                <span>Monto en cuotas:</span>
                                                <span>$${restante.toFixed(2)}</span>
                                            </div>
                                        ` : ''}
                                    ` : ''}
                                </div>
                            `;
                        }
                        return '';
                    })() : ''}

                    ${this.venta.tipo_pago === 'tarjeta' && this.venta.cuotas && parseInt(this.venta.cuotas) > 0 ? `
                        <div class="totals" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">
                            <div class="total-row">
                                <span>Cuotas:</span>
                                <span>${this.venta.cuotas} cuota(s) de $${(totalFinal / parseInt(this.venta.cuotas)).toFixed(2)}</span>
                            </div>
                        </div>
                    ` : ''}

                    <div class="footer">
                        <p><strong>Gracias por su compra</strong></p>
                        <p>Este es un comprobante de venta válido.</p>
                        <p>Conserve este documento para sus registros.</p>
                    </div>

                    <div class="button-container no-print">
                        <button onclick="window.print()">Imprimir</button>
                        <button onclick="window.close()" style="background-color: #6c757d; margin-left: 10px;">Cerrar</button>
                    </div>
                </body>
                </html>
            `;

            const ventanaImpresion = window.open('', '_blank');
            if (!ventanaImpresion) {
                this.error = 'El navegador bloqueó la ventana emergente. Permita ventanas para imprimir.';
                return;
            }
            ventanaImpresion.document.open();
            ventanaImpresion.document.write(contenidoHTML);
            ventanaImpresion.document.close();
            setTimeout(() => {
                try {
                    ventanaImpresion.focus();
                    ventanaImpresion.print();
                } catch (e) {}
            }, 450);
        }
    }
}
</script>
@endpush
@endsection
