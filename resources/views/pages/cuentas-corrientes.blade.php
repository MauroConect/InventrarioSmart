@extends('layouts.app')

@section('title', 'Cuentas Corrientes - El Cristo')
@section('page-title', 'Cuentas Corrientes')

@section('content')
<div x-data="cuentasCorrientesPage({ esAdmin: @json(auth()->user()->isAdmin()) })" x-init="init()" class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Cuentas corrientes</h1>
        <button
            type="button"
            x-show="esAdmin"
            x-cloak
            @click="abrirModalNueva()"
            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm whitespace-nowrap w-full sm:w-auto"
        >
            + Nueva cuenta
        </button>
    </div>

    <div x-show="pageError" x-cloak class="p-3 bg-red-50 border border-red-200 text-red-800 rounded-md text-sm" x-text="pageError"></div>
    <div x-show="pageSuccess" x-cloak class="p-3 bg-green-50 border border-green-200 text-green-800 rounded-md text-sm" x-text="pageSuccess"></div>

    <div class="bg-white rounded-lg shadow border border-gray-100 overflow-hidden">
        <template x-if="loading">
            <div class="p-8 text-center text-gray-500">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <p class="mt-2">Cargando cuentas...</p>
            </div>
        </template>

        <template x-if="!loading">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cliente</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Deuda</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Límite</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Disponible</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr x-show="cuentas.length === 0">
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">No hay cuentas corrientes.</td>
                        </tr>
                        <template x-for="cuenta in cuentas" :key="cuenta.id">
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium text-gray-900" x-text="nombreCliente(cuenta)"></td>
                                <td class="px-4 py-3">
                                    <span
                                        class="inline-flex px-2 py-0.5 rounded text-xs font-medium"
                                        :class="cuenta.activa ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-700'"
                                        x-text="cuenta.activa ? 'Activa' : 'Inactiva'"
                                    ></span>
                                </td>
                                <td class="px-4 py-3 text-right font-semibold">
                                    <span :class="claseSaldo(cuenta.saldo)" x-text="'$' + parseFloat(cuenta.saldo || 0).toFixed(2)"></span>
                                </td>
                                <td class="px-4 py-3 text-right text-gray-700" x-text="'$' + parseFloat(cuenta.limite_credito || 0).toFixed(2)"></td>
                                <td class="px-4 py-3 text-right text-gray-700" x-text="textoDisponible(cuenta)"></td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-2">
                                        <a :href="'/cuentas-corrientes/' + cuenta.id" class="text-blue-600 hover:text-blue-800 whitespace-nowrap">Ver cuenta</a>
                                        <template x-if="esAdmin && cuenta.activa">
                                            <button type="button" @click="abrirModalMov(cuenta, 'haber')" class="text-green-700 hover:text-green-900 whitespace-nowrap text-left">Pago</button>
                                        </template>
                                        <template x-if="esAdmin && cuenta.activa">
                                            <button type="button" @click="abrirModalMov(cuenta, 'debe')" class="text-amber-700 hover:text-amber-900 whitespace-nowrap text-left">Cargo</button>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </template>
    </div>

    <div x-show="showModalMov" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" @click.self="showModalMov = false">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto" @click.stop>
            <div class="border-b px-4 py-3 flex justify-between items-center">
                <h3 class="text-lg font-semibold">Movimiento en cuenta</h3>
                <button type="button" @click="showModalMov = false" class="text-gray-500 hover:text-gray-800 text-xl leading-none">×</button>
            </div>
            <form @submit.prevent="guardarMovimiento()" class="p-4 space-y-4">
                <p class="text-sm text-gray-600" x-text="cuentaMov ? nombreCliente(cuentaMov) : ''"></p>
                <div x-show="modalMovError" x-cloak class="text-sm text-red-700 bg-red-50 border border-red-200 rounded px-3 py-2" x-text="modalMovError"></div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
                    <select x-model="movForm.tipo" class="w-full border border-gray-300 rounded-md px-3 py-2">
                        <option value="haber">Pago / ingreso (reduce la deuda)</option>
                        <option value="debe">Cargo al debe (aumenta la deuda)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Monto *</label>
                    <input type="number" step="0.01" min="0.01" x-model="movForm.monto" class="w-full border border-gray-300 rounded-md px-3 py-2" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Concepto *</label>
                    <input type="text" x-model="movForm.concepto" maxlength="255" class="w-full border border-gray-300 rounded-md px-3 py-2" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                    <textarea rows="2" x-model="movForm.observaciones" class="w-full border border-gray-300 rounded-md px-3 py-2"></textarea>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="showModalMov = false" class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50">Cancelar</button>
                    <button type="submit" :disabled="modalMovSaving" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50" x-text="modalMovSaving ? 'Guardando…' : 'Registrar'"></button>
                </div>
            </form>
        </div>
    </div>

    <div x-show="showModalNueva" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" @click.self="showModalNueva = false">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md" @click.stop>
            <div class="border-b px-4 py-3 flex justify-between items-center">
                <h3 class="text-lg font-semibold">Nueva cuenta corriente</h3>
                <button type="button" @click="showModalNueva = false" class="text-gray-500 hover:text-gray-800 text-xl">×</button>
            </div>
            <form @submit.prevent="crearCuenta()" class="p-4 space-y-4">
                <div x-show="modalNuevaError" x-cloak class="text-sm text-red-700 bg-red-50 border border-red-200 rounded px-3 py-2" x-text="modalNuevaError"></div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cliente *</label>
                    <select x-model="nueva.cliente_id" class="w-full border border-gray-300 rounded-md px-3 py-2" required>
                        <option value="">Seleccionar…</option>
                        <template x-for="c in clientesSinCuenta" :key="c.id">
                            <option :value="c.id" x-text="(c.nombre || '') + ' ' + (c.apellido || '') + (c.dni ? ' — DNI ' + c.dni : '')"></option>
                        </template>
                    </select>
                    <p class="text-xs text-amber-700 mt-1" x-show="clientesSinCuenta.length === 0 && esAdmin">No hay clientes sin cuenta corriente.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Límite de crédito *</label>
                    <input type="number" step="0.01" min="0" x-model="nueva.limite_credito" placeholder="0 = sin tope" class="w-full border border-gray-300 rounded-md px-3 py-2" required>
                    <p class="text-xs text-gray-500 mt-1">0 permite comprar en cuenta sin tope (según reglas de venta).</p>
                </div>
                <div class="flex items-center gap-2">
                    <input id="nv-activa" type="checkbox" x-model="nueva.activa">
                    <label for="nv-activa" class="text-sm text-gray-700">Cuenta activa</label>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="showModalNueva = false" class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50">Cancelar</button>
                    <button type="submit" :disabled="nuevaSaving || clientesSinCuenta.length === 0" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50" x-text="nuevaSaving ? 'Creando…' : 'Crear cuenta'"></button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
