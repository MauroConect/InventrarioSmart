@extends('layouts.platform')

@section('title', $tenant->name)
@section('page-title', $tenant->name)

@section('actions')
    @if ($tenant->status === 'active')
        <form method="POST" action="{{ url('/platform/tenants/'.$tenant->getRouteKey().'/suspend') }}" x-data="{ open: false }">
            @csrf
            @method('PATCH')
            <button type="button" @click="open = true" class="rounded-lg border border-rose-300 bg-white px-4 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50">Suspender</button>
            <div x-cloak x-show="open" @click.outside="open = false" class="fixed right-4 top-20 z-50 w-[calc(100%-2rem)] max-w-md rounded-2xl border border-slate-200 bg-white p-5 text-left shadow-2xl">
                <h3 class="font-bold text-slate-900">Suspender comercio</h3>
                <p class="mt-1 text-sm text-slate-500">La operación quedará bloqueada sin eliminar información.</p>
                <label class="mt-4 block text-sm font-semibold text-slate-700">
                    Motivo
                    <textarea name="reason" maxlength="1000" rows="3" class="mt-2 w-full rounded-lg border-slate-300 px-3 py-2 font-normal focus:border-cyan-500 focus:ring-cyan-500"></textarea>
                </label>
                <div class="mt-4 flex justify-end gap-2">
                    <button type="button" @click="open = false" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold">Cancelar</button>
                    <button class="rounded-lg bg-rose-600 px-3 py-2 text-sm font-semibold text-white hover:bg-rose-500">Confirmar suspensión</button>
                </div>
            </div>
        </form>
    @else
        <form method="POST" action="{{ url('/platform/tenants/'.$tenant->getRouteKey().'/reactivate') }}">
            @csrf
            @method('PATCH')
            <button class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">Reactivar</button>
        </form>
    @endif
@endsection

