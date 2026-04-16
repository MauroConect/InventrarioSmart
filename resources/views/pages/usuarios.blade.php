@extends('layouts.app')

@section('title', 'Usuarios - El Cristo')
@section('page-title', 'Usuarios')

@section('content')
<div x-data="usuariosAdmin()" x-init="init()" class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
        <h1 class="text-2xl sm:text-3xl font-bold">Usuarios del sistema</h1>
        <button
            type="button"
            @click="openModal()"
            class="w-full sm:w-auto bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
        >
            Nuevo usuario
        </button>
    </div>

    <div x-show="error" x-cloak class="p-4 bg-red-100 border border-red-400 text-red-700 rounded" x-text="error"></div>
    <div x-show="success" x-cloak class="p-4 bg-green-100 border border-green-400 text-green-700 rounded" x-text="success"></div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <template x-if="loading">
            <div class="p-8 text-center text-gray-500">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <p class="mt-2">Cargando usuarios...</p>
            </div>
        </template>

        <template x-if="!loading">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rol</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="u in usuarios" :key="u.id">
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900" x-text="u.name"></td>
                                <td class="px-6 py-4 text-sm text-gray-600" x-text="u.email"></td>
                                <td class="px-6 py-4 text-sm">
                                    <span
                                        class="px-2 py-1 text-xs rounded-full font-medium"
                                        :class="(u.role || '').toLowerCase() === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800'"
                                        x-text="u.role || 'vendedor'"
                                    ></span>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <div class="flex flex-col sm:flex-row gap-2">
                                        <button type="button" @click="edit(u)" class="text-blue-600 hover:text-blue-800 text-left">Editar</button>
                                        <button type="button" @click="remove(u)" class="text-red-600 hover:text-red-800 text-left">Eliminar</button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
                <div
                    x-show="pagination.last_page > 1"
                    class="flex flex-col sm:flex-row justify-between items-center gap-3 px-4 py-3 bg-gray-50 border-t border-gray-200"
                >
                    <p class="text-sm text-gray-600" x-text="paginationText()"></p>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            :disabled="page <= 1"
                            @click="setPage(page - 1)"
                            class="px-3 py-1.5 text-sm border border-gray-300 rounded-md bg-white disabled:opacity-50"
                        >Anterior</button>
                        <span class="text-sm text-gray-700 tabular-nums" x-text="'Página ' + page + ' de ' + pagination.last_page"></span>
                        <button
                            type="button"
                            :disabled="page >= pagination.last_page"
                            @click="setPage(page + 1)"
                            class="px-3 py-1.5 text-sm border border-gray-300 rounded-md bg-white disabled:opacity-50"
                        >Siguiente</button>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <div
        x-show="showModal"
        x-cloak
        class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50 p-4"
        @click.away="closeModal()"
    >
        <div class="bg-white rounded-lg w-full max-w-md max-h-[90vh] overflow-y-auto" @click.stop>
            <div class="sticky top-0 bg-white border-b px-6 py-4">
                <h3 class="text-lg font-bold" x-text="editing ? 'Editar usuario' : 'Nuevo usuario'"></h3>
            </div>
            <form @submit.prevent="save()" class="p-6 space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
                    <input type="text" x-model="formData.name" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" x-model="formData.email" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Contraseña <span x-show="editing" class="text-gray-500 font-normal">(dejar vacío para no cambiar)</span>
                        <span x-show="!editing" class="text-red-500">*</span>
                    </label>
                    <input
                        type="password"
                        x-model="formData.password"
                        :required="!editing"
                        minlength="8"
                        autocomplete="new-password"
                        class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Mínimo 8 caracteres"
                    >
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Rol</label>
                    <select x-model="formData.role" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="admin">Administrador</option>
                        <option value="vendedor">Vendedor</option>
                    </select>
                </div>
                <div class="flex flex-col sm:flex-row justify-end gap-2 pt-3 border-t">
                    <button type="button" @click="closeModal()" class="w-full sm:w-auto px-4 py-2 border rounded hover:bg-gray-50">Cancelar</button>
                    <button type="submit" :disabled="saving" class="w-full sm:w-auto px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50">
                        <span x-show="!saving">Guardar</span>
                        <span x-show="saving" x-cloak>Guardando...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function usuariosAdmin() {
    return {
        usuarios: [],
        loading: true,
        saving: false,
        showModal: false,
        editing: null,
        page: 1,
        pagination: { current_page: 1, last_page: 1, total: 0, from: 0, to: 0 },
        error: '',
        success: '',
        formData: { name: '', email: '', password: '', role: 'vendedor' },

        async init() {
            await this.fetch();
        },

        paginationText() {
            const p = this.pagination;
            if (!p.total) return '';
            return 'Mostrando ' + (p.from || 0) + '–' + (p.to || 0) + ' de ' + p.total;
        },

        async setPage(n) {
            this.page = n;
            await this.fetch();
        },

        async fetch() {
            try {
                this.loading = true;
                this.error = '';
                const response = await axios.get('/api/usuarios', { params: { page: this.page } });
                const body = response.data;
                if (body && Array.isArray(body.data) && body.last_page !== undefined) {
                    this.usuarios = body.data;
                    this.pagination = {
                        current_page: body.current_page || 1,
                        last_page: body.last_page || 1,
                        total: body.total || 0,
                        from: body.from ?? 0,
                        to: body.to ?? 0,
                    };
                } else {
                    this.usuarios = Array.isArray(body) ? body : [];
                    this.pagination = { current_page: 1, last_page: 1, total: this.usuarios.length, from: 1, to: this.usuarios.length };
                }
            } catch (e) {
                const s = e.response?.status;
                this.error = s === 401
                    ? 'Sesión no válida o expirada. Recargá la página o cerrá sesión y volvé a ingresar.'
                    : (s === 419 ? 'Sesión de seguridad vencida. Recargá la página e intentá de nuevo.' : (e.response?.data?.message || 'Error al cargar usuarios.'));
            } finally {
                this.loading = false;
            }
        },

        openModal() {
            this.editing = null;
            this.formData = { name: '', email: '', password: '', role: 'vendedor' };
            this.showModal = true;
            this.error = '';
        },

        edit(u) {
            this.editing = u.id;
            this.formData = { name: u.name, email: u.email, password: '', role: (u.role || 'vendedor').toLowerCase() };
            this.showModal = true;
            this.error = '';
        },

        closeModal() {
            this.showModal = false;
            this.editing = null;
        },

        async save() {
            this.saving = true;
            this.error = '';
            this.success = '';
            try {
                const payload = {
                    name: this.formData.name,
                    email: this.formData.email,
                    role: this.formData.role,
                };
                if (this.formData.password) {
                    payload.password = this.formData.password;
                }
                if (this.editing) {
                    await axios.put('/api/usuarios/' + this.editing, payload);
                    this.success = 'Usuario actualizado.';
                } else {
                    if (!this.formData.password || this.formData.password.length < 8) {
                        this.error = 'La contraseña es obligatoria y debe tener al menos 8 caracteres.';
                        this.saving = false;
                        return;
                    }
                    await axios.post('/api/usuarios', payload);
                    this.success = 'Usuario creado.';
                }
                await this.fetch();
                this.closeModal();
                setTimeout(() => { this.success = ''; }, 3500);
            } catch (e) {
                const msg = e.response?.data?.message;
                if (e.response?.data?.errors) {
                    const first = Object.values(e.response.data.errors)[0];
                    this.error = Array.isArray(first) ? first[0] : msg || 'Error al guardar.';
                } else {
                    this.error = msg || 'Error al guardar.';
                }
            } finally {
                this.saving = false;
            }
        },

        async remove(u) {
            if (!confirm('¿Eliminar al usuario ' + u.name + '?')) return;
            this.error = '';
            this.success = '';
            try {
                await axios.delete('/api/usuarios/' + u.id);
                this.success = 'Usuario eliminado.';
                if (this.usuarios.length === 1 && this.page > 1) {
                    this.page -= 1;
                }
                await this.fetch();
                setTimeout(() => { this.success = ''; }, 3500);
            } catch (e) {
                this.error = e.response?.data?.message || 'Error al eliminar.';
            }
        },
    };
}
</script>
@endpush
@endsection
