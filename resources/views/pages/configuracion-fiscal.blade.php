@extends('layouts.app')

@section('title', 'Configuracion Fiscal - El Cristo')
@section('page-title', 'Configuracion Fiscal')

@section('content')
<div x-data="configuracionFiscal()" x-init="init()" class="space-y-6 max-w-4xl">
    <div>
        <h1 class="text-2xl font-bold">Configuracion Fiscal AFIP/ARCA</h1>
        <p class="text-sm text-gray-600 mt-1">
            Parametros editables para facturacion electronica por comercio.
        </p>
    </div>

    <div x-show="form.ambiente === 'produccion'" x-cloak class="p-4 bg-amber-50 border border-amber-300 text-amber-900 rounded text-sm">
        <strong>Produccion:</strong> los comprobantes tienen validez fiscal. Verifique CUIT, punto de venta y certificados antes de emitir.
    </div>
    <div class="p-4 bg-blue-50 border border-blue-200 text-blue-900 rounded text-sm">
        <strong>Homologacion:</strong> use ambiente de pruebas AFIP hasta validar todo el flujo. Factura A requiere cliente con CUIT de 11 digitos valido.
    </div>

    <div x-show="error" x-cloak class="p-4 bg-red-100 border border-red-400 text-red-700 rounded" x-text="error"></div>
    <div x-show="success" x-cloak class="p-4 bg-green-100 border border-green-400 text-green-700 rounded" x-text="success"></div>

    <form @submit.prevent="guardar()" class="bg-white p-6 rounded-lg shadow space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Razon Social</label>
                <input type="text" x-model="form.razon_social" class="w-full px-3 py-2 border border-gray-300 rounded-md">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">CUIT Emisor</label>
                <input type="text" x-model="form.cuit_emisor" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Ej: 20123456789">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Condicion IVA</label>
                <select x-model="form.condicion_iva" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    <option value="responsable_inscripto">Responsable Inscripto</option>
                    <option value="monotributo">Monotributo</option>
                    <option value="exento">Exento</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Punto de Venta</label>
                <input type="number" x-model.number="form.punto_venta" min="1" max="99999" class="w-full px-3 py-2 border border-gray-300 rounded-md">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Ambiente</label>
                <select x-model="form.ambiente" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    <option value="homologacion">Homologacion</option>
                    <option value="produccion">Produccion</option>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Comprobante por defecto</label>
                <select x-model="form.comprobante_tipo_default" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    <option value="A">Factura A</option>
                    <option value="B">Factura B</option>
                    <option value="C">Factura C</option>
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Path Certificado (CRT/PEM)</label>
                <input type="text" x-model="form.certificado_path" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="/var/www/storage/certs/certificado.crt">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Path Clave Privada (KEY)</label>
                <input type="text" x-model="form.clave_privada_path" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="/var/www/storage/certs/clave.key">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Passphrase Certificado (opcional)</label>
                <input type="password" x-model="form.passphrase_certificado" class="w-full px-3 py-2 border border-gray-300 rounded-md" autocomplete="off" placeholder="Dejar vacio para no cambiar">
                <p class="text-xs text-gray-500 mt-1" x-show="form.has_passphrase">Ya hay una passphrase guardada. Solo complete si desea reemplazarla.</p>
            </div>
        </div>

        <div class="pt-4 border-t">
            <button type="submit" :disabled="loading" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50">
                <span x-show="!loading">Guardar configuracion</span>
                <span x-show="loading" x-cloak>Guardando...</span>
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
function configuracionFiscal() {
    return {
        loading: false,
        error: '',
        success: '',
        form: {
            razon_social: '',
            cuit_emisor: '',
            condicion_iva: 'monotributo',
            punto_venta: '',
            ambiente: 'homologacion',
            comprobante_tipo_default: 'B',
            certificado_path: '',
            clave_privada_path: '',
            passphrase_certificado: '',
            has_passphrase: false,
        },
        async init() {
            await this.fetch();
        },
        async fetch() {
            try {
                const token = localStorage.getItem('token');
                const response = await axios.get('/api/configuracion-fiscal', {
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                this.form = { ...this.form, ...(response.data || {}), passphrase_certificado: '' };
            } catch (error) {
                this.error = error.response?.data?.message || 'No se pudo cargar la configuracion fiscal.';
            }
        },
        async guardar() {
            try {
                this.loading = true;
                this.error = '';
                this.success = '';
                const token = localStorage.getItem('token');
                const response = await axios.post('/api/configuracion-fiscal', this.form, {
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                this.success = response.data?.message || 'Configuracion guardada.';
                if (response.data?.data) {
                    this.form = { ...this.form, ...response.data.data, passphrase_certificado: '' };
                }
            } catch (error) {
                this.error = error.response?.data?.message || 'No se pudo guardar la configuracion.';
            } finally {
                this.loading = false;
            }
        }
    };
}
</script>
@endpush
@endsection
