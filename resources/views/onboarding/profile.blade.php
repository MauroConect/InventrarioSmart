@extends('onboarding.layout')
@section('title', 'Configurá tu negocio')
@section('onboarding-content')
<h1 class="text-3xl font-black">Contanos sobre tu negocio</h1>
<p class="mt-2 text-slate-500">Esto define el perfil inicial y la dirección de acceso.</p>
<form method="POST" action="{{ route('onboarding.store', 'profile') }}" class="mt-8 grid gap-5 md:grid-cols-2" x-data="{ name: @js(old('name', $tenant?->name ?? '')), slug: @js(old('slug', $tenant?->slug ?? '')) }">
    @csrf
    <label class="md:col-span-2"><span class="text-sm font-semibold">Nombre del comercio</span><input name="name" x-model="name" @input="if(!slug) slug = name.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'').replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'')" required class="mt-1 w-full rounded-lg border px-3 py-2"></label>
    <label><span class="text-sm font-semibold">Identificador</span><input name="slug" x-model="slug" required class="mt-1 w-full rounded-lg border px-3 py-2"><small class="text-slate-500" x-text="slug + '.{{ config('tenancy.base_domain') }}'"></small></label>
    <label><span class="text-sm font-semibold">Rubro</span><select name="rubro_profile" class="mt-1 w-full rounded-lg border px-3 py-2">@foreach(['base'=>'General','retail'=>'Comercio / retail','heladeria'=>'Heladería','gastronomia'=>'Gastronomía','servicios'=>'Servicios'] as $value=>$label)<option value="{{ $value }}" @selected(old('rubro_profile', $tenant?->rubro_profile) === $value)>{{ $label }}</option>@endforeach</select></label>
    <label class="md:col-span-2"><span class="text-sm font-semibold">Zona horaria</span><select name="timezone" class="mt-1 w-full rounded-lg border px-3 py-2"><option value="America/Argentina/Buenos_Aires">Argentina</option><option value="UTC">UTC</option></select></label>
    <div class="md:col-span-2 flex justify-end"><button class="rounded-lg bg-blue-600 px-6 py-3 font-bold text-white">Continuar</button></div>
</form>
@endsection
