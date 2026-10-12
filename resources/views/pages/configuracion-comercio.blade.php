@extends('layouts.app')

@section('title', 'Ajustes')
@section('page-title', 'Ajustes')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">Ajustes</h1>
    </div>

    <div x-data="configModulos()" x-init="init()" id="modulos" class="space-y-4">
        <div x-show="error" x-cloak class="p-4 bg-red-100 border border-red-400 text-red-700 rounded" x-text="error"></div>
        <div x-show="success" x-cloak class="p-4 bg-green-100 border border-green-400 text-green-700 rounded" x-text="success"></div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="mb-4 border-b pb-2">
                <h2 class="text-lg font-semibold text-gray-800">Módulos</h2>
                <p class="mt-1 text-sm text-gray-500">Activá o desactivá lo que usa tu comercio. El producto base queda siempre encendido.</p>
            </div>

            <template x-if="loading">
                <div class="py-8 text-center text-gray-500">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                    <p class="mt-2">Cargando módulos...</p>
                </div>
            </template>

            <template x-if="!loading">
                <div class="space-y-6">
                    <template x-for="group in grouped()" :key="group.key">
                        <div>
                            <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-400" x-text="group.label"></p>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <template x-for="mod in group.items" :key="mod.key">
                                    <div class="flex items-start justify-between gap-4 rounded-lg border p-4"
                                         :class="!mod.is_ready ? 'bg-gray-50 opacity-70' : 'bg-white'">
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-gray-800" x-text="mod.name"></p>
                                            <p class="mt-1 text-xs leading-5 text-gray-500" x-text="mod.description"></p>
                                            <p x-show="mod.locked" class="mt-2 text-[11px] font-medium text-gray-400">Incluido en el producto base</p>
                                            <p x-show="!mod.is_ready" class="mt-2 inline-block rounded-full bg-gray-200 px-2 py-0.5 text-[10px] font-semibold uppercase text-gray-600">Próximamente</p>
                                            <p x-show="mod.is_ready && mod.depends_on.length && !mod.locked" class="mt-2 text-[11px] text-gray-400">
                                                Requiere: <span x-text="dependencyNames(mod)"></span>
                                            </p>
                                        </div>
                                        <button
                                            type="button"
                                            role="switch"
                                            :aria-checked="mod.enabled.toString()"
                                            :disabled="mod.locked || !mod.is_ready || saving"
                                            @click="toggle(mod)"
                                            class="relative inline-flex h-6 w-11 shrink-0 rounded-full transition focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-not-allowed"
                                            :class="mod.enabled ? 'bg-blue-600' : 'bg-gray-300'"
                                        >
                                            <span class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition mt-0.5"
                                                  :class="mod.enabled ? 'translate-x-5' : 'translate-x-1'"></span>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                    <div class="flex justify-end">
                        <button
                            type="button"
                            @click="guardar()"
                            :disabled="saving"
                            class="px-6 py-3 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 font-medium"
                        >
                            <span x-show="!saving">Guardar módulos</span>
                            <span x-show="saving" x-cloak>Guardando...</span>
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>

