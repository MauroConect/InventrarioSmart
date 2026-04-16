@extends('layouts.app')

@section('title', 'Ventas de Helado - Danielles')
@section('page-title', 'Ventas')

@section('content')
<div x-data="ventas()" x-init="init()" class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
        <h1 class="text-2xl sm:text-3xl font-bold">Ventas</h1>
        <button
            @click="openModal()"
            :disabled="cajasAbiertas.length === 0"
            :class="cajasAbiertas.length === 0 ? 'bg-gray-400 cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700'"
            class="w-full sm:w-auto px-4 py-2 rounded text-white"
        >
            + Nueva Venta de Helado
        </button>
    </div>

    <div x-show="cajasAbiertas.length === 0" x-cloak class="p-4 bg-yellow-100 border border-yellow-400 text-yellow-800 rounded">
        No hay ninguna caja abierta. Debe abrir una caja para poder registrar ventas.
    </div>

    <div x-show="cajasAbiertas.length > 0" x-cloak class="p-4 bg-blue-50 border border-blue-200 rounded">
        <label class="block text-sm font-medium text-gray-700 mb-2">Seleccionar Caja:</label>
        <select x-model="cajaSeleccionada" @change="seleccionarCaja()" class="w-full md:w-auto px-3 py-2 border border-gray-300 rounded-md bg-white">
            <option value="">Seleccionar caja...</option>
            <template x-for="caja in cajasAbiertas" :key="caja.id">
                <option :value="caja.id" x-text="(caja.nombre || 'Caja #' + caja.id) + ' - ' + new Date(caja.fecha_apertura).toLocaleString()"></option>
            </template>
        </select>
    </div>

    <div x-show="error" x-cloak class="p-4 bg-red-100 border border-red-400 text-red-700 rounded" x-text="error"></div>
    <div x-show="success" x-cloak class="p-4 bg-green-100 border border-green-400 text-green-700 rounded" x-text="success"></div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <template x-if="loadingLista">
            <div class="p-8 text-center text-gray-500">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <p class="mt-2">Cargando ventas...</p>
            </div>
        </template>
        
        <template x-if="!loadingLista">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cliente</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vendedor</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo Pago</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="venta in ventas" :key="venta.id">
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900" x-text="venta.id"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="new Date(venta.created_at).toLocaleString()"></td>
                                <td class="px-6 py-4 text-sm" x-text="venta.cliente ? (venta.cliente.nombre + ' ' + venta.cliente.apellido) : 'Cliente General'"></td>
                                <td class="px-6 py-4 text-sm text-gray-600" x-text="venta.usuario ? venta.usuario.name : '—'"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium" x-text="'$' + parseFloat(venta.total || 0).toFixed(2)"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm" x-text="etiquetaTipoPago(venta.tipo_pago)"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span
                                        class="px-2 py-1 text-xs rounded-full"
                                        :class="(venta.estado || '').toLowerCase() === 'abierta' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'"
                                        x-text="venta.estado || 'cerrada'"
                                    ></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a :href="'/ventas/' + venta.id" class="text-blue-600 hover:text-blue-900">Ver Detalle</a>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </template>
    </div>

    <!-- Modal Nueva Venta -->
    <div x-show="showModal" x-cloak class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4" @click.self="closeModal()">
        <div class="relative bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] overflow-y-auto" @click.stop>
            <div class="sticky top-0 bg-white border-b px-6 py-4">
                <h3 class="text-xl font-bold">Nueva Venta</h3>
            </div>
            <form @submit.prevent="guardarVenta()" class="p-6 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Cliente <span x-show="tipoPago === 'cuenta_corriente'" x-cloak class="text-red-600">*</span>
                            <span x-show="tipoPago !== 'cuenta_corriente'">(opcional)</span>
                        </label>
                        <select x-model="clienteId" class="w-full px-3 py-2 border border-gray-300 rounded-md" :required="tipoPago === 'cuenta_corriente'">
                            <option value="">Cliente General</option>
                            <template x-for="cliente in clientes" :key="cliente.id">
                                <option :value="cliente.id" x-text="cliente.nombre + ' ' + cliente.apellido"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Pago *</label>
                        <p class="text-xs text-gray-500 mb-1">Se guarda al confirmar o al imprimir el ticket (el botón verde registra la venta).</p>
                        <select x-model="tipoPago" @change="pagoCon = ''" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                            <option value="efectivo">Efectivo</option>
                            <option value="tarjeta">Tarjeta</option>
                            <option value="transferencia">Transferencia</option>
                            <option value="cuenta_corriente">Cuenta Corriente</option>
                            <option value="mixto">Mixto</option>
                        </select>
                    </div>
                </div>

                <p x-show="tipoPago === 'cuenta_corriente'" x-cloak class="text-sm text-amber-900 bg-amber-50 border border-amber-200 rounded-md px-3 py-2">
                    El cliente debe tener una cuenta corriente activa (la crea un administrador). Se cargará el importe en el saldo de esa cuenta.
                </p>

                <div x-show="tipoPago === 'mixto'" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Monto Tarjeta</label>
                        <input type="number" step="0.01" x-model.number="montoTarjeta" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Monto Efectivo</label>
                        <input type="number" step="0.01" x-model.number="montoEfectivo" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Monto Transferencia</label>
                        <input type="number" step="0.01" x-model.number="montoTransferencia" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descuento</label>
                    <input type="number" step="0.01" x-model.number="descuento" class="w-full px-3 py-2 border border-gray-300 rounded-md" value="0">
                </div>

                <div class="border-t pt-4">
                    <div class="flex justify-between items-center mb-4">
                        <h4 class="font-semibold">Helados</h4>
                        <div class="flex gap-2">
                            <button type="button" @click="imprimirPresupuesto()" :disabled="loadingSubmit" class="px-3 py-1 bg-green-600 text-white rounded text-sm hover:bg-green-700 flex items-center gap-1 disabled:opacity-50 disabled:cursor-not-allowed">
                                <span x-show="!loadingSubmit">🖨️ Imprimir y guardar venta</span>
                                <span x-show="loadingSubmit" x-cloak>Guardando…</span>
                            </button>
                            <button type="button" @click="agregarItem()" class="px-3 py-1 bg-gray-200 rounded text-sm hover:bg-gray-300">+ Agregar</button>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <template x-for="(item, index) in items" :key="index">
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-2 items-end">
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Producto</label>
                                    <div class="space-y-1">
                                        <input 
                                            type="text" 
                                            x-model="busquedaProducto[index]"
                                            @input="busquedaProducto[index] = $event.target.value"
                                            placeholder="Buscar producto..."
                                            class="w-full px-2 py-1 border border-gray-300 rounded-md text-sm"
                                        />
                                        <select 
                                            x-model="item.producto_id" 
                                            @change="busquedaProducto[index] = ''"
                                            class="w-full px-2 py-1 border border-gray-300 rounded-md text-sm" 
                                            required
                                        >
                                            <option value="">Seleccionar...</option>
                                            <template x-for="prod in filtrarProductos(index)" :key="prod.id">
                                                <option :value="prod.id" x-text="(prod.nombre || '') + (prod.codigo ? ' (' + prod.codigo + ')' : '') + ' - $' + (parseFloat(prod.precio_venta || 0).toFixed(2))"></option>
                                            </template>
                                        </select>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Cantidad</label>
                                    <input type="number" x-model.number="item.cantidad" class="w-full px-2 py-1 border border-gray-300 rounded-md text-sm" required min="1">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Subtotal</label>
                                    <input type="text" :value="calcularSubtotal(item)" readonly class="w-full px-2 py-1 border border-gray-300 rounded-md text-sm bg-gray-50">
                                </div>
                                <div>
                                    <button type="button" @click="eliminarItem(index)" x-show="items.length > 1" class="px-2 py-1 bg-red-500 text-white rounded text-sm">Eliminar</button>
                                </div>
                            </div>
                        </template>
                    </div>
                    <div class="mt-4 pt-4 border-t">
                        <div class="flex justify-end">
                            <div class="text-right">
                                <p class="text-sm text-gray-600">Total: <span class="text-xl font-bold" x-text="'$' + calcularTotal().toFixed(2)"></span></p>
                            </div>
                        </div>
                    </div>
                    <div x-show="tipoPago === 'efectivo' || tipoPago === 'mixto'" x-cloak class="mt-4 bg-amber-50 border border-amber-200 rounded-md p-3">
                        <h5 class="font-semibold text-sm text-amber-900 mb-2">Calculadora de Vuelto</h5>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Total a cobrar en efectivo</label>
                                <div class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-sm font-semibold">
                                    $<span x-text="(tipoPago === 'mixto' ? (parseFloat(montoEfectivo) || 0) : calcularTotal()).toFixed(2)"></span>
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
                                <label class="block text-xs font-medium text-gray-700 mb-1" x-text="((parseFloat(pagoCon) || 0) - (tipoPago === 'mixto' ? (parseFloat(montoEfectivo) || 0) : calcularTotal())) >= 0 ? 'Vuelto' : 'Falta'"></label>
                                <div
                                    class="w-full px-3 py-2 border rounded-md text-sm font-bold"
                                    :class="((parseFloat(pagoCon) || 0) - (tipoPago === 'mixto' ? (parseFloat(montoEfectivo) || 0) : calcularTotal())) >= 0
                                        ? 'bg-green-50 border-green-300 text-green-700'
                                        : 'bg-red-50 border-red-300 text-red-700'"
                                >
                                    $<span x-text="Math.abs((parseFloat(pagoCon) || 0) - (tipoPago === 'mixto' ? (parseFloat(montoEfectivo) || 0) : calcularTotal())).toFixed(2)"></span>
                                </div>
                            </div>
                        </div>
                        <p x-show="tipoPago === 'mixto'" x-cloak class="mt-2 text-xs text-amber-800">
                            En pago mixto, el vuelto se calcula solo sobre el monto en efectivo.
                        </p>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Adjuntar archivos (opcional)
                    </label>
                    <input
                        type="file"
                        multiple
                        @change="adjuntos = Array.from($event.target.files || [])"
                        accept="image/*,.pdf,.doc,.docx"
                        class="block w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                    />
                    <p class="mt-1 text-xs text-gray-500">
                        Puede seleccionar múltiples archivos (imágenes, PDFs, documentos)
                    </p>
                    <div x-show="adjuntos && adjuntos.length > 0" x-cloak class="mt-2">
                        <p class="text-xs text-gray-600">
                            <span x-text="adjuntos.length"></span> archivo(s) seleccionado(s):
                        </p>
                        <ul class="mt-1 text-xs text-gray-500 list-disc list-inside">
                            <template x-for="(archivo, index) in adjuntos" :key="index">
                                <li x-text="archivo.name"></li>
                            </template>
                        </ul>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-4 border-t">
                    <button type="button" @click="imprimirPresupuesto()" :disabled="loadingSubmit" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="!loadingSubmit">🖨️ Imprimir y guardar venta</span>
                        <span x-show="loadingSubmit" x-cloak>Guardando…</span>
                    </button>
                    <button type="button" @click="closeModal()" class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50">Cancelar</button>
                    <button type="submit" :disabled="loadingSubmit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50">
                        <span x-show="!loadingSubmit">Guardar Venta</span>
                        <span x-show="loadingSubmit" x-cloak>Guardando...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
