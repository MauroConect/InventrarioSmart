@extends('layouts.app')

@section('title', 'Cuenta corriente - El Cristo')
@section('page-title', 'Cuenta corriente')

@section('content')
<div
    x-data="cuentaCorrienteDetalle({ id: {{ (int) $cuentaId }}, esAdmin: @json(auth()->user()->isAdmin()) })"
    x-init="init()"
    class="space-y-6 max-w-5xl"
>
    <div>
        <a href="{{ route('cuentas-corrientes.index') }}" class="text-sm text-blue-600 hover:underline">← Cuentas corrientes</a>
    </div>

    <template x-if="loading && !cuenta">
        <div class="p-8 text-center text-gray-500">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <p class="mt-2">Cargando…</p>
        </div>
    </template>

    <template x-if="error && !cuenta">
        <div class="p-4 bg-red-50 border border-red-200 text-red-800 rounded" x-text="error"></div>
    </template>

    <template x-if="cuenta">
        <div class="space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Cuenta corriente</h1>
                    <p class="text-lg text-gray-700 mt-1" x-text="nombreCliente()"></p>
                    <p class="text-sm text-gray-500 mt-1">
                        Estado:
                        <span :class="cuenta.activa ? 'text-green-700 font-medium' : 'text-red-600 font-medium'" x-text="cuenta.activa ? 'Activa' : 'Inactiva'"></span>
                    </p>
                </div>
                <div class="flex flex-wrap gap-2" x-show="esAdmin && cuenta.activa" x-cloak>
                    <button type="button" @click="abrirModalMov('haber')" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 text-sm">Registrar pago</button>
                    <button type="button" @click="abrirModalMov('debe')" class="px-4 py-2 bg-amber-600 text-white rounded-md hover:bg-amber-700 text-sm">Cargo / ajuste</button>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white rounded-lg border border-gray-200 p-4 shadow-sm">
                    <p class="text-xs font-medium text-gray-500 uppercase">Deuda actual</p>
                    <p class="text-2xl font-bold mt-1" :class="claseSaldo(cuenta.saldo)" x-text="'$' + parseFloat(cuenta.saldo || 0).toFixed(2)"></p>
                    <p class="text-sm text-gray-600 mt-1" x-text="etiquetaSaldo(cuenta.saldo)"></p>
                </div>
                <div class="bg-white rounded-lg border border-gray-200 p-4 shadow-sm">
                    <p class="text-xs font-medium text-gray-500 uppercase">Límite de crédito</p>
                    <p class="text-2xl font-bold mt-1 text-gray-900" x-text="'$' + parseFloat(cuenta.limite_credito || 0).toFixed(2)"></p>
                </div>
                <div class="bg-white rounded-lg border border-gray-200 p-4 shadow-sm">
                    <p class="text-xs font-medium text-gray-500 uppercase">Crédito disponible</p>
                    <p class="text-2xl font-bold mt-1 text-gray-900" x-text="textoDisponible()"></p>
                    <p class="text-sm text-gray-600 mt-1" x-text="subtextoDisponible()"></p>
                </div>
            </div>

            <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
                    <h2 class="font-semibold text-gray-800">Movimientos</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Concepto</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Monto</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase hidden md:table-cell">Venta</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr x-show="!cuenta.movimientos || cuenta.movimientos.length === 0">
                                <td colspan="5" class="px-4 py-8 text-center text-gray-500">No hay movimientos.</td>
                            </tr>
                            <template x-for="m in (cuenta.movimientos || [])" :key="m.id">
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-2 whitespace-nowrap text-gray-600" x-text="fmtFecha(m.created_at)"></td>
                                    <td class="px-4 py-2">
                                        <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium" :class="m.tipo === 'debe' ? 'bg-amber-100 text-amber-900' : 'bg-green-100 text-green-900'" x-text="m.tipo === 'debe' ? 'Debe' : 'Haber (pago)'"></span>
                                    </td>
                                    <td class="px-4 py-2 text-gray-800">
                                        <span x-text="m.concepto"></span>
                                        <span class="block text-xs text-gray-500 mt-0.5" x-show="m.observaciones" x-text="m.observaciones"></span>
                                    </td>
                                    <td class="px-4 py-2 text-right font-medium" x-text="'$' + parseFloat(m.monto || 0).toFixed(2)"></td>
                                    <td class="px-4 py-2 text-gray-600 hidden md:table-cell" x-text="m.venta?.numero_factura || (m.venta_id ? '#' + m.venta_id : '—')"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </template>

    <div x-show="showModalMov" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" @click.self="showModalMov = false">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto" @click.stop>
            <div class="border-b px-4 py-3 flex justify-between items-center">
                <h3 class="text-lg font-semibold">Movimiento en cuenta</h3>
                <button type="button" @click="showModalMov = false" class="text-gray-500 hover:text-gray-800 text-xl leading-none">×</button>
            </div>
            <form @submit.prevent="guardarMovimiento()" class="p-4 space-y-4">
                <p class="text-sm text-gray-600" x-show="cuenta" x-text="nombreCliente()"></p>
                <div x-show="modalError" x-cloak class="text-sm text-red-700 bg-red-50 border border-red-200 rounded px-3 py-2" x-text="modalError"></div>
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
                    <button type="submit" :disabled="modalSaving" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50" x-text="modalSaving ? 'Guardando…' : 'Registrar'"></button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function cuentaCorrienteDetalle(opts) {
    const API = @json(rtrim(url('/api/cuentas-corrientes'), '/'));
    return {
        cuentaId: opts.id,
        esAdmin: !!opts.esAdmin,
        cuenta: null,
        loading: true,
        error: '',
        showModalMov: false,
        modalSaving: false,
        modalError: '',
        movForm: { tipo: 'haber', monto: '', concepto: '', observaciones: '' },

        async init() {
            await this.fetchCuenta();
        },

        headers() {
            const token = localStorage.getItem('token');
            return token ? { Authorization: 'Bearer ' + token } : {};
        },

        async fetchCuenta() {
            try {
                this.loading = true;
                this.error = '';
                const r = await axios.get(API + '/' + this.cuentaId, { headers: this.headers() });
                this.cuenta = r.data;
            } catch (e) {
                console.error(e);
                this.error = 'No se pudo cargar la cuenta corriente.';
                this.cuenta = null;
            } finally {
                this.loading = false;
            }
        },

        nombreCliente() {
            if (!this.cuenta || !this.cuenta.cliente) return '—';
            return (this.cuenta.cliente.nombre || '') + ' ' + (this.cuenta.cliente.apellido || '');
        },

        claseSaldo(s) {
            const n = parseFloat(s) || 0;
            if (n > 0) return 'text-amber-800';
            if (n < 0) return 'text-blue-700';
            return 'text-green-700';
        },

        etiquetaSaldo(s) {
            const n = parseFloat(s) || 0;
            if (n > 0) return 'Debe';
            if (n < 0) return 'A favor';
            return 'Al día';
        },

        creditoDispNum() {
            if (!this.cuenta) return null;
            const lim = parseFloat(this.cuenta.limite_credito) || 0;
            const sal = parseFloat(this.cuenta.saldo) || 0;
            if (lim <= 0) return null;
            return Math.max(0, lim - sal);
        },

        textoDisponible() {
            const d = this.creditoDispNum();
            return d === null ? '—' : '$' + d.toFixed(2);
        },

        subtextoDisponible() {
            const lim = this.cuenta ? parseFloat(this.cuenta.limite_credito) || 0 : 0;
            return lim <= 0 ? 'Sin límite configurado' : 'Para nuevas compras en cuenta';
        },

        fmtFecha(iso) {
            if (!iso) return '—';
            return new Date(iso).toLocaleString('es-AR');
        },

        abrirModalMov(tipo) {
            this.modalError = '';
            this.movForm = {
                tipo: tipo,
                monto: '',
                concepto: tipo === 'haber' ? 'Pago recibido' : 'Cargo / ajuste',
                observaciones: ''
            };
            this.showModalMov = true;
        },

        async guardarMovimiento() {
            this.modalError = '';
            const m = parseFloat(this.movForm.monto);
            if (!m || m < 0.01) {
                this.modalError = 'Ingrese un monto válido.';
                return;
            }
            if (!String(this.movForm.concepto || '').trim()) {
                this.modalError = 'El concepto es obligatorio.';
                return;
            }
            try {
                this.modalSaving = true;
                await axios.post(
                    API + '/' + this.cuentaId + '/movimiento',
                    {
                        tipo: this.movForm.tipo,
                        monto: m,
                        concepto: String(this.movForm.concepto).trim(),
                        observaciones: String(this.movForm.observaciones || '').trim() || null
                    },
                    { headers: this.headers() }
                );
                this.showModalMov = false;
                await this.fetchCuenta();
            } catch (e) {
                const msg = e.response?.data?.message;
                this.modalError = typeof msg === 'string' ? msg : 'No se pudo registrar el movimiento.';
            } finally {
                this.modalSaving = false;
            }
        }
    };
}
</script>
@endpush
@endsection