<div x-data="configComercio()" x-init="init()" class="space-y-6">
    <div x-show="error" x-cloak class="p-4 bg-red-100 border border-red-400 text-red-700 rounded" x-text="error"></div>
    <div x-show="success" x-cloak class="p-4 bg-green-100 border border-green-400 text-green-700 rounded" x-text="success"></div>

    <template x-if="loading">
        <div class="p-8 text-center text-gray-500">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <p class="mt-2">Cargando configuración...</p>
        </div>
    </template>

    <template x-if="!loading">
        <form @submit.prevent="guardar()" class="space-y-6">
            <!-- Identidad del comercio -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Identidad del Comercio</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del Comercio *</label>
                        <input type="text" x-model="form.nombre_comercio" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:outline-none" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Slogan</label>
                        <input type="text" x-model="form.slogan" placeholder="Tu frase comercial..." class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                </div>

                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Logo del Comercio</label>
                    <div class="flex items-center gap-4">
                        <div class="flex-shrink-0">
                            <template x-if="logoPreview || form.logo_url">
                                <img :src="logoPreview || form.logo_url" alt="Logo" class="h-20 w-20 object-contain rounded-lg border border-gray-200 bg-white p-1">
                            </template>
                            <template x-if="!logoPreview && !form.logo_url">
                                <div class="h-20 w-20 bg-gray-100 rounded-lg border-2 border-dashed border-gray-300 flex items-center justify-center text-gray-400 text-xs text-center">
                                    Sin logo
                                </div>
                            </template>
                        </div>
                        <div class="flex-1 space-y-2">
                            <input
                                type="file"
                                accept="image/jpeg,image/png,image/svg+xml,image/webp"
                                @change="previewLogo($event)"
                                x-ref="logoInput"
                                class="block w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                            >
                            <p class="text-xs text-gray-500">JPG, PNG, SVG o WebP. Máx 2MB.</p>
                            <button
                                type="button"
                                x-show="form.logo_url || logoPreview"
                                @click="quitarLogo()"
                                class="text-sm text-red-600 hover:text-red-800"
                            >
                                Quitar logo
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Datos de contacto -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Datos de Contacto</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
                        <input type="text" x-model="form.direccion" placeholder="Calle 123, Ciudad..." class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                        <input type="text" x-model="form.telefono" placeholder="+54 11 1234-5678" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" x-model="form.email" placeholder="contacto@micomercio.com" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Sitio Web</label>
                        <input type="url" x-model="form.sitio_web" placeholder="https://www.micomercio.com" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                </div>
            </div>

            <!-- Personalización visual -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Personalización Visual</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Color Primario (botones, links)</label>
                        <div class="flex items-center gap-3">
                            <input type="color" x-model="form.color_primario" class="h-10 w-14 rounded cursor-pointer border border-gray-300">
                            <input type="text" x-model="form.color_primario" class="w-28 px-3 py-2 border border-gray-300 rounded-md text-sm font-mono" maxlength="7">
                            <button type="button" @click="form.color_primario = '#1e40af'" class="text-xs text-gray-500 hover:text-gray-700 underline">Reset</button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Color del Sidebar</label>
                        <div class="flex items-center gap-3">
                            <input type="color" x-model="form.color_sidebar" class="h-10 w-14 rounded cursor-pointer border border-gray-300">
                            <input type="text" x-model="form.color_sidebar" class="w-28 px-3 py-2 border border-gray-300 rounded-md text-sm font-mono" maxlength="7">
                            <button type="button" @click="form.color_sidebar = '#1f2937'" class="text-xs text-gray-500 hover:text-gray-700 underline">Reset</button>
                        </div>
                    </div>
                </div>
                <div class="mt-4 p-4 rounded-lg border border-gray-200 bg-gray-50">
                    <p class="text-sm font-medium text-gray-600 mb-2">Vista previa:</p>
                    <div class="flex gap-3 items-center">
                        <div class="h-12 w-48 rounded flex items-center justify-center text-white text-sm font-bold" :style="'background-color:' + form.color_sidebar">
                            Sidebar
                        </div>
                        <button type="button" class="px-4 py-2 rounded text-white text-sm font-medium" :style="'background-color:' + form.color_primario">
                            Botón ejemplo
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mensajes personalizados -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Mensajes Personalizados</h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Mensaje en Tickets / Presupuestos</label>
                        <textarea x-model="form.mensaje_ticket" rows="2" placeholder="Ej: Gracias por su compra. Los precios pueden variar sin previo aviso." class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:outline-none"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Mensaje en el pie de página del sistema</label>
                        <textarea x-model="form.mensaje_footer" rows="2" placeholder="Ej: Sistema desarrollado para Mi Comercio" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:outline-none"></textarea>
                    </div>
                </div>
            </div>

            <!-- Botón guardar -->
            <div class="flex justify-end">
                <button
                    type="submit"
                    :disabled="saving"
                    class="px-6 py-3 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 font-medium"
                >
                    <span x-show="!saving">Guardar Configuración</span>
                    <span x-show="saving" x-cloak>Guardando...</span>
                </button>
            </div>
        </form>
    </template>
</div>
</div>