const TIPO_PAGO_LABELS = {
    efectivo: 'Efectivo',
    tarjeta: 'Tarjeta',
    transferencia: 'Transferencia',
    cuenta_corriente: 'Cuenta Corriente',
    mixto: 'Mixto',
};

function etiquetaTipoPagoTicket(tipo) {
    return TIPO_PAGO_LABELS[tipo] ?? tipo ?? '-';
}

const TICKET_THERMAL_STYLES = `
                <style>
                    * { box-sizing: border-box; }
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
                        th, td, .total-row, .total-final, .footer, .footer p {
                            color: #000 !important;
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
                    html, body {
                        margin: 0;
                        padding: 0;
                    }
                    body {
                        font-family: Arial, Helvetica, sans-serif;
                        max-width: 48mm;
                        width: 100%;
                        margin: 0 auto;
                        padding: 2mm;
                        font-size: 10px;
                        line-height: 1.25;
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
                    }
                    .header p {
                        margin: 2px 0;
                        color: #666;
                        font-size: 9px;
                    }
                    .info-section {
                        margin-bottom: 2mm;
                    }
                    .info-row {
                        display: flex;
                        justify-content: space-between;
                        gap: 1mm;
                        margin-bottom: 1mm;
                        font-size: 9px;
                    }
                    .info-label {
                        font-weight: bold;
                        color: #333;
                        flex-shrink: 0;
                    }
                    .info-value {
                        color: #666;
                        text-align: right;
                        max-width: 28mm;
                        word-break: break-word;
                    }
                    table {
                        width: 100%;
                        border-collapse: collapse;
                        margin: 2mm 0;
                        table-layout: fixed;
                    }
                    th {
                        border-bottom: 1px dashed #333;
                        padding: 2px 0;
                        text-align: left;
                        font-size: 8px;
                        color: #333;
                    }
                    td {
                        padding: 1px 0;
                        font-size: 8px;
                        word-break: break-word;
                        vertical-align: top;
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
                        font-size: 9px;
                        gap: 1mm;
                    }
                    .total-final {
                        font-size: 10px;
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
                        font-size: 7px;
                        border-top: 1px dashed #ddd;
                        padding-top: 2mm;
                        line-height: 1.3;
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
                </style>
`;

