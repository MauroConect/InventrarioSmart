@extends('layouts.app')

@section('title', 'Turnos')
@section('page-title', 'Turnos')

@section('content')
<div x-data="agendaTurnos()" x-init="init()" class="space-y-4">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold">Agenda de turnos</h1>
            <p class="text-sm text-gray-600">Consultorio, dentista y servicios con cita.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <input type="date" x-model="date" @change="cargar()" class="rounded-md border px-3 py-2 text-sm">
            <button type="button" @click="abrirFormulario()" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Nuevo turno</button>
        </div>
    </div>

    <div x-show="error" x-cloak class="rounded border border-red-300 bg-red-50 p-3 text-red-800" x-text="error"></div>
    <div x-show="success" x-cloak class="rounded border border-green-300 bg-green-50 p-3 text-green-800" x-text="success"></div>

    <div class="overflow-hidden rounded-lg bg-white shadow">
        <template x-if="loading">
            <p class="p-8 text-center text-gray-500">Cargando agenda…</p>
        </template>
        <template x-if="!loading && turnos.length === 0">
            <p class="p-8 text-center text-gray-500">No hay turnos este día.</p>
        </template>
        <ul class="divide-y" x-show="!loading && turnos.length">
            <template x-for="turno in turnos" :key="turno.id">
                <li class="flex flex-col gap-2 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="font-semibold">
                            <span x-text="hora(turno.starts_at) + ' – ' + hora(turno.ends_at)"></span>
                            <span class="ml-2 text-gray-800" x-text="turno.nombre"></span>
                        </p>
                        <p class="text-sm text-gray-500">
                            <span x-text="turno.prestacion || 'Consulta'"></span>
                            <span x-show="turno.telefono" x-text="' · ' + turno.telefono"></span>
                        </p>
                        <p class="mt-1 flex flex-wrap gap-x-3 gap-y-1 text-xs text-gray-500" x-show="(turno.custom_fields || []).length">
                            <template x-for="campo in (turno.custom_fields || [])" :key="campo.id">
                                <span x-show="campo.value !== '' && campo.value !== false && campo.value != null" x-text="campo.label + ': ' + (campo.type === 'checkbox' ? (campo.value ? 'Sí' : 'No') : campo.value)"></span>
                            </template>
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-full px-2 py-1 text-xs font-medium"
                              :class="badge(turno.status)"
                              x-text="etiquetaEstado(turno.status)"></span>
                        <button type="button" x-show="turno.status === 'scheduled'" @click="cambiarEstado(turno, 'confirmed')" class="text-sm text-blue-700">Confirmar</button>
                        <button type="button" x-show="['scheduled','confirmed'].includes(turno.status)" @click="cambiarEstado(turno, 'completed')" class="text-sm text-green-700">Atendido</button>
                        <button type="button" x-show="['scheduled','confirmed'].includes(turno.status)" @click="cambiarEstado(turno, 'no_show')" class="text-sm text-amber-700">Ausente</button>
                        <button type="button" x-show="turno.status !== 'cancelled'" @click="cancelar(turno)" class="text-sm text-red-700">Cancelar</button>
                    </div>
                </li>
            </template>
        </ul>
    </div>

    <div x-show="showForm" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="showForm = false">
        <form @submit.prevent="guardar()" class="w-full max-w-md max-h-[90vh] overflow-y-auto space-y-3 rounded-lg bg-white p-6 shadow-xl">
            <h3 class="text-lg font-bold">Nuevo turno</h3>
            <input x-model="form.nombre" required placeholder="Nombre del paciente" class="w-full rounded-md border px-3 py-2">
            <input x-model="form.telefono" placeholder="Teléfono" class="w-full rounded-md border px-3 py-2">
            <input x-model="form.prestacion" placeholder="Prestación (limpieza, control…)" class="w-full rounded-md border px-3 py-2">
            <div class="grid grid-cols-2 gap-2">
                <input type="datetime-local" x-model="form.starts_at" required class="rounded-md border px-3 py-2 text-sm">
                <input type="datetime-local" x-model="form.ends_at" class="rounded-md border px-3 py-2 text-sm">
            </div>
            <textarea x-model="form.notes" rows="2" placeholder="Notas" class="w-full rounded-md border px-3 py-2"></textarea>
            @include('partials.custom-form-fields')
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" @click="showForm = false" class="rounded-md border px-4 py-2 text-sm">Cerrar</button>
                <button class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Guardar</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function agendaTurnos() {
    return {
        ...customFormMixin('turnos'),
        date: new Date().toISOString().slice(0, 10),
        turnos: [],
        loading: true,
        error: '',
        success: '',
        showForm: false,
        form: { nombre: '', telefono: '', prestacion: '', starts_at: '', ends_at: '', notes: '' },
        authHeaders() {
            const t = localStorage.getItem('token');
            return t ? { Authorization: 'Bearer ' + t } : {};
        },
        hora(value) {
            return new Date(value).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        },
        etiquetaEstado(status) {
            return { scheduled: 'Reservado', confirmed: 'Confirmado', completed: 'Atendido', cancelled: 'Cancelado', no_show: 'Ausente' }[status] || status;
        },
        badge(status) {
            return {
                scheduled: 'bg-slate-100 text-slate-800',
                confirmed: 'bg-blue-100 text-blue-800',
                completed: 'bg-green-100 text-green-800',
                cancelled: 'bg-gray-100 text-gray-500',
                no_show: 'bg-amber-100 text-amber-800',
            }[status] || 'bg-slate-100';
        },
        async init() {
            await Promise.all([this.cargar(), this.loadCustomFields()]);
        },
        async cargar() {
            this.loading = true;
            this.error = '';
            try {
                const res = await axios.get('/api/turnos', { params: { date: this.date }, headers: this.authHeaders() });
                this.turnos = res.data.data || [];
            } catch (e) {
                this.error = e.response?.data?.message || 'No se pudo cargar la agenda.';
            } finally {
                this.loading = false;
            }
        },
        abrirFormulario() {
            const start = this.date + 'T09:00';
            this.form = { nombre: '', telefono: '', prestacion: '', starts_at: start, ends_at: this.date + 'T09:30', notes: '' };
            this.resetCustomValues();
            this.showAddCustomField = false;
            this.showForm = true;
        },
        async guardar() {
            this.error = '';
            try {
                await axios.post('/api/turnos', { ...this.form, custom_fields: this.customValues }, { headers: this.authHeaders() });
                this.showForm = false;
                this.success = 'Turno reservado.';
                await this.cargar();
            } catch (e) {
                this.error = e.response?.data?.message || Object.values(e.response?.data?.errors || {})[0]?.[0] || 'No se pudo guardar.';
            }
        },
        async cambiarEstado(turno, status) {
            try {
                await axios.put('/api/turnos/' + turno.id, {
                    nombre: turno.nombre,
                    telefono: turno.telefono,
                    prestacion: turno.prestacion,
                    starts_at: turno.starts_at,
                    ends_at: turno.ends_at,
                    notes: turno.notes,
                    status,
                }, { headers: this.authHeaders() });
                await this.cargar();
            } catch (e) {
                this.error = e.response?.data?.message || 'No se pudo actualizar.';
            }
        },
        async cancelar(turno) {
            if (!confirm('¿Cancelar este turno?')) return;
            try {
                await axios.delete('/api/turnos/' + turno.id, { headers: this.authHeaders() });
                this.success = 'Turno cancelado.';
                await this.cargar();
            } catch (e) {
                this.error = e.response?.data?.message || 'No se pudo cancelar.';
            }
        },
    };
}
</script>
@endpush
