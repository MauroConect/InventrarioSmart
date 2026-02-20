@extends('layouts.app')

@section('title', 'Aumento Masivo de Precios - Inventario Inteligente')
@section('page-title', 'Aumento Masivo de Precios')

@section('content')
<div x-data="aumentoMasivo()" x-init="init()" class="space-y-6">
    <h1 class="text-3xl font-bold">Aumento Masivo de Precios</h1>

    <div x-show="error" x-cloak class="p-4 bg-red-100 border border-red-400 text-red-700 rounded" x-text="error"></div>
    <div x-show="success" x-cloak class="p-4 bg-green-100 border border-green-400 text-green-700 rounded" x-text="success"></div>

    <div class="bg-white rounded-lg shadow p-6 space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Modo de Filtro</label>
                <select x-model="modoFiltro" @change="cambiarModo()" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    <option value="proveedor">Por Proveedor</option>
                    <option value="general">Todos los Productos</option>
                </select>
            </div>
            
            <div x-show="modoFiltro === 'proveedor'">
                <label class="block text-sm font-medium text-gray-700 mb-2">Proveedor</label>
                <select x-model="proveedorSeleccionado" @change="fetchProductos()" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    <option value="">Seleccionar proveedor...</option>
                    <template x-for="prov in proveedores" :key="prov.id">
                        <option :value="prov.id" x-text="prov.nombre"></option>
                    </template>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Porcentaje de Aumento</label>
                <input type="number" step="0.01" x-model.number="porcentaje" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Ej: 10">
            </div>
            <div>
                <label class="flex items-center">
                    <input type="checkbox" x-model="aplicarACompra" class="mr-2">
                    <span class="text-sm text-gray-700">Aplicar a Precio de Compra</span>
                </label>
            </div>
            <div>
                <label class="flex items-center">
                    <input type="checkbox" x-model="aplicarAVenta" class="mr-2">
                    <span class="text-sm text-gray-700">Aplicar a Precio de Venta</span>
                </label>
            </div>
        </div>

        <div x-show="modoFiltro === 'general'">
            <label class="block text-sm font-medium text-gray-700 mb-2">Buscar Productos</label>
            <input type="text" x-model="search" @input.debounce.300ms="filtrarProductos()" placeholder="Buscar por nombre o código..." class="w-full px-3 py-2 border border-gray-300 rounded-md">
        </div>

        <div class="flex justify-between items-center">
            <button @click="seleccionarTodos()" class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50">
                Seleccionar Todos
            </button>
            <button @click="aplicarAumento()" :disabled="loading || !aplicarACompra && !aplicarAVenta" class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50">
                <span x-show="!loading">Aplicar Aumento</span>
                <span x-show="loading" x-cloak>Aplicando...</span>
            </button>
        </div>

        <div class="bg-white rounded-lg border">
            <template x-if="cargandoProductos">
                <div class="p-8 text-center text-gray-500">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                    <p class="mt-2">Cargando productos...</p>
                </div>
            </template>
            
            <template x-if="!cargandoProductos && productosFiltrados.length > 0">
                <div class="overflow-x-auto max-h-96">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50 sticky top-0">
                            <tr>
                                <th class="px-4 py-3 text-left">
                                    <input type="checkbox" @change="toggleAll()" :checked="productosSeleccionados.size === productosFiltrados.length">
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Código</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Precio Compra</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Precio Venta</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <template x-for="producto in productosFiltrados" :key="producto.id">
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <input type="checkbox" :checked="productosSeleccionados.has(producto.id)" @change="toggleProducto(producto.id)">
                                    </td>
                                    <td class="px-4 py-3 text-sm" x-text="producto.codigo"></td>
                                    <td class="px-4 py-3 text-sm" x-text="producto.nombre"></td>
                                    <td class="px-4 py-3 text-sm" x-text="'$' + parseFloat(producto.precio_compra || 0).toFixed(2)"></td>
                                    <td class="px-4 py-3 text-sm" x-text="'$' + parseFloat(producto.precio_venta || 0).toFixed(2)"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </template>
        </div>
    </div>
</div>

