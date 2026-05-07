@extends('layouts.app')

@section('title', 'Configuracion del Negocio - Danielles')
@section('page-title', 'Configuracion del Negocio')

@section('content')
<div x-data="configuracionNegocio()" x-init="init()" class="space-y-6 max-w-4xl">
    <div>
        <h1 class="text-2xl font-bold">Personalizacion del negocio</h1>
        <p class="text-sm text-gray-600 mt-1">
            Configura nombre, logo y estilo para la experiencia del cliente final.
        </p>
    </div>

    <div x-show="error" x-cloak class="p-4 bg-red-100 border border-red-400 text-red-700 rounded" x-text="error"></div>
    <div x-show="success" x-cloak class="p-4 bg-green-100 border border-green-400 text-green-700 rounded" x-text="success"></div>

    <form @submit.prevent="guardar()" class="bg-white p-6 rounded-lg shadow space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del negocio</label>
                <input type="text" x-model="form.nombre_negocio" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Slogan</label>
                <input type="text" x-model="form.slogan" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Ej: Sabores unicos para cada momento">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">URL del logo</label>
            <input type="url" x-model="form.logo_url" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="https://.../logo.png">
            <p class="text-xs text-gray-500 mt-1">Sube el logo donde prefieras (CDN/hosting) y pega la URL pública.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Color principal</label>
                <div class="flex items-center gap-2">
                    <input type="color" x-model="form.color_primario" class="h-10 w-14 border border-gray-300 rounded-md bg-white p-1">
                    <input type="text" x-model="form.color_primario" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="#ea580c">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Descripcion corta</label>
                <input type="text" x-model="form.descripcion_corta" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Texto visible en el menu QR">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Color fondo</label>
                <div class="flex items-center gap-2">
                    <input type="color" x-model="form.color_fondo" class="h-10 w-14 border border-gray-300 rounded-md bg-white p-1">
                    <input type="text" x-model="form.color_fondo" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="#fff7ed">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Color texto</label>
                <div class="flex items-center gap-2">
                    <input type="color" x-model="form.color_texto" class="h-10 w-14 border border-gray-300 rounded-md bg-white p-1">
                    <input type="text" x-model="form.color_texto" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="#111827">
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Color header</label>
                <div class="flex items-center gap-2">
                    <input type="color" x-model="form.color_header" class="h-10 w-14 border border-gray-300 rounded-md bg-white p-1">
                    <input type="text" x-model="form.color_header" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="#ffffff">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Color tarjetas</label>
                <div class="flex items-center gap-2">
                    <input type="color" x-model="form.color_tarjeta" class="h-10 w-14 border border-gray-300 rounded-md bg-white p-1">
                    <input type="text" x-model="form.color_tarjeta" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="#ffffff">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Color chips</label>
                <div class="flex items-center gap-2">
                    <input type="color" x-model="form.color_chip" class="h-10 w-14 border border-gray-300 rounded-md bg-white p-1">
                    <input type="text" x-model="form.color_chip" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="#ea580c">
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Color precio</label>
                <div class="flex items-center gap-2">
                    <input type="color" x-model="form.color_precio" class="h-10 w-14 border border-gray-300 rounded-md bg-white p-1">
                    <input type="text" x-model="form.color_precio" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="#ea580c">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Color nombre categoría</label>
                <div class="flex items-center gap-2">
                    <input type="color" x-model="form.color_categoria" class="h-10 w-14 border border-gray-300 rounded-md bg-white p-1">
                    <input type="text" x-model="form.color_categoria" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="#f97316">
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Moneda del menu</label>
                <select x-model="form.moneda" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    <option value="ARS">Peso argentino (ARS)</option>
                    <option value="USD">Dolar (USD)</option>
                    <option value="EUR">Euro (EUR)</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">WhatsApp</label>
                <input type="text" x-model="form.telefono_whatsapp" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="+54911...">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Instagram URL</label>
                <input type="url" x-model="form.instagram_url" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="https://instagram.com/tuusuario">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Direccion</label>
                <input type="text" x-model="form.direccion" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Calle 123, Ciudad">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Horario de atencion</label>
                <input type="text" x-model="form.horario_atencion" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Lun a Dom 08:00 a 23:00">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Mensaje de bienvenida</label>
                <input type="text" x-model="form.mensaje_bienvenida" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Ej: Te recomendamos nuestros especiales">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <label class="flex items-center gap-2 rounded border border-gray-200 px-3 py-2">
                <input type="checkbox" x-model="form.mostrar_precios" class="rounded border-gray-300">
                <span class="text-sm text-gray-700">Mostrar precios en el menu publico</span>
            </label>
            <label class="flex items-center gap-2 rounded border border-gray-200 px-3 py-2">
                <input type="checkbox" x-model="form.mostrar_descripciones" class="rounded border-gray-300">
                <span class="text-sm text-gray-700">Mostrar descripciones de productos</span>
            </label>
        </div>

        <div class="pt-4 border-t flex items-center justify-between">
            <button type="submit" :disabled="loading" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50">
                <span x-show="!loading">Guardar configuracion</span>
                <span x-show="loading" x-cloak>Guardando...</span>
            </button>
            <span class="text-xs text-gray-500">Estos cambios impactan en la vista publica `/menu`.</span>
        </div>
    </form>
</div>

@push('scripts')
<script>
function configuracionNegocio() {
    return {
        loading: false,
        error: '',
        success: '',
        form: {
            nombre_negocio: '',
            slogan: '',
            logo_url: '',
            color_primario: '#ea580c',
            color_fondo: '#fff7ed',
            color_texto: '#111827',
            color_tarjeta: '#ffffff',
            color_header: '#ffffff',
            color_chip: '#ea580c',
            color_precio: '#ea580c',
            color_categoria: '#f97316',
            descripcion_corta: '',
            moneda: 'ARS',
            telefono_whatsapp: '',
            instagram_url: '',
            direccion: '',
            horario_atencion: '',
            mensaje_bienvenida: '',
            mostrar_precios: true,
            mostrar_descripciones: true,
        },
        async init() {
            await this.fetch();
        },
        async fetch() {
            try {
                const token = localStorage.getItem('token');
                const response = await axios.get('/api/configuracion-negocio', {
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                this.form = { ...this.form, ...(response.data || {}) };
            } catch (error) {
                this.error = error.response?.data?.message || 'No se pudo cargar la configuracion del negocio.';
            }
        },
        async guardar() {
            try {
                this.loading = true;
                this.error = '';
                this.success = '';
                const token = localStorage.getItem('token');
                const response = await axios.post('/api/configuracion-negocio', this.form, {
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                this.success = response.data?.message || 'Configuracion guardada.';
                if (response.data?.data) {
                    this.form = { ...this.form, ...response.data.data };
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