function construirHtmlTicketDesdeVentaGuardada(venta) {
    const fechaStr = venta.fecha
        ? new Date(venta.fecha).toLocaleString('es-AR')
        : new Date().toLocaleString('es-AR');
    const clienteSeleccionado = venta.cliente;
    const totalBruto = parseFloat(venta.total || 0) || 0;
    const descuentoNum = parseFloat(venta.descuento || 0) || 0;
    const totalFinal =
        parseFloat(venta.total_final != null ? venta.total_final : totalBruto - descuentoNum) || 0;
    const tipoPagoV = venta.tipo_pago || 'efectivo';

    const itemsRows = (venta.items || [])
        .map((item) => {
            const p = item.producto;
            const cant = parseInt(item.cantidad, 10) || 0;
            const pu = parseFloat(item.precio_unitario || 0) || 0;
            const sub = parseFloat(item.subtotal || 0) || 0;
            return `
                            <tr>
                                <td>${p?.codigo || '-'}</td>
                                <td>${p?.nombre || '-'}</td>
                                <td class="text-right">${cant}</td>
                                <td class="text-right">${pu.toFixed(2)}</td>
                                <td class="text-right">${sub.toFixed(2)}</td>
                            </tr>`;
        })
        .join('');

    const bloqueCliente = clienteSeleccionado
        ? `
                        <div class="info-row">
                            <span class="info-label">Cliente:</span>
                            <span class="info-value">${clienteSeleccionado.nombre} ${clienteSeleccionado.apellido || ''}</span>
                        </div>
                        ${clienteSeleccionado.dni ? `
                            <div class="info-row">
                                <span class="info-label">DNI:</span>
                                <span class="info-value">${clienteSeleccionado.dni}</span>
                            </div>
                        ` : ''}
                        ${clienteSeleccionado.telefono ? `
                            <div class="info-row">
                                <span class="info-label">Teléfono:</span>
                                <span class="info-value">${clienteSeleccionado.telefono}</span>
                            </div>
                        ` : ''}`
        : `
                        <div class="info-row">
                            <span class="info-label">Cliente:</span>
                            <span class="info-value">Consumidor Final</span>
                        </div>`;

    const montoTarjetaNum = parseFloat(venta.monto_tarjeta || 0) || 0;
    const montoEfectivoNum = parseFloat(venta.monto_efectivo || 0) || 0;
    const montoTransferenciaNum = parseFloat(venta.monto_transferencia || 0) || 0;
    const cuotasNum = venta.cuotas != null && venta.cuotas !== '' ? parseInt(venta.cuotas, 10) : 0;
    const sumaMontos = montoTarjetaNum + montoEfectivoNum + montoTransferenciaNum;
    const restante = totalFinal - sumaMontos;

    let bloquePagoExtra = '';
    if (tipoPagoV === 'mixto') {
        if (montoTarjetaNum > 0 || montoEfectivoNum > 0 || montoTransferenciaNum > 0 || cuotasNum > 0) {
            bloquePagoExtra = `
                            <div class="totals" style="margin-top: 2mm; padding-top: 2mm; border-top: 1px dashed #333;">
                                <h3 style="margin: 0 0 1mm 0; font-size: 9px; font-weight: bold; color: #333;">Detalle de Pago:</h3>
                                ${montoEfectivoNum > 0 ? `
                                    <div class="total-row">
                                        <span>Efectivo:</span>
                                        <span>${montoEfectivoNum.toFixed(2)}</span>
                                    </div>
                                ` : ''}
                                ${montoTarjetaNum > 0 ? `
                                    <div class="total-row">
                                        <span>Tarjeta:</span>
                                        <span>${montoTarjetaNum.toFixed(2)}</span>
                                    </div>
                                ` : ''}
                                ${montoTransferenciaNum > 0 ? `
                                    <div class="total-row">
                                        <span>Transferencia:</span>
                                        <span>${montoTransferenciaNum.toFixed(2)}</span>
                                    </div>
                                ` : ''}
                                ${cuotasNum > 0 ? `
                                    <div class="total-row">
                                        <span>Cuotas:</span>
                                        <span>${cuotasNum} cuota(s)</span>
                                    </div>
                                    ${restante > 0.01 ? `
                                        <div class="total-row">
                                            <span>Monto en cuotas:</span>
                                            <span>${restante.toFixed(2)}</span>
                                        </div>
                                    ` : ''}
                                ` : ''}
                            </div>`;
        }
    } else if (tipoPagoV === 'tarjeta' && cuotasNum > 0) {
        bloquePagoExtra = `
                    <div class="totals" style="margin-top: 2mm; padding-top: 2mm; border-top: 1px dashed #333;">
                        <div class="total-row">
                            <span>Cuotas:</span>
                            <span>${cuotasNum} cuota(s) de ${(totalFinal / cuotasNum).toFixed(2)}</span>
                        </div>
                    </div>`;
    }

    return `<!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <title>Danielles</title>
                ${TICKET_THERMAL_STYLES}
            </head>
            <body>
                <div class="header">
                    <h1>Danielles</h1>
                    <p>${venta.numero_factura ? `Nº ${venta.numero_factura} · ` : ''}Fecha: ${fechaStr}</p>
                </div>

                <div class="info-section">
                    ${bloqueCliente}
                    <div class="info-row">
                        <span class="info-label">Tipo de Pago:</span>
                        <span class="info-value">${etiquetaTipoPagoTicket(tipoPagoV)}</span>
                    </div>
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
                        ${itemsRows}
                    </tbody>
                </table>

                <div class="totals">
                    <div class="total-row">
                        <span>Subtotal:</span>
                        <span>${totalBruto.toFixed(2)}</span>
                    </div>
                    ${descuentoNum > 0 ? `
                        <div class="total-row">
                            <span>Descuento:</span>
                            <span>-${descuentoNum.toFixed(2)}</span>
                        </div>
                    ` : ''}
                    <div class="total-row total-final">
                        <span>TOTAL:</span>
                        <span>${totalFinal.toFixed(2)}</span>
                    </div>
                </div>

                ${bloquePagoExtra}

                <div class="footer">
                    <p>Danielles. Venta registrada en el sistema.</p>
                    <p>Conserve este comprobante.</p>
                </div>

                <div class="button-container no-print">
                    <button onclick="window.print()">Imprimir</button>
                    <button onclick="window.close()" style="background-color: #6c757d; margin-left: 10px;">Cerrar</button>
                </div>
            </body>
            </html>`;
}