@push('scripts')
<script>
function aumentoMasivo() {
    return {
        proveedores: [],
        productos: [],
        productosFiltrados: [],
        productosSeleccionados: new Set(),
        proveedorSeleccionado: '',
        modoFiltro: 'proveedor',
        porcentaje: 0,
        aplicarACompra: false,
        aplicarAVenta: false,
        loading: false,
        cargandoProductos: false,
        error: '',
        success: '',
        search: '',
        
        async init() {
            await this.fetchProveedores();
        },
        
        async fetchProveedores() {
            try {
                const token = localStorage.getItem('token');
                const response = await axios.get('/api/proveedores', {
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                this.proveedores = (response.data?.data || response.data || []).filter(p => p.activo !== false);
            } catch (error) {
                console.error('Error:', error);
            }
        },
        
        async cambiarModo() {
            this.productos = [];
            this.productosFiltrados = [];
            this.productosSeleccionados = new Set();
            if (this.modoFiltro === 'proveedor' && this.proveedorSeleccionado) {
                await this.fetchProductos();
            } else if (this.modoFiltro === 'general') {
                await this.fetchTodosLosProductos();
            }
        },
        
        async fetchProductos() {
            if (!this.proveedorSeleccionado) {
                this.productos = [];
                this.productosFiltrados = [];
                return;
            }
            try {
                this.cargandoProductos = true;
                const token = localStorage.getItem('token');
                const response = await axios.get(`/api/productos/proveedor/${this.proveedorSeleccionado}`, {
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                this.productos = Array.isArray(response.data) ? response.data : [];
                this.productosFiltrados = this.productos;
                this.productosSeleccionados = new Set();
            } catch (error) {
                console.error('Error:', error);
                this.error = 'Error al cargar productos';
            } finally {
                this.cargandoProductos = false;
            }
        },
        
        async fetchTodosLosProductos() {
            try {
                this.cargandoProductos = true;
                const token = localStorage.getItem('token');
                const response = await axios.get('/api/productos', {
                    params: { all: 'true' },
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                this.productos = response.data?.data || response.data || [];
                this.productosFiltrados = this.productos;
                this.productosSeleccionados = new Set();
            } catch (error) {
                console.error('Error:', error);
                this.error = 'Error al cargar productos';
            } finally {
                this.cargandoProductos = false;
            }
        },
        
        filtrarProductos() {
            if (!this.search) {
                this.productosFiltrados = this.productos;
                return;
            }
            const searchLower = this.search.toLowerCase();
            this.productosFiltrados = this.productos.filter(p => 
                (p.nombre && p.nombre.toLowerCase().includes(searchLower)) ||
                (p.codigo && p.codigo.toLowerCase().includes(searchLower))
            );
        },
        
        toggleProducto(id) {
            if (this.productosSeleccionados.has(id)) {
                this.productosSeleccionados.delete(id);
            } else {
                this.productosSeleccionados.add(id);
            }
        },
        
        toggleAll() {
            if (this.productosSeleccionados.size === this.productosFiltrados.length) {
                this.productosSeleccionados = new Set();
            } else {
                this.productosSeleccionados = new Set(this.productosFiltrados.map(p => p.id));
            }
        },
        
        seleccionarTodos() {
            this.productosSeleccionados = new Set(this.productosFiltrados.map(p => p.id));
        },
        
        async aplicarAumento() {
            if (this.productosSeleccionados.size === 0) {
                this.error = 'Debe seleccionar al menos un producto';
                return;
            }
            if (!this.aplicarACompra && !this.aplicarAVenta) {
                this.error = 'Debe seleccionar al menos un tipo de precio';
                return;
            }
            if (!this.porcentaje || this.porcentaje <= 0) {
                this.error = 'Debe ingresar un porcentaje válido';
                return;
            }
            
            try {
                this.loading = true;
                this.error = '';
                this.success = '';
                const token = localStorage.getItem('token');
                await axios.post('/api/productos/aumento-masivo', {
                    producto_ids: Array.from(this.productosSeleccionados),
                    porcentaje: this.porcentaje,
                    aplicar_a_compra: this.aplicarACompra,
                    aplicar_a_venta: this.aplicarAVenta
                }, {
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                this.success = `Aumento aplicado a ${this.productosSeleccionados.size} producto(s)`;
                await this.fetchProductos();
                this.productosSeleccionados = new Set();
                setTimeout(() => this.success = '', 3000);
            } catch (error) {
                this.error = error.response?.data?.message || 'Error al aplicar aumento';
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>
@endpush
@endsection