@section('content')
    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-medium text-slate-500">Identidad del comercio</p>
                        <p class="mt-1 font-mono text-sm text-slate-700">{{ $tenant->uuid }}</p>
                    </div>
                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $tenant->status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                        {{ $tenant->status === 'active' ? 'Activo' : 'Suspendido' }}
                    </span>
                </div>
                <dl class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    <div><dt class="text-xs uppercase tracking-wide text-slate-500">Slug</dt><dd class="mt-1 font-semibold">{{ $tenant->slug }}</dd></div>
                    <div><dt class="text-xs uppercase tracking-wide text-slate-500">Perfil</dt><dd class="mt-1 font-semibold">{{ $tenant->rubro_profile }}</dd></div>
                    <div><dt class="text-xs uppercase tracking-wide text-slate-500">Zona horaria</dt><dd class="mt-1 font-semibold">{{ $tenant->timezone }}</dd></div>
                    <div><dt class="text-xs uppercase tracking-wide text-slate-500">Usuarios</dt><dd class="mt-1 font-semibold">{{ $tenant->users_count }}</dd></div>
                </dl>
                @if ($tenant->status === 'suspended' && $tenant->suspension_reason)
                    <div class="mt-5 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
                        <span class="font-semibold">Motivo de suspensión:</span> {{ $tenant->suspension_reason }}
                    </div>
                @endif
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-6 py-4">
                    <h3 class="font-semibold">Membresías</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-6 py-3">Usuario</th>
                                <th class="px-6 py-3">Rol</th>
                                <th class="px-6 py-3">Estado</th>
                                <th class="px-6 py-3">Predeterminado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($tenant->memberships as $membership)
                                <tr>
                                    <td class="px-6 py-4">
                                        <p class="font-semibold">{{ $membership->user?->name ?? 'Usuario eliminado' }}</p>
                                        <p class="text-xs text-slate-500">{{ $membership->user?->email }}</p>
                                    </td>
                                    <td class="px-6 py-4">{{ $membership->role }}</td>
                                    <td class="px-6 py-4">{{ $membership->status }}</td>
                                    <td class="px-6 py-4">{{ $membership->is_default ? 'Sí' : 'No' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-6 py-10 text-center text-slate-500">Sin membresías.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                    <h3 class="font-semibold">Auditoría reciente</h3>
                    <a href="{{ url('/platform/audit?tenant_id='.$tenant->id) }}" class="text-sm font-semibold text-cyan-700">Ver historial</a>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse ($events as $event)
                        <div x-data="{ open: false }" class="px-6 py-4">
                            <button type="button" class="flex w-full items-start justify-between gap-4 text-left" @click="open = !open">
                                <span>
                                    <span class="block font-semibold">{{ $event->action }}</span>
                                    <span class="mt-1 block text-xs text-slate-500">{{ $event->actor?->name ?? 'Sistema' }} · {{ $event->created_at?->format('d/m/Y H:i:s') }}</span>
                                </span>
                                <span class="text-slate-400" x-text="open ? '−' : '+'"></span>
                            </button>
                            <div x-cloak x-show="open" class="mt-3 grid gap-3 sm:grid-cols-2">
                                <pre class="overflow-x-auto rounded-lg bg-slate-950 p-3 text-xs text-slate-200">{{ json_encode($event->before, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                <pre class="overflow-x-auto rounded-lg bg-slate-950 p-3 text-xs text-slate-200">{{ json_encode($event->after, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            </div>
                        </div>
                    @empty
                        <p class="px-6 py-10 text-center text-sm text-slate-500">Sin eventos.</p>
                    @endforelse
                </div>
            </section>
        </div>

        <aside class="space-y-6">
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold">Módulos habilitados</h3>
                    <a href="{{ url('/platform/modules?search='.urlencode($tenant->slug)) }}" class="text-sm font-semibold text-cyan-700">Editar</a>
                </div>
                <div class="mt-4 space-y-3">
                    @php($enabledKeys = $tenant->moduleGrants->where('enabled', true)->pluck('module_key')->all())
                    @foreach ($modules as $module)
                        @php($enabled = in_array($module->key, $enabledKeys, true))
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-sm text-slate-700">{{ $module->name }}</span>
                            <span class="h-2.5 w-2.5 rounded-full {{ $enabled ? 'bg-emerald-500' : 'bg-slate-300' }}" title="{{ $enabled ? 'Habilitado' : 'Deshabilitado' }}"></span>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="font-semibold">Repositorio y entrega</h3>
                <p class="mt-1 text-sm text-slate-500">El producto es multi-tenant. El repo privado sirve para features exclusivas del cliente.</p>
                <form method="POST" action="{{ url('/platform/tenants/'.$tenant->getRouteKey().'/delivery') }}" class="mt-4 space-y-3">
                    @csrf
                    @method('PATCH')
                    <label class="block text-sm"><span class="font-semibold">Repo privado</span><input name="repository_url" value="{{ old('repository_url', $tenant->repository_url) }}" class="mt-1 w-full rounded-lg border px-3 py-2"></label>
                    <label class="block text-sm"><span class="font-semibold">Versión núcleo</span><input name="core_version" value="{{ old('core_version', $tenant->core_version) }}" class="mt-1 w-full rounded-lg border px-3 py-2"></label>
                    <label class="block text-sm"><span class="font-semibold">URL del entorno</span><input name="environment_url" value="{{ old('environment_url', $tenant->environment_url) }}" class="mt-1 w-full rounded-lg border px-3 py-2"></label>
                    <label class="block text-sm"><span class="font-semibold">Notas visibles</span><textarea name="delivery_notes" rows="3" class="mt-1 w-full rounded-lg border px-3 py-2">{{ old('delivery_notes', $tenant->delivery_notes) }}</textarea></label>
                    <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Guardar entrega</button>
                </form>
            </section>
            <section class="rounded-2xl border border-amber-200 bg-amber-50 p-6 text-sm text-amber-900">
                <h3 class="font-semibold">Límite de plataforma</h3>
                <p class="mt-2 leading-6">El superadmin configura el tenant, no opera ventas ni stock del comercio.</p>
            </section>
        </aside>
    </div>
@endsection
