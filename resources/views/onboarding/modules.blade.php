@extends('onboarding.layout')
@section('title', 'Elegí tus módulos')
@section('onboarding-content')
<h1 class="text-3xl font-black">¿Qué necesitás gestionar?</h1>
<p class="mt-2 text-slate-500">Podés cambiar estos módulos más adelante. Los módulos base ya están seleccionados.</p>
<form method="POST" action="{{ route('onboarding.store', 'modules') }}" class="mt-8">
    @csrf
    <div class="grid gap-4 md:grid-cols-2">
        @foreach($modules as $module)
            <label class="flex gap-3 rounded-xl border p-4 {{ $module->is_ready ? 'cursor-pointer hover:border-blue-400' : 'bg-slate-50 opacity-60' }}">
                <input type="checkbox" name="modules[]" value="{{ $module->key }}" @checked($module->is_ready && in_array($module->key, old('modules', $selectedModules), true)) @disabled(!$module->is_ready) class="mt-1 h-5 w-5">
                <span><strong class="block">{{ $module->name }} @if(!$module->is_ready)<small class="ml-2 rounded bg-slate-200 px-2 py-1 text-xs">Próximamente</small>@endif</strong><span class="text-sm text-slate-500">{{ $module->description }}</span>@if($module->depends_on)<small class="mt-1 block text-slate-400">Requiere: {{ implode(', ', $module->depends_on) }}</small>@endif</span>
            </label>
        @endforeach
    </div>
    <div class="mt-8 flex justify-between"><a href="{{ route('onboarding.step', 'profile') }}" class="px-4 py-3 text-slate-500">Volver</a><button class="rounded-lg bg-blue-600 px-6 py-3 font-bold text-white">Continuar</button></div>
</form>
@endsection
