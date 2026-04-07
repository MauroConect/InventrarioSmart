@extends('layouts.app')

@section('title', 'Punto de caja - Danielles')
@section('page-title', 'Punto de caja')

@section('content')
<div x-data="initPuntoCajaPage()" x-init="init()" class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold">Punto de caja</h1>
        <button type="button" @click="openModal()" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
            Abrir caja
        </button>
    </div>

    <p class="text-sm text-gray-600">Operación de caja sin pasar por la API ni permisos extra: solo tu sesión.</p>

    <div x-show="error" x-cloak class="p-4 bg-red-100 border border-red-400 text-red-700 rounded" x-text="error"></div>
    <div x-show="success" x-cloak class="p-4 bg-green-100 border border-green-400 text-green-700 rounded" x-text="success"></div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <template x-if="loading">
            <div class="p-8 text-center text-gray-500">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <p class="mt-2">Cargando cajas...</p>
            </div>
        </template>

        <template x-if="!loading">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Apertura</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Monto</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="caja in listaCajas" :key="caja.id">
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900" x-text="caja.id"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="new Date(caja.fecha_apertura).toLocaleString()"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="'$' + parseFloat(caja.monto_apertura || 0).toFixed(2)"></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs rounded-full"
                                          :class="caja.estado === 'abierta' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'"
                                          x-text="caja.estado === 'abierta' ? 'Abierta' : 'Cerrada'"></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button type="button" x-show="caja.estado === 'abierta'" @click="openCerrarModal(caja)" class="text-orange-600 hover:text-orange-900">Cerrar</button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </template>
    </div>

    <div x-show="showModal" x-cloak class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50 p-4" @click.away="closeModal()">
        <div class="bg-white rounded-lg w-full max-w-md" @click.stop>
            <div class="px-6 py-4 border-b">
                <h3 class="text-lg font-bold">Abrir caja</h3>
            </div>
            <form @submit.prevent="abrirCaja()" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Monto de apertura *</label>
                    <input type="number" step="0.01" x-model.number="montoApertura" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre (opcional)</label>
                    <input type="text" x-model="nombreCaja" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                </div>
                <div class="flex justify-end gap-2 pt-4 border-t">
                    <button type="button" @click="closeModal()" class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50">Cancelar</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Abrir</button>
                </div>
            </form>
        </div>
    </div>

    <div x-show="showCerrarModal" x-cloak class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50 p-4" @click.away="closeCerrarModal()">
        <div class="bg-white rounded-lg w-full max-w-2xl max-h-[90vh] overflow-y-auto" @click.stop>
            <div class="px-6 py-4 border-b">
                <h3 class="text-lg font-bold">Cerrar caja #<span x-text="cajaSeleccionada?.id"></span></h3>
            </div>
            <div class="p-6 space-y-4">
                <template x-if="cargandoResumen">
                    <div class="text-center py-8">
                        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                        <p class="mt-2 text-gray-500">Cargando resumen...</p>
                    </div>
                </template>
                <template x-if="!cargandoResumen && resumenCierre">
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-gray-50 p-4 rounded">
                                <p class="text-sm text-gray-600">Monto esperado</p>
                                <p class="text-xl font-bold" x-text="'$' + parseFloat(resumenCierre.resumen.monto_esperado || 0).toFixed(2)"></p>
                            </div>
                            <div class="bg-gray-50 p-4 rounded">
                                <p class="text-sm text-gray-600">Monto apertura</p>
                                <p class="text-xl font-bold" x-text="'$' + parseFloat(resumenCierre.resumen.monto_apertura || 0).toFixed(2)"></p>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Monto real *</label>
                            <input type="number" step="0.01" x-model.number="montoReal" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                            <textarea x-model="observaciones" class="w-full px-3 py-2 border border-gray-300 rounded-md" rows="3"></textarea>
                        </div>
                        <div class="flex flex-wrap justify-end gap-2 pt-4 border-t">
                            <button type="button" @click="imprimirTicketBorrador()" :disabled="cerrando" class="px-4 py-2 border border-gray-400 rounded-md hover:bg-gray-100 disabled:opacity-50">Imprimir ticket</button>
                            <button type="button" @click="closeCerrarModal()" class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50">Cancelar</button>
                            <button type="button" @click="cerrarCaja()" :disabled="cerrando" class="px-4 py-2 bg-orange-600 text-white rounded-md hover:bg-orange-700 disabled:opacity-50">
                                <span x-show="!cerrando">Cerrar caja</span>
                                <span x-show="cerrando" x-cloak>Cerrando...</span>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="{{ asset('js/ticket-cierre-caja.js') }}"></script>
