@extends('onboarding.layout')
@section('title', 'Elegí tu acceso')
@section('onboarding-content')
<h1 class="text-3xl font-black">Elegí la dirección de tu comercio</h1>
<p class="mt-2 text-slate-500">Los dominios propios estarán disponibles en una etapa posterior.</p>
<form method="POST" action="{{ route('onboarding.store', 'domain') }}" class="mt-8" x-data="{ subdomain: @js(old('subdomain', $tenant->slug)) }">
    @csrf
    <label class="block"><span class="text-sm font-semibold">Subdominio</span><div class="mt-1 flex"><input name="subdomain" x-model="subdomain" required class="min-w-0 flex-1 rounded-l-lg border px-3 py-2"><span class="rounded-r-lg border border-l-0 bg-slate-100 px-3 py-2 text-slate-500">.{{ config('tenancy.base_domain') }}</span></div></label>
    <div class="mt-8 flex justify-between"><a href="{{ route('onboarding.step', 'branding') }}" class="px-4 py-3 text-slate-500">Volver</a><button class="rounded-lg bg-blue-600 px-6 py-3 font-bold text-white">Revisar configuración</button></div>
</form>
@endsection
