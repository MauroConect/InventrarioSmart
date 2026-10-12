@extends('onboarding.layout')
@section('title', 'Revisá tu configuración')
@section('onboarding-content')
<h1 class="text-3xl font-black">Todo listo para empezar</h1>
<p class="mt-2 text-slate-500">Podrás ajustar la mayoría de estas opciones desde el sistema.</p>
<dl class="mt-8 grid gap-4 rounded-xl bg-slate-50 p-6 md:grid-cols-2">
    <div><dt class="text-sm text-slate-500">Comercio</dt><dd class="font-bold">{{ $tenant->name }}</dd></div>
    <div><dt class="text-sm text-slate-500">Rubro</dt><dd class="font-bold">{{ $tenant->rubro_profile }}</dd></div>
    <div><dt class="text-sm text-slate-500">Módulos activos</dt><dd class="font-bold">{{ $tenant->moduleGrants()->where('enabled', true)->count() }}</dd></div>
    <div><dt class="text-sm text-slate-500">Dirección</dt><dd class="font-bold">{{ $tenant->domains()->where('is_primary', true)->value('host') }}</dd></div>
</dl>
<form method="POST" action="{{ route('onboarding.complete') }}" class="mt-8 flex justify-between">
    @csrf
    <a href="{{ route('onboarding.step', 'domain') }}" class="px-4 py-3 text-slate-500">Volver</a>
    <button class="rounded-lg bg-green-600 px-6 py-3 font-bold text-white">Activar mi comercio</button>
</form>
@endsection