const CC_API = @json(rtrim(url('/api/cuentas-corrientes'), '/'));

function cuentasCorrientesPage(opts) {
    return {
        esAdmin: !!opts.esAdmin,
        cuentas: [],
        loading: true,
        pageError: '',
        pageSuccess: '',

        showModalMov: false,
        cuentaMov: null,
        modalMovSaving: false,
        modalMovError: '',
        movForm: { tipo: 'haber', monto: '', concepto: '', observaciones: '' },

        showModalNueva: false,
        clientesSinCuenta: [],
        nueva: { cliente_id: '', limite_credito: '', activa: true },
        nuevaSaving: false,
        modalNuevaError: '',

        headers() {
            const token = localStorage.getItem('token');
            return token ? { Authorization: 'Bearer ' + token } : {};
        },

        async init() {
            await this.fetch();
        },

        nombreCliente(cuenta) {
            if (!cuenta || !cuenta.cliente) return '—';
            return (cuenta.cliente.nombre || '') + ' ' + (cuenta.cliente.apellido || '');
        },

        claseSaldo(s) {
            const n = parseFloat(s) || 0;
            if (n > 0) return 'text-amber-800';
            if (n < 0) return 'text-blue-700';
            return 'text-green-700';
        },

        textoDisponible(cuenta) {
            const lim = parseFloat(cuenta.limite_credito) || 0;
            const sal = parseFloat(cuenta.saldo) || 0;
            if (lim <= 0) return '—';
            return '$' + Math.max(0, lim - sal).toFixed(2);
        },

        async fetch() {
            try {
                this.loading = true;
                this.pageError = '';
                const response = await axios.get(CC_API, {
                    params: { per_page: 100 },
                    headers: this.headers()
                });
                this.cuentas = response.data?.data || response.data || [];
            } catch (e) {
                console.error(e);
                this.pageError = 'No se pudieron cargar las cuentas. ¿Sesión de administrador y API disponible?';
                this.cuentas = [];
            } finally {
                this.loading = false;
            }
        },

        abrirModalMov(cuenta, tipo) {
            this.cuentaMov = cuenta;
            this.modalMovError = '';
            this.movForm = {
                tipo: tipo,
                monto: '',
                concepto: tipo === 'haber' ? 'Pago recibido' : 'Cargo / ajuste',
                observaciones: ''
            };
            this.showModalMov = true;
        },

        async guardarMovimiento() {
            this.modalMovError = '';
            const m = parseFloat(this.movForm.monto);
            if (!m || m < 0.01) {
                this.modalMovError = 'Ingrese un monto válido.';
                return;
            }
            if (!String(this.movForm.concepto || '').trim()) {
                this.modalMovError = 'El concepto es obligatorio.';
                return;
            }
            try {
                this.modalMovSaving = true;
                await axios.post(
                    CC_API + '/' + this.cuentaMov.id + '/movimiento',
                    {
                        tipo: this.movForm.tipo,
                        monto: m,
                        concepto: String(this.movForm.concepto).trim(),
                        observaciones: String(this.movForm.observaciones || '').trim() || null
                    },
                    { headers: this.headers() }
                );
                this.showModalMov = false;
                this.pageSuccess = 'Movimiento registrado.';
                setTimeout(() => { this.pageSuccess = ''; }, 3500);
                await this.fetch();
            } catch (e) {
                const msg = e.response?.data?.message;
                this.modalMovError = typeof msg === 'string' ? msg : 'No se pudo registrar.';
            } finally {
                this.modalMovSaving = false;
            }
        },

        async abrirModalNueva() {
            this.modalNuevaError = '';
            this.nueva = { cliente_id: '', limite_credito: '', activa: true };
            try {
                const [cuentasRes, clientesRes] = await Promise.all([
                    axios.get(CC_API, { params: { per_page: 100 }, headers: this.headers() }),
                    axios.get(@json(rtrim(url('/api/clientes'), '/')), { params: { all: 'true' }, headers: this.headers() })
                ]);
                const listC = cuentasRes.data?.data || cuentasRes.data || [];
                const conCuenta = new Set(listC.map((c) => c.cliente_id));
                const clientes = clientesRes.data || [];
                this.clientesSinCuenta = clientes.filter((c) => !conCuenta.has(c.id) && c.activo !== false);
                this.showModalNueva = true;
            } catch (e) {
                console.error(e);
                this.pageError = 'No se pudo cargar clientes para el alta.';
            }
        },

        async crearCuenta() {
            this.modalNuevaError = '';
            if (!this.nueva.cliente_id) {
                this.modalNuevaError = 'Seleccioná un cliente.';
                return;
            }
            const lim = parseFloat(this.nueva.limite_credito);
            if (!Number.isFinite(lim) || lim < 0) {
                this.modalNuevaError = 'Límite inválido.';
                return;
            }
            try {
                this.nuevaSaving = true;
                await axios.post(
                    CC_API,
                    {
                        cliente_id: parseInt(this.nueva.cliente_id, 10),
                        limite_credito: lim,
                        activa: !!this.nueva.activa
                    },
                    { headers: this.headers() }
                );
                this.showModalNueva = false;
                this.pageSuccess = 'Cuenta creada correctamente.';
                setTimeout(() => { this.pageSuccess = ''; }, 4000);
                await this.fetch();
            } catch (e) {
                const msg = e.response?.data?.message;
                this.modalNuevaError = typeof msg === 'string' ? msg : 'No se pudo crear la cuenta.';
            } finally {
                this.nuevaSaving = false;
            }
        }
    };
}
</script>
@endpush
@endsection
