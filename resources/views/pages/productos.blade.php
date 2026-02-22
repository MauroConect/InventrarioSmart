@extends('layouts.app')

@section('title', 'Productos - Inventario Inteligente')
@section('page-title', 'Productos')

@section('content')
<div x-data="productos()" x-init="init()" class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">Productos</h1>
        <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
            <input
                type="text"
                x-model="search"
                @input.debounce.500ms="fetch()"
                placeholder="Buscar por nombre o código..."
                class="w-full sm:w-auto px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
            <button
                @click="openModal()"
                class="w-full sm:w-auto bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
            >
                + Nuevo Producto
            </button>
        </div>
    </div>

    <div x-show="error" x-cloak class="p-4 bg-red-100 border border-red-400 text-red-700 rounded">
        <span x-text="error"></span>
    </div>

    <div x-show="success" x-cloak class="p-4 bg-green-100 border border-green-400 text-green-700 rounded">
        <span x-text="success"></span>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <template x-if="loading">
            <div class="p-8 text-center text-gray-500">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <p class="mt-2">Cargando productos...</p>
            </div>
        </template>
        
        <template x-if="!loading && productos.length === 0">
            <div class="p-8 text-center text-gray-500">
                <p class="text-lg">No hay productos registrados</p>
            </div>
        </template>
        
        <template x-if="!loading && productos.length > 0">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Código</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase hidden md:table-cell">Precio Compra</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Precio Venta</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stock</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase hidden lg:table-cell">Categoría</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase hidden sm:table-cell">Estado</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="producto in productos" :key="producto.id">
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900" x-text="producto.codigo"></td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <div class="font-medium" x-text="producto.nombre"></div>
                                    <div x-show="producto.descripcion" class="text-xs text-gray-500 mt-1" x-text="producto.descripcion"></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 hidden md:table-cell" 
                                    x-text="'$' + parseFloat(producto.precio_compra || 0).toFixed(2)"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900" 
                                    x-text="'$' + parseFloat(producto.precio_venta || 0).toFixed(2)"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span :class="parseInt(producto.stock_actual || 0) < parseInt(producto.stock_minimo || 0) ? 'text-red-600 font-bold' : 'text-gray-900'"
                                          x-text="producto.stock_actual || 0"></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 hidden lg:table-cell" 
                                    x-text="producto.categoria?.nombre || '-'"></td>
                                <td class="px-6 py-4 whitespace-nowrap hidden sm:table-cell">
                                    <span class="px-2 py-1 text-xs rounded-full" 
                                          :class="producto.activo ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                          x-text="producto.activo ? 'Activo' : 'Inactivo'"></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button @click="edit(producto)" class="text-blue-600 hover:text-blue-900 mr-3">Editar</button>
                                    <button @click="remove(producto.id)" class="text-red-600 hover:text-red-900">Eliminar</button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </template>
    </div>

    <!-- Modal -->
    <div x-show="showModal" 
         x-cloak
         class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4"
         @click.away="closeModal()">
        <div class="relative bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto" @click.stop>
            <div class="sticky top-0 bg-white border-b px-6 py-4">
                <h3 class="text-xl font-bold text-gray-800" x-text="editing ? 'Editar' : 'Nuevo' + ' Producto'"></h3>
            </div>
            <form @submit.prevent="save()" class="p-6 space-y-4">
                <div x-show="error" class="p-3 bg-red-100 border border-red-400 text-red-700 rounded text-sm" x-text="error"></div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Código *</label>
                        <input type="text" x-model="formData.codigo" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                        <input type="text" x-model="formData.nombre" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                    <textarea x-model="formData.descripcion" class="w-full px-3 py-2 border border-gray-300 rounded-md" rows="3"></textarea>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Precio Compra *</label>
                        <input type="number" step="0.01" x-model.number="formData.precio_compra" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Precio Venta *</label>
                        <input type="number" step="0.01" x-model.number="formData.precio_venta" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Stock Mínimo *</label>
                        <input type="number" x-model.number="formData.stock_minimo" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Stock Actual *</label>
                        <input type="number" x-model.number="formData.stock_actual" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Categoría *</label>
                        <select x-model="formData.categoria_id" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                            <option value="">Seleccionar...</option>
                            <template x-for="cat in categorias.filter(c => c.activa !== false)" :key="cat.id">
                                <option :value="cat.id" x-text="cat.nombre"></option>
                            </template>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Proveedor</label>
                    <select x-model="formData.proveedor_id" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        <option value="">Seleccionar (opcional)...</option>
                        <template x-for="prov in proveedores.filter(p => p.activo !== false)" :key="prov.id">
                            <option :value="prov.id" x-text="prov.nombre"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="flex items-center">
                        <input type="checkbox" x-model="formData.activo" class="mr-2">
                        <span class="text-sm text-gray-700">Producto activo</span>
                    </label>
                </div>
                <div class="flex justify-end gap-2 pt-4 border-t">
                    <button type="button" @click="closeModal()" class="px-6 py-2 border border-gray-300 rounded-md hover:bg-gray-50">Cancelar</button>
                    <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700" x-text="editing ? 'Actualizar' : 'Guardar'"></button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function productos() {
    return {
        productos: [],
        categorias: [],
        proveedores: [],
        loading: true,
        showModal: false,
        editing: null,
        search: '',
        error: '',
        success: '',
        formData: {
            codigo: '', nombre: '', descripcion: '', precio_compra: 0, precio_venta: 0,
            stock_minimo: 0, stock_actual: 0, categoria_id: '', proveedor_id: '', activo: true
        },
        
        async init() {
            await Promise.all([this.fetch(), this.fetchCategorias(), this.fetchProveedores()]);
        },
        
        async fetch() {
            try {
                this.loading = true;
                this.error = '';
                const params = this.search ? { search: this.search } : {};
                const response = await axios.get('/api/productos', {
                    params,
                    withCredentials: true
                });
                this.productos = response.data?.data || response.data || [];
            } catch (error) {
                console.error('Error:', error);
                this.error = 'Error al cargar productos: ' + (error.response?.data?.message || error.message);
                this.productos = [];
            } finally {
                this.loading = false;
            }
        },
        
        async fetchCategorias() {
            try {
                const response = await axios.get('/api/categorias', {
                    withCredentials: true
                });
                this.categorias = response.data?.data || response.data || [];
            } catch (error) {
                console.error('Error:', error);
            }
        },
        
        async fetchProveedores() {
            try {
                const response = await axios.get('/api/proveedores', {
                    withCredentials: true
                });
                this.proveedores = response.data?.data || response.data || [];
            } catch (error) {
                console.error('Error:', error);
            }
        },
        
        openModal() {
            this.editing = null;
            this.formData = {
                codigo: '', nombre: '', descripcion: '', precio_compra: 0, precio_venta: 0,
                stock_minimo: 0, stock_actual: 0, categoria_id: '', proveedor_id: '', activo: true
            };
            this.error = '';
            this.success = '';
            this.showModal = true;
        },
        
        edit(producto) {
            this.editing = producto.id;
            this.formData = {
                codigo: producto.codigo || '',
                nombre: producto.nombre || '',
                descripcion: producto.descripcion || '',
                precio_compra: producto.precio_compra || 0,
                precio_venta: producto.precio_venta || 0,
                stock_minimo: producto.stock_minimo || 0,
                stock_actual: producto.stock_actual || 0,
                categoria_id: producto.categoria_id || '',
                proveedor_id: producto.proveedor_id || '',
                activo: producto.activo !== undefined ? producto.activo : true
            };
            this.error = '';
            this.success = '';
            this.showModal = true;
        },
        
        closeModal() {
            this.showModal = false;
            this.editing = null;
            this.error = '';
        },
        
        async save() {
            try {
                this.error = '';
                this.success = '';
                if (this.editing) {
                    await axios.put(`/api/productos/${this.editing}`, this.formData, {
                        withCredentials: true
                    });
                    this.success = 'Producto actualizado correctamente';
                } else {
                    await axios.post('/api/productos', this.formData, {
                        withCredentials: true
                    });
                    this.success = 'Producto creado correctamente';
                }
                await this.fetch();
                setTimeout(() => {
                    this.closeModal();
                    this.success = '';
                }, 1000);
            } catch (error) {
                this.error = error.response?.data?.message || error.response?.data?.error || 'Error al guardar';
            }
        },
        
        async remove(id) {
            if (!confirm('¿Está seguro de eliminar este producto?')) return;
            try {
                await axios.delete(`/api/productos/${id}`, {
                    withCredentials: true
                });
                this.success = 'Producto eliminado correctamente';
                await this.fetch();
                setTimeout(() => this.success = '', 3000);
            } catch (error) {
                this.error = error.response?.data?.message || 'Error al eliminar';
                setTimeout(() => this.error = '', 5000);
            }
        }
    }
}
</script>
@endpush
@endsection
