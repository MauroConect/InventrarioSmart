@extends('layouts.platform')

@section('title', 'Comercios')
@section('page-title', 'Comercios')

@section('actions')
    <a href="{{ url('/platform/onboarding') }}" class="rounded-lg bg-cyan-500 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-cyan-400">
        Nuevo comercio
    </a>
@endsection

@section('content')
    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <form method="GET" action="{{ url('/platform/tenants') }}" class="grid gap-3 border-b border-slate-200 p-4 sm:grid-cols-[minmax(0,1fr)_12rem_auto]">
            <input
                type="search"
                name="search"
                value="{{ $filters['search'] ?? '' }}"
                placeholder="Nombre, slug o UUID"
                class="rounded-lg border-slate-300 px-3 py-2 text-sm focus:border-cyan-500 focus:ring-cyan-500"
            >
            <select name="status" class="rounded-lg border-slate-300 px-3 py-2 text-sm focus:border-cyan-500 focus:ring-cyan-500">
                <option value="">Todos los estados</option>
                <option value="active" @selected(($filters['status'] ?? '') === 'active')>Activos</option>
                <option value="suspended" @selected(($filters['status'] ?? '') === 'suspended')>Suspendidos</option>
            </select>
            <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Filtrar</button>
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-6 py-3">Comercio</th>
                        <th class="px-6 py-3">Perfil</th>
                        <th class="px-6 py-3">Estado</th>
                        <th class="px-6 py-3 text-right">Usuarios</th>
                        <th class="px-6 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($tenants as $tenant)
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-4">
                                <a href="{{ url('/platform/tenants/'.$tenant->getRouteKey()) }}" class="font-semibold text-slate-900 hover:text-cyan-700">{{ $tenant->name }}</a>
                                <p class="mt-0.5 text-xs text-slate-500">{{ $tenant->slug }} · {{ $tenant->uuid }}</p>
                            </td>
                            <td class="px-6 py-4 text-slate-600">{{ $tenant->rubro_profile }}</td>
                            <td class="px-6 py-4">
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $tenant->status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                    {{ $tenant->status === 'active' ? 'Activo' : 'Suspendido' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right font-medium">{{ $tenant->users_count }}</td>
                            <td class="px-6 py-4">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ url('/platform/tenants/'.$tenant->getRouteKey()) }}" class="rounded-lg border border-slate-300 px-3 py-1.5 font-medium hover:bg-slate-100">Detalle</a>
                                    @if ($tenant->status === 'active')
                                        <form method="POST" action="{{ url('/platform/tenants/'.$tenant->getRouteKey().'/suspend') }}" @submit.prevent="if (confirm('¿Suspender este comercio?')) $el.submit()">
                                            @csrf
                                            @method('PATCH')
                                            <button class="rounded-lg border border-rose-300 px-3 py-1.5 font-medium text-rose-700 hover:bg-rose-50">Suspender</button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ url('/platform/tenants/'.$tenant->getRouteKey().'/reactivate') }}">
                                            @csrf
                                            @method('PATCH')
                                            <button class="rounded-lg border border-emerald-300 px-3 py-1.5 font-medium text-emerald-700 hover:bg-emerald-50">Reactivar</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-6 py-12 text-center text-slate-500">No se encontraron comercios.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($tenants->hasPages())
            <div class="border-t border-slate-200 px-6 py-4">{{ $tenants->links() }}</div>
        @endif
    </section>
@endsection
