@extends('layouts.app')

@section('title', 'Ventas - Inventario Inteligente')
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
            + Nueva Venta
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
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo Pago</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="venta in ventas" :key="venta.id">
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900" x-text="venta.id"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="new Date(venta.created_at).toLocaleString()"></td>
                                <td class="px-6 py-4 text-sm" x-text="venta.cliente ? (venta.cliente.nombre + ' ' + venta.cliente.apellido) : 'Cliente General'"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium" x-text="'$' + parseFloat(venta.total || 0).toFixed(2)"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm" x-text="venta.tipo_pago"></td>
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
    <div x-show="showModal" x-cloak class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4" @click.away="closeModal()">
        <div class="relative bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] overflow-y-auto" @click.stop>
            <div class="sticky top-0 bg-white border-b px-6 py-4">
                <h3 class="text-xl font-bold">Nueva Venta</h3>
            </div>
            <form @submit.prevent="guardarVenta()" class="p-6 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cliente (opcional)</label>
                        <select x-model="clienteId" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            <option value="">Cliente General</option>
                            <template x-for="cliente in clientes" :key="cliente.id">
                                <option :value="cliente.id" x-text="cliente.nombre + ' ' + cliente.apellido"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Pago *</label>
                        <select x-model="tipoPago" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                            <option value="efectivo">Efectivo</option>
                            <option value="tarjeta">Tarjeta</option>
                            <option value="mixto">Mixto</option>
                            <option value="cuenta_corriente">Cuenta Corriente</option>
                        </select>
                    </div>
                </div>

                <div x-show="tipoPago === 'mixto'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Monto Tarjeta</label>
                        <input type="number" step="0.01" x-model.number="montoTarjeta" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Monto Efectivo</label>
                        <input type="number" step="0.01" x-model.number="montoEfectivo" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descuento</label>
                    <input type="number" step="0.01" x-model.number="descuento" class="w-full px-3 py-2 border border-gray-300 rounded-md" value="0">
                </div>

                <div class="border-t pt-4">
                    <div class="flex justify-between items-center mb-4">
                        <h4 class="font-semibold">Productos</h4>
                        <button type="button" @click="agregarItem()" class="px-3 py-1 bg-gray-200 rounded text-sm hover:bg-gray-300">+ Agregar</button>
                    </div>
                    <div class="space-y-2">
                        <template x-for="(item, index) in items" :key="index">
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-2 items-end">
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Producto</label>
                                    <select x-model="item.producto_id" class="w-full px-2 py-1 border border-gray-300 rounded-md text-sm" required>
                                        <option value="">Seleccionar...</option>
                                        <template x-for="prod in productos" :key="prod.id">
                                            <option :value="prod.id" x-text="prod.nombre"></option>
                                        </template>
                                    </select>
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
                </div>

                <div class="flex justify-end gap-2 pt-4 border-t">
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
        descuento: 0,
        items: [{ producto_id: '', cantidad: 1 }],
        
        async init() {
            await Promise.all([this.fetchVentas(), this.fetchDatosFormulario()]);
        },
        
        async fetchVentas() {
            try {
                this.loadingLista = true;
                const token = localStorage.getItem('token');
                const response = await axios.get('/api/ventas', {
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                this.ventas = response.data?.data || response.data || [];
            } catch (error) {
                console.error('Error:', error);
            } finally {
                this.loadingLista = false;
            }
        },
        
        async fetchDatosFormulario() {
            try {
                this.loadingFormDatos = true;
                const token = localStorage.getItem('token');
                const [clientesRes, productosRes, cajasRes] = await Promise.all([
                    axios.get('/api/clientes', { headers: { 'Authorization': `Bearer ${token}` } }),
                    axios.get('/api/productos', { headers: { 'Authorization': `Bearer ${token}` } }),
                    axios.get('/api/cajas', { params: { estado: 'abierta' }, headers: { 'Authorization': `Bearer ${token}` } })
                ]);
                this.clientes = (clientesRes.data?.data || clientesRes.data || []).filter(c => c.activo !== false);
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
        
        calcularSubtotal(item) {
            const prod = this.obtenerProducto(item.producto_id);
            if (!prod) return 0;
            return (parseFloat(prod.precio_venta || 0) * (parseInt(item.cantidad) || 0)) || 0;
        },
        
        calcularTotal() {
            const totalBruto = this.items.reduce((acc, item) => acc + this.calcularSubtotal(item), 0);
            return totalBruto - (parseFloat(this.descuento) || 0);
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
            this.descuento = 0;
            this.items = [{ producto_id: '', cantidad: 1 }];
        },
        
        async guardarVenta() {
            if (!this.cajaSeleccionada) {
                this.error = 'Debe seleccionar una caja';
                return;
            }
            
            const itemsValidos = this.items
                .filter(item => item.producto_id && (parseInt(item.cantidad) || 0) > 0)
                .map(item => ({
                    producto_id: item.producto_id,
                    cantidad: parseInt(item.cantidad) || 0,
                }));
            
            if (itemsValidos.length === 0) {
                this.error = 'Debe agregar al menos un producto';
                return;
            }
            
            try {
                this.loadingSubmit = true;
                this.error = '';
                this.success = '';
                const token = localStorage.getItem('token');
                
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
                }
                
                const response = await axios.post('/api/ventas', payload, {
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                
                this.success = 'Venta registrada correctamente';
                await this.fetchVentas();
                this.closeModal();
                
                if (response.data?.id) {
                    setTimeout(() => {
                        window.location.href = `/ventas/${response.data.id}`;
                    }, 1000);
                }
            } catch (error) {
                this.error = error.response?.data?.message || 'Error al registrar la venta';
            } finally {
                this.loadingSubmit = false;
            }
        }
    }
}
</script>
@endpush
@endsection
