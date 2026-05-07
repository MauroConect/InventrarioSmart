<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menú - Danielles</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body :style="{ backgroundColor: negocio.color_fondo, color: negocio.color_texto }">
    <div x-data="menuQr()" x-init="init()" class="min-h-screen">
        <header class="sticky top-0 z-10 backdrop-blur border-b border-orange-100 shadow-sm" :style="{ backgroundColor: negocio.color_header }">
            <div class="max-w-5xl mx-auto px-4 py-4">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-xs uppercase tracking-wider font-semibold" :style="{ color: negocio.color_primario }">Menú digital</p>
                        <div class="flex items-center gap-3">
                            <img x-show="negocio.logo_url" x-cloak :src="negocio.logo_url" alt="Logo negocio" class="h-10 w-10 rounded-full object-cover border border-orange-200">
                            <h1 class="text-2xl font-extrabold text-gray-900" x-text="negocio.nombre_negocio"></h1>
                        </div>
                        <p class="text-sm text-gray-500 mt-1" x-text="negocio.descripcion_corta"></p>
                        <p class="text-xs mt-1" :style="{ color: negocio.color_primario }" x-text="negocio.mensaje_bienvenida"></p>
                    </div>
                    <div class="rounded-full px-3 py-1 text-xs font-semibold text-white" :style="{ backgroundColor: negocio.color_chip }">
                        <span x-text="productos.length"></span> productos
                    </div>
                </div>
                <div class="mt-4">
                    <input
                        type="text"
                        x-model="search"
                        placeholder="Buscar por nombre o descripcion..."
                        class="w-full rounded-xl border border-orange-200 bg-white px-4 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-orange-500"
                    >
                </div>
            </div>
        </header>

        <main class="max-w-5xl mx-auto px-4 py-5 space-y-4">
            <template x-if="loading">
                <div class="py-20 text-center text-gray-500">
                    <div class="mx-auto h-10 w-10 rounded-full border-2 border-gray-200 border-t-orange-500 animate-spin"></div>
                    <p class="mt-3">Cargando menú...</p>
                </div>
            </template>

            <template x-if="error">
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" x-text="error"></div>
            </template>

            <template x-if="!loading && !error">
                <div class="space-y-5">
                    <div class="rounded-2xl border border-orange-100 bg-white/80 p-3 shadow-sm">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-sm font-semibold text-gray-700">Categorías</p>
                            <p class="text-xs text-gray-500">
                                Mostrando <span class="font-semibold text-orange-600" x-text="filteredProductos.length"></span> resultados
                            </p>
                        </div>
                        <div class="flex gap-2 overflow-x-auto pb-1">
                        <button
                            type="button"
                            @click="selectedCategoria = 'all'"
                            :class="selectedCategoria === 'all' ? 'text-white border-transparent' : 'bg-white text-gray-700 border-gray-300'"
                            :style="selectedCategoria === 'all' ? { backgroundColor: negocio.color_chip } : {}"
                            class="shrink-0 rounded-full border px-4 py-2 text-sm font-medium"
                        >
                            Todos
                        </button>
                        <template x-for="categoria in categorias" :key="categoria.id">
                            <button
                                type="button"
                                @click="selectedCategoria = categoria.id"
                                :class="selectedCategoria === categoria.id ? 'text-white border-transparent' : 'bg-white text-gray-700 border-gray-300'"
                                :style="selectedCategoria === categoria.id ? { backgroundColor: negocio.color_chip } : {}"
                                class="shrink-0 rounded-full border px-4 py-2 text-sm font-medium"
                                x-text="categoria.nombre"
                            ></button>
                        </template>
                    </div>
                    </div>

                    <template x-if="filteredProductos.length === 0">
                        <div class="rounded-xl border border-gray-200 bg-white px-4 py-10 text-center text-gray-500 shadow-sm">
                            <p class="font-medium">No encontramos productos con ese filtro.</p>
                            <p class="text-sm mt-1">Probá cambiando la categoría o borrando la búsqueda.</p>
                        </div>
                    </template>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        <template x-for="producto in filteredProductos" :key="producto.id">
                            <article @click="openProducto(producto)" class="cursor-pointer rounded-2xl border border-orange-100 p-4 shadow-sm hover:shadow-md transition-shadow" :style="{ backgroundColor: negocio.color_tarjeta }">
                                <template x-if="(producto.imagenes || []).length > 0">
                                    <img :src="producto.imagenes[0].ruta" alt="Foto producto" class="mb-3 h-40 w-full rounded-lg object-cover">
                                </template>
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-xs uppercase tracking-wide font-semibold" :style="{ color: negocio.color_categoria }" x-text="producto.categoria_nombre"></p>
                                        <h2 class="text-base font-semibold leading-tight" x-text="producto.nombre"></h2>
                                    </div>
                                    <p
                                        x-show="negocio.mostrar_precios"
                                        x-cloak
                                        class="text-lg font-extrabold"
                                        :style="{ color: negocio.color_precio }"
                                        x-text="formatPrice(producto.precio_venta)"
                                    ></p>
                                </div>
                                <p
                                    x-show="negocio.mostrar_descripciones"
                                    x-cloak
                                    class="mt-2 text-sm text-gray-600"
                                    x-text="producto.descripcion || 'Sin descripcion disponible.'"
                                ></p>
                            </article>
                        </template>
                    </div>

                    <footer class="rounded-2xl border border-orange-100 p-4 text-sm text-gray-600" :style="{ backgroundColor: negocio.color_tarjeta }">
                        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                            <p x-show="negocio.direccion" x-cloak><strong>Direccion:</strong> <span x-text="negocio.direccion"></span></p>
                            <p x-show="negocio.horario_atencion" x-cloak><strong>Horario:</strong> <span x-text="negocio.horario_atencion"></span></p>
                        </div>
                        <div class="mt-2 flex flex-wrap items-center gap-3">
                            <a
                                x-show="negocio.telefono_whatsapp"
                                x-cloak
                                :href="'https://wa.me/' + (negocio.telefono_whatsapp || '').replace(/\D/g, '')"
                                class="text-orange-700 font-semibold hover:underline"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                WhatsApp
                            </a>
                            <a
                                x-show="negocio.instagram_url"
                                x-cloak
                                :href="negocio.instagram_url"
                                class="text-orange-700 font-semibold hover:underline"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                Instagram
                            </a>
                        </div>
                    </footer>
                </div>
            </template>
        </main>

        <div
            x-show="selectedProducto"
            x-cloak
            class="fixed inset-0 z-50 bg-black/80 flex items-center justify-center p-3"
            @keydown.escape.window="closeProducto()"
            @click.self="closeProducto()"
        >
            <div class="relative w-full max-w-3xl max-h-[95vh] overflow-hidden rounded-2xl bg-white">
                <button
                    type="button"
                    @click="closeProducto()"
                    class="absolute right-3 top-3 z-10 rounded-full bg-black/70 text-white px-3 py-1 text-sm"
                >
                    Cerrar
                </button>

                <div class="relative bg-gray-100" x-show="selectedProducto">
                    <template x-if="(selectedProducto?.imagenes || []).length > 0">
                        <img :src="selectedProducto.imagenes[currentImageIndex].ruta" alt="Imagen producto" class="h-[45vh] w-full object-cover">
                    </template>
                    <template x-if="(selectedProducto?.imagenes || []).length === 0">
                        <div class="h-[45vh] w-full flex items-center justify-center text-gray-400">Sin imágenes</div>
                    </template>

                    <button
                        x-show="(selectedProducto?.imagenes || []).length > 1"
                        type="button"
                        @click="prevImage()"
                        class="absolute left-3 top-1/2 -translate-y-1/2 rounded-full bg-black/60 text-white px-3 py-2"
                    >
                        ‹
                    </button>
                    <button
                        x-show="(selectedProducto?.imagenes || []).length > 1"
                        type="button"
                        @click="nextImage()"
                        class="absolute right-3 top-1/2 -translate-y-1/2 rounded-full bg-black/60 text-white px-3 py-2"
                    >
                        ›
                    </button>
                </div>

                <div class="p-4 space-y-2">
                    <p class="text-xs uppercase tracking-wide font-semibold" :style="{ color: negocio.color_categoria }" x-text="selectedProducto?.categoria_nombre"></p>
                    <h2 class="text-2xl font-bold" x-text="selectedProducto?.nombre"></h2>
                    <p
                        x-show="negocio.mostrar_precios"
                        x-cloak
                        class="text-xl font-extrabold"
                        :style="{ color: negocio.color_precio }"
                        x-text="formatPrice(selectedProducto?.precio_venta)"
                    ></p>
                    <p
                        x-show="negocio.mostrar_descripciones"
                        x-cloak
                        class="text-gray-600"
                        x-text="selectedProducto?.descripcion || 'Sin descripcion disponible.'"
                    ></p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function menuQr() {
            return {
                loading: true,
                error: '',
                search: '',
                selectedCategoria: 'all',
                categorias: [],
                productos: [],
                selectedProducto: null,
                currentImageIndex: 0,
                negocio: {
                    nombre_negocio: 'Danielles Bar & Buffet',
                    slogan: 'Menu digital',
                    logo_url: '',
                    color_primario: '#ea580c',
                    color_fondo: '#fff7ed',
                    color_texto: '#111827',
                    color_tarjeta: '#ffffff',
                    color_header: '#ffffff',
                    color_chip: '#ea580c',
                    color_precio: '#ea580c',
                    color_categoria: '#f97316',
                    descripcion_corta: 'Explora categorias y productos disponibles.',
                    moneda: 'ARS',
                    telefono_whatsapp: '',
                    instagram_url: '',
                    direccion: '',
                    horario_atencion: '',
                    mensaje_bienvenida: 'Bienvenido a nuestro menu digital',
                    mostrar_precios: true,
                    mostrar_descripciones: true,
                },

                async init() {
                    await this.fetchCatalogo();
                },

                async fetchCatalogo() {
                    try {
                        this.loading = true;
                        this.error = '';
                        const response = await fetch('/api/menu/catalogo');
                        if (!response.ok) {
                            throw new Error('No se pudo cargar el menú en este momento.');
                        }

                        const payload = await response.json();
                        this.negocio = { ...this.negocio, ...(payload.negocio || {}) };
                        this.categorias = payload.categorias || [];
                        this.productos = this.categorias.flatMap((categoria) =>
                            (categoria.productos || []).map((producto) => ({
                                ...producto,
                                categoria_nombre: categoria.nombre,
                                imagenes: (producto.imagenes || []).map((img) => ({
                                    ...img,
                                    ruta: this.normalizeImageUrl(img.ruta),
                                })),
                            }))
                        );
                    } catch (err) {
                        this.error = err.message || 'Error inesperado al cargar el menú.';
                    } finally {
                        this.loading = false;
                    }
                },

                formatPrice(price) {
                    const value = Number(price || 0);
                    const currency = this.negocio.moneda || 'ARS';
                    return value.toLocaleString('es-AR', {
                        style: 'currency',
                        currency,
                        minimumFractionDigits: 2,
                    });
                },

                normalizeImageUrl(url) {
                    const raw = (url || '').trim();
                    if (!raw) return '';

                    if (raw.startsWith('/')) {
                        const m = raw.match(/\/storage\/productos\/([^/?#]+)/i);
                        if (m && m[1]) {
                            return `${window.location.origin}/menu/imagenes/${encodeURIComponent(m[1])}`;
                        }
                        return `${window.location.origin}${raw}`;
                    }

                    if (/^https?:\/\//i.test(raw)) {
                        try {
                            const parsed = new URL(raw);
                            const m = parsed.pathname.match(/\/storage\/productos\/([^/?#]+)/i);
                            if (m && m[1]) {
                                return `${window.location.origin}/menu/imagenes/${encodeURIComponent(m[1])}`;
                            }
                            if (parsed.hostname === 'danielles.store' || parsed.hostname === 'www.danielles.store') {
                                return `${window.location.origin}${parsed.pathname}${parsed.search || ''}`;
                            }
                        } catch (e) {
                            return raw;
                        }
                        return raw;
                    }

                    const m = raw.match(/productos\/([^/?#]+)/i);
                    if (m && m[1]) {
                        return `${window.location.origin}/menu/imagenes/${encodeURIComponent(m[1])}`;
                    }

                    return `${window.location.origin}/${raw.replace(/^\/+/, '')}`;
                },

                get filteredProductos() {
                    const term = this.search.trim().toLowerCase();

                    return this.productos.filter((producto) => {
                        const byCategoria = this.selectedCategoria === 'all'
                            || Number(producto.categoria_id) === Number(this.selectedCategoria);
                        if (!byCategoria) {
                            return false;
                        }

                        if (!term) {
                            return true;
                        }

                        return (
                            producto.nombre.toLowerCase().includes(term) ||
                            (producto.descripcion || '').toLowerCase().includes(term)
                        );
                    });
                },

                openProducto(producto) {
                    this.selectedProducto = producto;
                    this.currentImageIndex = 0;
                },

                closeProducto() {
                    this.selectedProducto = null;
                    this.currentImageIndex = 0;
                },

                nextImage() {
                    const total = (this.selectedProducto?.imagenes || []).length;
                    if (total < 2) return;
                    this.currentImageIndex = (this.currentImageIndex + 1) % total;
                },

                prevImage() {
                    const total = (this.selectedProducto?.imagenes || []).length;
                    if (total < 2) return;
                    this.currentImageIndex = (this.currentImageIndex - 1 + total) % total;
                },
            };
        }
    </script>
</body>
</html>
