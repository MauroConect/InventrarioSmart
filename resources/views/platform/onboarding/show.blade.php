@extends('layouts.platform')

@section('title', 'Caso de onboarding')
@section('page-title', $onboarding->user?->name ?? 'Onboarding')

@section('content')
@php($answers = $answers ?? [])
<div class="grid gap-6 xl:grid-cols-3">
    <div class="space-y-6 xl:col-span-2">
        <section class="rounded-2xl border bg-white p-6 shadow-sm">
            <h3 class="font-semibold">Cuestionario</h3>
            <dl class="mt-4 grid gap-4 sm:grid-cols-2 text-sm">
                @foreach([
                    'business_name' => 'Comercio',
                    'locality' => 'Localidad',
                    'rubro' => 'Rubro',
                    'monthly_sales' => 'Ventas/mes',
                    'products_count' => 'Productos',
                    'cash_registers_count' => 'Cajas',
                    'users_count' => 'Usuarios',
                    'open_orders' => 'Pedidos abiertos',
                    'credit_accounts' => 'Crédito',
                    'cheques' => 'Cheques',
                    'electronic_invoicing' => 'Facturación',
                    'special_features' => 'Features especiales',
                ] as $key => $label)
                    <div>
                        <dt class="text-xs uppercase text-slate-500">{{ $label }}</dt>
                        <dd class="mt-1 font-medium">{{ is_array($answers[$key] ?? null) ? implode(', ', $answers[$key]) : ($answers[$key] ?? '—') }}</dd>
                    </div>
                @endforeach
            </dl>
            @if($onboarding->suggested_modules)
                <p class="mt-4 text-sm text-slate-600"><strong>Sugerencia automática:</strong> {{ implode(', ', $onboarding->suggested_modules) }}</p>
            @endif
        </section>

        @unless($onboarding->tenant)
            <section class="rounded-2xl border bg-white p-6 shadow-sm">
                <h3 class="font-semibold">Crear tenant multi-tenant</h3>
                <form method="POST" action="{{ url('/platform/onboarding/'.$onboarding->id.'/provision') }}" class="mt-4 grid gap-4 md:grid-cols-2">
                    @csrf
                    <label class="md:col-span-2 text-sm"><span class="font-semibold">Nombre</span><input name="name" value="{{ old('name', $answers['business_name'] ?? $onboarding->user?->name) }}" required class="mt-1 w-full rounded-lg border px-3 py-2"></label>
                    <label class="text-sm"><span class="font-semibold">Slug</span><input name="slug" value="{{ old('slug', $suggestedSlug) }}" required class="mt-1 w-full rounded-lg border px-3 py-2"></label>
                    <label class="text-sm"><span class="font-semibold">Perfil</span><input name="rubro_profile" value="{{ old('rubro_profile', $onboarding->suggested_profile ?? $answers['rubro'] ?? 'base') }}" required class="mt-1 w-full rounded-lg border px-3 py-2"></label>
                    <label class="md:col-span-2 text-sm"><span class="font-semibold">Zona horaria</span><input name="timezone" value="America/Argentina/Buenos_Aires" required class="mt-1 w-full rounded-lg border px-3 py-2"></label>
                    <label class="md:col-span-2 text-sm"><span class="font-semibold">Repo privado de features</span><input name="repository_url" value="{{ old('repository_url') }}" class="mt-1 w-full rounded-lg border px-3 py-2"></label>
                    <div class="md:col-span-2 grid gap-2 sm:grid-cols-2">
                        @foreach($modules as $module)
                            <label class="flex gap-2 rounded-lg border p-3 text-sm">
                                <input type="checkbox" name="modules[]" value="{{ $module->key }}" @checked(in_array($module->key, old('modules', $onboarding->suggested_modules ?? []), true))>
                                <span>{{ $module->name }}</span>
                            </label>
                        @endforeach
                    </div>
                    <div class="md:col-span-2"><button class="rounded-lg bg-cyan-600 px-4 py-2 font-semibold text-white">Provisionar tenant</button></div>
                </form>
            </section>
        @endunless

        <section class="rounded-2xl border bg-white p-6 shadow-sm">
            <h3 class="font-semibold">Notas</h3>
            <form method="POST" action="{{ url('/platform/onboarding/'.$onboarding->id.'/notes') }}" class="mt-4 space-y-3">
                @csrf
                <textarea name="body" required rows="3" class="w-full rounded-lg border px-3 py-2"></textarea>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_internal" value="1" checked> Nota interna</label>
                <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Agregar nota</button>
            </form>
            <ul class="mt-4 space-y-3 text-sm">
                @foreach($onboarding->notes as $note)
                    <li class="rounded-lg bg-slate-50 p-3"><strong>{{ $note->author?->name }}</strong> {{ $note->is_internal ? '(interna)' : '(cliente)' }}<p class="mt-1">{{ $note->body }}</p></li>
                @endforeach
            </ul>
        </section>
    </div>

    <aside class="space-y-6">
        <section class="rounded-2xl border bg-white p-6 shadow-sm">
            <h3 class="font-semibold">Cambiar estado</h3>
            <form method="POST" action="{{ url('/platform/onboarding/'.$onboarding->id.'/status') }}" class="mt-4 space-y-3">
                @csrf
                @method('PATCH')
                <select name="status" class="w-full rounded-lg border px-3 py-2">
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" @selected($onboarding->status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <input name="next_action" value="{{ $onboarding->next_action }}" placeholder="Próxima acción" class="w-full rounded-lg border px-3 py-2">
                <textarea name="customer_message" rows="3" class="w-full rounded-lg border px-3 py-2" placeholder="Mensaje visible para el cliente">{{ $onboarding->customer_message }}</textarea>
                <textarea name="comment" rows="2" class="w-full rounded-lg border px-3 py-2" placeholder="Comentario interno"></textarea>
                <button class="w-full rounded-lg bg-cyan-600 px-4 py-2 font-semibold text-white">Actualizar</button>
            </form>
        </section>
        <section class="rounded-2xl border bg-white p-6 text-sm shadow-sm">
            <h3 class="font-semibold">Historial</h3>
            <ul class="mt-3 space-y-2">
                @foreach($onboarding->events as $event)
                    <li>{{ $event->from_status }} → {{ $event->to_status }}<br><span class="text-xs text-slate-500">{{ $event->comment }}</span></li>
                @endforeach
            </ul>
        </section>
    </aside>
</div>
@endsection