<script>
(function () {
    const BASE = @json(rtrim(url('/api/cajas'), '/'));
    const LISTADO = BASE;
    const APERTURA = BASE;
    const resumenUrl = (id) => BASE + '/' + encodeURIComponent(id) + '/resumen-cierre';
    const cerrarUrl = (id) => BASE + '/' + encodeURIComponent(id) + '/cerrar';

    function initPuntoCajaPage() {
        return {
            listaCajas: [],
            loading: true,
            showModal: false,
            showCerrarModal: false,
            cajaSeleccionada: null,
            montoApertura: 0,
            nombreCaja: '',
            resumenCierre: null,
            cargandoResumen: false,
            montoReal: '',
            observaciones: '',
            error: '',
            success: '',
            cerrando: false,

            async init() {
                await this.fetch();
            },

            async fetch() {
                try {
                    this.loading = true;
                    const response = await axios.get(LISTADO);
                    this.listaCajas = response.data?.data || response.data || [];
                } catch (error) {
                    const s = error.response?.status;
                    this.error = s === 401
                        ? 'Sesión no válida. Volvé a iniciar sesión.'
                        : (s === 419 ? 'Recargá la página (seguridad) e intentá de nuevo.' : (error.response?.data?.message || 'Error al cargar las cajas'));
                } finally {
                    this.loading = false;
                }
            },

            openModal() {
                this.showModal = true;
                this.montoApertura = 0;
                this.nombreCaja = '';
                this.error = '';
            },
            closeModal() {
                this.showModal = false;
            },

            async openCerrarModal(caja) {
                this.cajaSeleccionada = caja;
                this.showCerrarModal = true;
                this.error = '';
                this.success = '';
                await this.fetchResumenCierre(caja.id);
            },

            async fetchResumenCierre(cajaId) {
                try {
                    this.cargandoResumen = true;
                    const response = await axios.get(resumenUrl(cajaId));
                    this.resumenCierre = response.data;
                    this.montoReal = parseFloat(response.data.resumen.monto_esperado).toFixed(2);
                } catch (error) {
                    this.error = error.response?.data?.message || 'Error al cargar el resumen';
                    this.showCerrarModal = false;
                } finally {
                    this.cargandoResumen = false;
                }
            },

            closeCerrarModal() {
                this.showCerrarModal = false;
                this.cajaSeleccionada = null;
                this.resumenCierre = null;
                this.montoReal = '';
                this.observaciones = '';
            },

            async abrirCaja() {
                try {
                    this.error = '';
                    this.success = '';
                    await axios.post(APERTURA, {
                        nombre: this.nombreCaja || null,
                        monto_apertura: this.montoApertura
                    });
                    this.success = 'Caja abierta correctamente';
                    await this.fetch();
                    this.closeModal();
                    setTimeout(() => { this.success = ''; }, 3000);
                } catch (error) {
                    const s = error.response?.status;
                    this.error = s === 419
                        ? 'Recargá la página e intentá otra vez.'
                        : (error.response?.data?.message || 'Error al abrir caja');
                }
            },

            imprimirTicketBorrador() {
                if (!this.resumenCierre) return;
                if (this.montoReal === '' || parseFloat(this.montoReal) < 0) {
                    this.error = 'Ingresá un monto real válido para el ticket';
                    return;
                }
                if (typeof window.imprimirTicketCierreCaja !== 'function' || typeof window.construirPayloadTicketCierreCaja !== 'function') {
                    this.error = 'Falta el script de ticket (public/js/ticket-cierre-caja.js). Recargá la página o desplegá ese archivo.';
                    return;
                }
                this.error = '';
                const payload = window.construirPayloadTicketCierreCaja({
                    resumenCierre: this.resumenCierre,
                    montoReal: parseFloat(this.montoReal),
                    observaciones: this.observaciones,
                    cajaCerrada: null,
                });
                if (!window.imprimirTicketCierreCaja(payload)) {
                    this.error = 'Permití ventanas emergentes para imprimir.';
                }
            },

            async cerrarCaja() {
                if (this.montoReal === '' || parseFloat(this.montoReal) < 0) {
                    this.error = 'Ingresá un monto real válido';
                    return;
                }
                try {
                    this.cerrando = true;
                    this.error = '';
                    const snapshot = this.resumenCierre;
                    const r = await axios.post(cerrarUrl(this.cajaSeleccionada.id), {
                        monto_real: parseFloat(this.montoReal),
                        observaciones: this.observaciones
                    });
                    if (snapshot && typeof window.imprimirTicketCierreCaja === 'function' && typeof window.construirPayloadTicketCierreCaja === 'function') {
                        const payload = window.construirPayloadTicketCierreCaja({
                            resumenCierre: snapshot,
                            montoReal: parseFloat(this.montoReal),
                            observaciones: this.observaciones,
                            cajaCerrada: r.data,
                        });
                        setTimeout(() => window.imprimirTicketCierreCaja(payload), 200);
                    }
                    this.success = 'Caja cerrada correctamente';
                    await this.fetch();
                    this.closeCerrarModal();
                    setTimeout(() => { this.success = ''; }, 3000);
                } catch (error) {
                    this.error = error.response?.data?.message || 'Error al cerrar caja';
                } finally {
                    this.cerrando = false;
                }
            }
        };
    }
    window.initPuntoCajaPage = initPuntoCajaPage;
})();
</script>
@endpush
@endsection