@push('scripts')
<script>
function configModulos() {
    return {
        loading: true,
        saving: false,
        error: '',
        success: '',
        modules: [],
        families: {},

        grouped() {
            return Object.keys(this.families)
                .map((key) => ({
                    key,
                    label: this.families[key],
                    items: this.modules.filter((mod) => mod.family === key),
                }))
                .filter((group) => group.items.length > 0);
        },

        dependencyNames(mod) {
            return (mod.depends_on || [])
                .map((key) => this.modules.find((item) => item.key === key)?.name || key)
                .join(', ');
        },

        async init() {
            try {
                const res = await axios.get('/api/modulos');
                this.modules = res.data.modules || [];
                this.families = res.data.families || {};
            } catch (e) {
                this.error = e.response?.data?.message || 'Error al cargar los módulos';
            } finally {
                this.loading = false;
            }
        },

        toggle(mod) {
            if (mod.locked || !mod.is_ready || this.saving) {
                return;
            }

            mod.enabled = !mod.enabled;

            if (mod.enabled) {
                (mod.depends_on || []).forEach((key) => {
                    const dependency = this.modules.find((item) => item.key === key);
                    if (dependency && !dependency.locked) {
                        dependency.enabled = true;
                    }
                });
                return;
            }

            this.modules.forEach((item) => {
                if ((item.depends_on || []).includes(mod.key)) {
                    item.enabled = false;
                }
            });
        },

        async guardar() {
            try {
                this.saving = true;
                this.error = '';
                this.success = '';

                const res = await axios.put('/api/modulos', {
                    modules: this.modules.filter((mod) => mod.enabled).map((mod) => mod.key),
                });

                this.modules = res.data.modules || this.modules;
                this.success = 'Módulos actualizados. El menú se va a refrescar...';
                setTimeout(() => window.location.reload(), 700);
            } catch (e) {
                const errors = e.response?.data?.errors?.modules;
                this.error = (Array.isArray(errors) && errors[0]) || e.response?.data?.message || 'Error al guardar los módulos';
            } finally {
                this.saving = false;
            }
        }
    };
}

function configComercio() {
    return {
        loading: true,
        saving: false,
        error: '',
        success: '',
        logoFile: null,
        logoPreview: null,
        quitarLogoFlag: false,
        form: {
            nombre_comercio: '',
            slogan: '',
            logo_url: null,
            direccion: '',
            telefono: '',
            email: '',
            sitio_web: '',
            color_primario: '#1e40af',
            color_sidebar: '#1f2937',
            mensaje_ticket: '',
            mensaje_footer: '',
        },

        async init() {
            try {
                const res = await axios.get('/api/configuracion-comercio');
                const data = res.data;
                this.form = {
                    nombre_comercio: data.nombre_comercio || '',
                    slogan: data.slogan || '',
                    logo_url: data.logo_url || null,
                    direccion: data.direccion || '',
                    telefono: data.telefono || '',
                    email: data.email || '',
                    sitio_web: data.sitio_web || '',
                    color_primario: data.color_primario || '#1e40af',
                    color_sidebar: data.color_sidebar || '#1f2937',
                    mensaje_ticket: data.mensaje_ticket || '',
                    mensaje_footer: data.mensaje_footer || '',
                };
            } catch (e) {
                if (e.response?.status !== 404) {
                    this.error = 'Error al cargar la configuración';
                }
            } finally {
                this.loading = false;
            }
        },

        previewLogo(event) {
            const file = event.target.files[0];
            if (!file) return;
            this.logoFile = file;
            this.quitarLogoFlag = false;
            const reader = new FileReader();
            reader.onload = (e) => { this.logoPreview = e.target.result; };
            reader.readAsDataURL(file);
        },

        quitarLogo() {
            this.logoFile = null;
            this.logoPreview = null;
            this.form.logo_url = null;
            this.quitarLogoFlag = true;
            if (this.$refs.logoInput) this.$refs.logoInput.value = '';
        },

        async guardar() {
            try {
                this.saving = true;
                this.error = '';
                this.success = '';

                const formData = new FormData();
                formData.append('nombre_comercio', this.form.nombre_comercio);
                formData.append('slogan', this.form.slogan || '');
                formData.append('direccion', this.form.direccion || '');
                formData.append('telefono', this.form.telefono || '');
                formData.append('email', this.form.email || '');
                formData.append('sitio_web', this.form.sitio_web || '');
                formData.append('color_primario', this.form.color_primario || '#1e40af');
                formData.append('color_sidebar', this.form.color_sidebar || '#1f2937');
                formData.append('mensaje_ticket', this.form.mensaje_ticket || '');
                formData.append('mensaje_footer', this.form.mensaje_footer || '');

                if (this.quitarLogoFlag) {
                    formData.append('quitar_logo', '1');
                }
                if (this.logoFile) {
                    formData.append('logo', this.logoFile);
                }

                const res = await axios.post('/api/configuracion-comercio', formData, {
                    headers: { 'Content-Type': 'multipart/form-data' }
                });

                const data = res.data.config;
                if (data) {
                    this.form.logo_url = data.logo_url || null;
                }
                this.logoFile = null;
                this.logoPreview = null;
                this.quitarLogoFlag = false;

                this.success = 'Configuración guardada correctamente. Recargá la página para ver los cambios en el menú.';
                setTimeout(() => this.success = '', 5000);
            } catch (e) {
                this.error = e.response?.data?.message || 'Error al guardar la configuración';
            } finally {
                this.saving = false;
            }
        }
    };
}
</script>
@endpush
@endsection
