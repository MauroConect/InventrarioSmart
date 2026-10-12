@extends('layouts.platform')

@section('title', 'Módulos y grants')
@section('page-title', 'Módulos y grants')

@section('content')
    <div class="mb-6 rounded-2xl border border-cyan-200 bg-cyan-50 p-5 text-sm text-cyan-950">
        Los grants habilitan capacidades por comercio. No reemplazan permisos de usuario ni migran automáticamente datos operativos.
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <form method="GET" action="{{ url('/platform/modules') }}" class="flex gap-3 border-b border-slate-200 p-4">
            <input
                type="search"
                name="search"
                value="{{ $filters['search'] ?? '' }}"
                placeholder="Buscar comercio"
                class="min-w-0 flex-1 rounded-lg border-slate-300 px-3 py-2 text-sm focus:border-cyan-500 focus:ring-cyan-500"
            >
            <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Buscar</button>
        </form>

        <div class="divide-y divide-slate-200">
            @forelse ($tenants as $tenant)
                @php
                    $enabledKeys = $tenant->moduleGrants->where('enabled', true)->pluck('module_key')->all();
                    $enabledCount = count($enabledKeys);
                @endphp
                <form
                    method="POST"
                    action="{{ url('/platform/tenants/'.$tenant->getRouteKey().'/modules') }}"
                    class="p-6"
                    x-data="{ selected: {{ $enabledCount }} }"
                >
                    @csrf
                    @method('PUT')
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <a href="{{ url('/platform/tenants/'.$tenant->getRouteKey()) }}" class="text-lg font-bold hover:text-cyan-700">{{ $tenant->name }}</a>
                            <p class="text-sm text-slate-500">{{ $tenant->slug }} · <span x-text="selected"></span> módulos habilitados</p>
                        </div>
                        <button class="rounded-lg bg-cyan-500 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-cyan-400">Guardar grants</button>
                    </div>

                    <div class="mt-5 space-y-6">
                        @php
                            $catalogByKey = collect(config('modules.catalog'))->keyBy('key');
                            $grouped = $modules->groupBy(fn ($module) => $catalogByKey[$module->key]['family'] ?? 'base');
                        @endphp
                        @foreach (config('modules.families') as $familyKey => $familyName)
                            @continue(! $grouped->has($familyKey))
                            <div>
                                <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $familyName }}</p>
                                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                                    @foreach ($grouped[$familyKey] as $module)
                                        @php($enabled = in_array($module->key, $enabledKeys, true))
                                        <label class="flex gap-3 rounded-xl border p-4 transition {{ $module->is_ready ? 'cursor-pointer hover:border-cyan-300 hover:bg-cyan-50/50' : 'cursor-not-allowed bg-slate-50 opacity-60' }}">
                                            <input
                                                type="checkbox"
                                                name="modules[]"
                                                value="{{ $module->key }}"
                                                @checked($enabled)
                                                @disabled(! $module->is_ready)
                                                @change="selected += $event.target.checked ? 1 : -1"
                                                class="mt-1 rounded border-slate-300 text-cyan-600 focus:ring-cyan-500"
                                            >
                                            <span>
                                                <span class="block text-sm font-semibold">{{ $module->name }}</span>
                                                <span class="mt-1 block text-xs leading-5 text-slate-500">{{ $module->description }}</span>
                                                @if (! $module->is_ready)
                                                    <span class="mt-2 inline-block rounded-full bg-slate-200 px-2 py-0.5 text-[10px] font-semibold uppercase text-slate-600">Próximamente</span>
                                                @elseif ($module->depends_on)
                                                    <span class="mt-2 block text-[10px] text-slate-400">Requiere: {{ implode(', ', $module->depends_on) }}</span>
                                                @endif
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </form>
            @empty
                <p class="px-6 py-12 text-center text-slate-500">No se encontraron comercios.</p>
            @endforelse
        </div>

        @if ($tenants->hasPages())
            <div class="border-t border-slate-200 px-6 py-4">{{ $tenants->links() }}</div>
        @endif
    </section>
@endsection
