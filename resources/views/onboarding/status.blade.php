@extends('onboarding.layout')
@section('title', 'Estado de tu comercio')
@section('onboarding-content')
<h1 class="text-3xl font-black">Estamos armando tu sistema</h1>
<p class="mt-2 text-slate-500">InventarioSmart es multi-tenant: tu comercio queda aislado de los demás. El equipo configura módulos, marca y, si hace falta, un repositorio privado para features exclusivas.</p>

<div class="mt-8 rounded-2xl bg-blue-50 p-6">
    <p class="text-sm font-semibold uppercase tracking-wide text-blue-700">{{ config('onboarding.statuses.'.$onboarding->status, $onboarding->status) }}</p>
    <p class="mt-2 text-lg text-slate-800">{{ $onboarding->customer_message ?: config('onboarding.customer_copy.'.$onboarding->status) }}</p>
    @if($onboarding->next_action)
        <p class="mt-4 text-sm text-slate-600"><strong>Próximo paso:</strong> {{ $onboarding->next_action }}</p>
    @endif
    @if($onboarding->assignee)
        <p class="mt-1 text-sm text-slate-600"><strong>Responsable:</strong> {{ $onboarding->assignee->name }}</p>
    @endif
</div>

<dl class="mt-8 grid gap-4 rounded-xl bg-slate-50 p-6 md:grid-cols-2">
    <div><dt class="text-sm text-slate-500">Comercio</dt><dd class="font-bold">{{ $answers['business_name'] ?? '—' }}</dd></div>
    <div><dt class="text-sm text-slate-500">Rubro</dt><dd class="font-bold">{{ $answers['rubro'] ?? '—' }}</dd></div>
    <div><dt class="text-sm text-slate-500">Ventas mensuales</dt><dd class="font-bold">{{ $answers['monthly_sales'] ?? '—' }}</dd></div>
    <div><dt class="text-sm text-slate-500">Tenant</dt><dd class="font-bold">{{ $onboarding->tenant?->name ?? 'Todavía no asignado' }}</dd></div>
</dl>

@if($onboarding->tenant?->core_version || $onboarding->tenant?->delivery_notes)
    <section class="mt-8 rounded-xl border p-6">
        <h2 class="font-bold">Entrega</h2>
        <p class="mt-2 text-sm text-slate-600">Versión: {{ $onboarding->tenant->core_version ?: 'en preparación' }}</p>
        @if($onboarding->tenant->delivery_notes)<p class="mt-2 text-sm text-slate-600">{{ $onboarding->tenant->delivery_notes }}</p>@endif
    </section>
@endif

@if($publicNotes->isNotEmpty())
    <section class="mt-8">
        <h2 class="font-bold">Mensajes del equipo</h2>
        <ul class="mt-3 space-y-3">
            @foreach($publicNotes as $note)
                <li class="rounded-lg bg-slate-50 p-4 text-sm"><strong>{{ $note->author?->name }}</strong> · {{ $note->created_at?->format('d/m H:i') }}<p class="mt-1">{{ $note->body }}</p></li>
            @endforeach
        </ul>
    </section>
@endif

<form method="POST" action="{{ route('logout') }}" class="mt-8">@csrf<button class="text-sm text-slate-500 underline">Cerrar sesión</button></form>
@endsection