function ventas() {
    return {
        ventas: [],
        clientes: [],
        productos: [],
        cajasAbiertas: [],
        cajaSeleccionada: '',
        loadingLista: true,
        loadingFormDatos: true,
        loadingSubmit: false,
        showModal: false,
        error: '',
        success: '',
        clienteId: '',
        tipoPago: 'efectivo',
        montoTarjeta: '',
        montoEfectivo: '',
        montoTransferencia: '',
        pagoCon: '',
        descuento: 0,
        items: [{ producto_id: '', cantidad: 1 }],
        busquedaProducto: {},
        adjuntos: [],

        authHeaders() {
            const t = localStorage.getItem('token');
            return t ? { Authorization: 'Bearer ' + t } : {};
        },
        
        async init() {
            await Promise.all([this.fetchVentas(), this.fetchDatosFormulario()]);
        },
        
        async fetchVentas() {
            try {
                this.loadingLista = true;
                const response = await axios.get('/api/ventas', {
                    headers: this.authHeaders()
                });
                const ventas = response.data?.data || response.data || [];
                this.ventas = [...ventas].sort((a, b) => {
                    const estadoA = (a?.estado || '').toLowerCase();
                    const estadoB = (b?.estado || '').toLowerCase();
                    if (estadoA === 'abierta' && estadoB !== 'abierta') return -1;
                    if (estadoA !== 'abierta' && estadoB === 'abierta') return 1;
                    return 0;
                });
            } catch (error) {
                console.error('Error:', error);
            } finally {
                this.loadingLista = false;
            }
        },
        
        async fetchDatosFormulario() {
            try {
                this.loadingFormDatos = true;
                const headers = this.authHeaders();

                const [productosRes, cajasRes] = await Promise.all([
                    axios.get('/api/productos', { params: { all: 'true' }, headers }),
                    axios.get(@json(rtrim(url('/api/cajas'), '/')), { params: { estado: 'abierta' }, headers })
                ]);

                let clientes = [];
                try {
                    const clientesRes = await axios.get('/api/clientes', { headers });
                    clientes = (clientesRes.data?.data || clientesRes.data || []).filter(c => c.activo !== false);
                } catch (e) {
                    clientes = [];
                }

                this.clientes = clientes;
                this.productos = (productosRes.data?.data || productosRes.data || []).filter(p => p.activo !== false);
                this.cajasAbiertas = (cajasRes.data?.data || cajasRes.data || []).filter(c => c.estado === 'abierta');
                if (this.cajasAbiertas.length > 0) {
                    this.cajaSeleccionada = this.cajasAbiertas[0].id;
                }
            } catch (error) {
                console.error('Error:', error);
                this.error = 'Error al cargar datos';
            } finally {
                this.loadingFormDatos = false;
            }
        },
        
        seleccionarCaja() {
            // Ya está seleccionada por el binding
        },
        
        agregarItem() {
            this.items.push({ producto_id: '', cantidad: 1 });
        },
        
        eliminarItem(index) {
            if (this.items.length > 1) {
                this.items.splice(index, 1);
            }
        },
        
        obtenerProducto(id) {
            return this.productos.find(p => String(p.id) === String(id));
        },
        
        filtrarProductos(index) {
            const busqueda = this.busquedaProducto[index] || '';
            if (!busqueda) return this.productos;
            
            const busquedaLower = busqueda.toLowerCase();
            return this.productos.filter(p => 
                (p.nombre && p.nombre.toLowerCase().includes(busquedaLower)) ||
                (p.codigo && p.codigo.toLowerCase().includes(busquedaLower)) ||
                (p.descripcion && p.descripcion.toLowerCase().includes(busquedaLower))
            );
        },
        
        calcularSubtotal(item) {
            const prod = this.obtenerProducto(item.producto_id);
            if (!prod) return 0;
            return (parseFloat(prod.precio_venta || 0) * (parseInt(item.cantidad) || 0)) || 0;
        },
        
        calcularTotal() {
            const totalBruto = this.items.reduce((acc, item) => acc + this.calcularSubtotal(item), 0);
            return totalBruto - (parseFloat(this.descuento) || 0);
        },

        async persistirVentaDesdeFormulario() {
            if (!this.cajaSeleccionada) {
                this.error = 'Debe seleccionar una caja';
                return null;
            }

            const itemsValidos = this.items
                .filter(item => item.producto_id && (parseInt(item.cantidad) || 0) > 0)
                .map(item => ({
                    producto_id: item.producto_id,
                    cantidad: parseInt(item.cantidad) || 0,
                }));

            if (itemsValidos.length === 0) {
                this.error = 'Debe agregar al menos un producto';
                return null;
            }

            if (this.tipoPago === 'cuenta_corriente' && !this.clienteId) {
                this.error = 'En cuenta corriente debe seleccionar un cliente.';
                return null;
            }

            const totalVenta = this.calcularTotal();
            if (this.tipoPago === 'mixto') {
                const mt = parseFloat(this.montoTarjeta) || 0;
                const me = parseFloat(this.montoEfectivo) || 0;
                const mtr = parseFloat(this.montoTransferencia) || 0;
                if (Math.abs(mt + me + mtr - totalVenta) > 0.01) {
                    this.error = 'La suma de efectivo, tarjeta y transferencia debe ser igual al total de la venta.';
                    return null;
                }
            }

            this.loadingSubmit = true;
            this.error = '';
            this.success = '';
            const headers = this.authHeaders();

            try {
                const payload = {
                    caja_id: this.cajaSeleccionada,
                    cliente_id: this.clienteId || null,
                    tipo_pago: this.tipoPago,
                    descuento: parseFloat(this.descuento) || 0,
                    items: itemsValidos,
                };

                if (this.tipoPago === 'mixto') {
                    payload.monto_tarjeta = parseFloat(this.montoTarjeta) || 0;
                    payload.monto_efectivo = parseFloat(this.montoEfectivo) || 0;
                    payload.monto_transferencia = parseFloat(this.montoTransferencia) || 0;
                }

                const response = await axios.post('/api/ventas', payload, {
                    headers
                });

                if (this.adjuntos && this.adjuntos.length > 0 && response.data?.id) {
                    try {
                        const formData = new FormData();
                        this.adjuntos.forEach((file) => {
                            formData.append('adjuntos[]', file);
                        });

                        await axios.post(`/api/ventas/${response.data.id}/adjuntos`, formData, {
                            headers: {
                                ...headers,
                                'Content-Type': 'multipart/form-data'
                            }
                        });
                    } catch (adjuntoError) {
                        console.error('Error al subir adjuntos:', adjuntoError);
                    }
                }

                return response.data;
            } catch (error) {
                this.error = error.response?.data?.message || 'Error al registrar la venta';
                return null;
            } finally {
                this.loadingSubmit = false;
            }
        },

        etiquetaTipoPago(tipo) {
            const m = { efectivo: 'Efectivo', tarjeta: 'Tarjeta', transferencia: 'Transferencia', cuenta_corriente: 'Cuenta Corriente', mixto: 'Mixto' };
            return m[tipo] || tipo || '-';
        },
        
        async imprimirPresupuesto() {
            const ventaGuardada = await this.persistirVentaDesdeFormulario();
            if (!ventaGuardada) {
                return;
            }

            const contenidoHTML = construirHtmlTicketDesdeVentaGuardada(ventaGuardada);

            const ventanaImpresion = window.open('', '_blank');
            if (!ventanaImpresion) {
                this.error = 'La venta ya quedó registrada, pero el navegador bloqueó la ventana para imprimir. Permita ventanas emergentes.';
                this.success = 'Venta ' + (ventaGuardada.numero_factura || ('#' + ventaGuardada.id)) + ' guardada.';
                await this.fetchVentas();
                this.closeModal();
                if (ventaGuardada.id) {
                    setTimeout(() => {
                        window.location.href = '/ventas/' + ventaGuardada.id;
                    }, 400);
                }
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

            this.success = 'Venta registrada. Se abrió el ticket para imprimir.';
            await this.fetchVentas();
            this.closeModal();
            if (ventaGuardada.id) {
                setTimeout(() => {
                    window.location.href = '/ventas/' + ventaGuardada.id;
                }, 800);
            }
        },

        openModal() {
            if (this.cajasAbiertas.length === 0) return;
            this.showModal = true;
            this.error = '';
            this.success = '';
            this.resetForm();
        },
        
        closeModal() {
            this.showModal = false;
            this.resetForm();
        },
        
        resetForm() {
            this.clienteId = '';
            this.tipoPago = 'efectivo';
            this.montoTarjeta = '';
            this.montoEfectivo = '';
            this.montoTransferencia = '';
            this.pagoCon = '';
            this.descuento = 0;
            this.items = [{ producto_id: '', cantidad: 1 }];
            this.busquedaProducto = {};
            this.adjuntos = [];
            // Limpiar el input file
            const fileInput = document.querySelector('input[type="file"]');
            if (fileInput) {
                fileInput.value = '';
            }
        },
        
        async guardarVenta() {
            const responseData = await this.persistirVentaDesdeFormulario();
            if (!responseData) {
                return;
            }

            this.success = 'Venta registrada correctamente';
            await this.fetchVentas();
            this.closeModal();

            if (responseData.id) {
                setTimeout(() => {
                    window.location.href = `/ventas/${responseData.id}`;
                }, 1000);
            }
        }
    }
}
</script>
@endpush
@endsection
