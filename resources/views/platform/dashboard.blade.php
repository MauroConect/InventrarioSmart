@extends('layouts.platform')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard de plataforma')

@section('actions')
    <a href="{{ url('/platform/onboarding') }}" class="rounded-lg bg-cyan-500 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-cyan-400">
        Nuevo comercio
    </a>
@endsection

@section('content')
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        @foreach ([
            ['label' => 'Comercios', 'value' => $metrics['tenants'], 'tone' => 'text-slate-900'],
            ['label' => 'Activos', 'value' => $metrics['activeTenants'], 'tone' => 'text-emerald-600'],
            ['label' => 'Suspendidos', 'value' => $metrics['suspendedTenants'], 'tone' => 'text-rose-600'],
            ['label' => 'Membresías', 'value' => $metrics['memberships'], 'tone' => 'text-indigo-600'],
            ['label' => 'Eventos hoy', 'value' => $metrics['eventsToday'], 'tone' => 'text-cyan-600'],
        ] as $metric)
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">{{ $metric['label'] }}</p>
                <p class="mt-2 text-3xl font-bold {{ $metric['tone'] }}">{{ number_format($metric['value']) }}</p>
            </article>
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-5">
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm xl:col-span-3">
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                <h3 class="font-semibold">Comercios recientes</h3>
                <a href="{{ url('/platform/tenants') }}" class="text-sm font-semibold text-cyan-700 hover:text-cyan-900">Ver todos</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-6 py-3">Comercio</th>
                            <th class="px-6 py-3">Estado</th>
                            <th class="px-6 py-3 text-right">Usuarios</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($recentTenants as $tenant)
                            <tr class="hover:bg-slate-50">
                                <td class="px-6 py-4">
                                    <a href="{{ url('/platform/tenants/'.$tenant->getRouteKey()) }}" class="font-semibold text-slate-900 hover:text-cyan-700">{{ $tenant->name }}</a>
                                    <p class="text-xs text-slate-500">{{ $tenant->slug }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $tenant->status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                        {{ $tenant->status === 'active' ? 'Activo' : 'Suspendido' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right font-medium">{{ $tenant->users_count }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-6 py-10 text-center text-slate-500">Todavía no hay comercios.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm xl:col-span-2">
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                <h3 class="font-semibold">Actividad reciente</h3>
                <a href="{{ url('/platform/audit') }}" class="text-sm font-semibold text-cyan-700 hover:text-cyan-900">Auditoría</a>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse ($recentEvents as $event)
                    <div class="px-6 py-4">
                        <p class="break-words text-sm font-semibold text-slate-800">{{ $event->action }}</p>
                        <p class="mt-1 text-xs text-slate-500">
                            {{ $event->tenant?->name ?? 'Plataforma' }} · {{ $event->actor?->name ?? 'Sistema' }}
                        </p>
                        <time class="mt-1 block text-xs text-slate-400">{{ $event->created_at?->format('d/m/Y H:i') }}</time>
                    </div>
                @empty
                    <p class="px-6 py-10 text-center text-sm text-slate-500">Sin actividad registrada.</p>
                @endforelse
            </div>
        </section>
    </div>
@endsection
