@extends('layouts.platform')

@section('title', 'Auditoría')
@section('page-title', 'Auditoría de plataforma')

@section('content')
    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <form method="GET" action="{{ url('/platform/audit') }}" class="grid gap-3 border-b border-slate-200 p-4 md:grid-cols-2 xl:grid-cols-6">
            <select name="tenant_id" class="rounded-lg border-slate-300 px-3 py-2 text-sm focus:border-cyan-500 focus:ring-cyan-500">
                <option value="">Todos los comercios</option>
                @foreach ($tenants as $tenant)
                    <option value="{{ $tenant->id }}" @selected((string) ($filters['tenant_id'] ?? '') === (string) $tenant->id)>{{ $tenant->name }}</option>
                @endforeach
            </select>
            <select name="action" class="rounded-lg border-slate-300 px-3 py-2 text-sm focus:border-cyan-500 focus:ring-cyan-500">
                <option value="">Todas las acciones</option>
                @foreach ($actions as $action)
                    <option value="{{ $action }}" @selected(($filters['action'] ?? '') === $action)>{{ $action }}</option>
                @endforeach
            </select>
            <input type="text" name="correlation_id" value="{{ $filters['correlation_id'] ?? '' }}" placeholder="Correlation UUID" class="rounded-lg border-slate-300 px-3 py-2 text-sm focus:border-cyan-500 focus:ring-cyan-500">
            <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" aria-label="Desde" class="rounded-lg border-slate-300 px-3 py-2 text-sm focus:border-cyan-500 focus:ring-cyan-500">
            <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" aria-label="Hasta" class="rounded-lg border-slate-300 px-3 py-2 text-sm focus:border-cyan-500 focus:ring-cyan-500">
            <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Filtrar</button>
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-6 py-3">Fecha</th>
                        <th class="px-6 py-3">Acción</th>
                        <th class="px-6 py-3">Comercio</th>
                        <th class="px-6 py-3">Actor</th>
                        <th class="px-6 py-3">Objeto</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($events as $event)
                        <tr x-data="{ open: false }" class="align-top">
                            <td class="whitespace-nowrap px-6 py-4 text-slate-500">{{ $event->created_at?->format('d/m/Y H:i:s') }}</td>
                            <td class="px-6 py-4 font-semibold">{{ $event->action }}</td>
                            <td class="px-6 py-4">{{ $event->tenant?->name ?? ($event->tenant_id ? '#'.$event->tenant_id : 'Plataforma') }}</td>
                            <td class="px-6 py-4">{{ $event->actor?->name ?? ($event->actor_user_id ? '#'.$event->actor_user_id : 'Sistema') }}</td>
                            <td class="px-6 py-4 text-xs text-slate-500">
                                <span class="block max-w-48 truncate">{{ $event->subject_type ?? '—' }}</span>
                                @if ($event->subject_id)<span>#{{ $event->subject_id }}</span>@endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button type="button" @click="open = !open" class="font-semibold text-cyan-700 hover:text-cyan-900">Detalle</button>
                                <div
                                    x-cloak
                                    x-show="open"
                                    @click.outside="open = false"
                                    class="fixed inset-x-4 top-20 z-50 mx-auto max-w-3xl rounded-2xl border border-slate-200 bg-white p-6 text-left shadow-2xl"
                                >
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <p class="font-bold">{{ $event->action }}</p>
                                            <p class="mt-1 font-mono text-xs text-slate-500">{{ $event->event_uuid }}</p>
                                        </div>
                                        <button type="button" @click="open = false" class="rounded-lg px-3 py-1 text-slate-500 hover:bg-slate-100">Cerrar</button>
                                    </div>
                                    <dl class="mt-5 grid gap-3 text-xs sm:grid-cols-2">
                                        <div><dt class="font-semibold text-slate-500">Correlation ID</dt><dd class="mt-1 break-all font-mono">{{ $event->correlation_id }}</dd></div>
                                        <div><dt class="font-semibold text-slate-500">IP</dt><dd class="mt-1">{{ $event->ip_address ?? '—' }}</dd></div>
                                    </dl>
                                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                                        <div>
                                            <p class="mb-2 text-xs font-semibold uppercase text-slate-500">Antes</p>
                                            <pre class="max-h-72 overflow-auto rounded-xl bg-slate-950 p-4 text-xs text-slate-200">{{ json_encode($event->before, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                        </div>
                                        <div>
                                            <p class="mb-2 text-xs font-semibold uppercase text-slate-500">Después</p>
                                            <pre class="max-h-72 overflow-auto rounded-xl bg-slate-950 p-4 text-xs text-slate-200">{{ json_encode($event->after, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                        </div>
                                    </div>
                                    @if ($event->metadata)
                                        <div class="mt-4">
                                            <p class="mb-2 text-xs font-semibold uppercase text-slate-500">Metadatos</p>
                                            <pre class="max-h-40 overflow-auto rounded-xl bg-slate-100 p-4 text-xs text-slate-700">{{ json_encode($event->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-6 py-12 text-center text-slate-500">No hay eventos para los filtros seleccionados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($events->hasPages())
            <div class="border-t border-slate-200 px-6 py-4">{{ $events->links() }}</div>
        @endif
    </section>
@endsection
