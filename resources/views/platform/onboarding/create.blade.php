@extends('layouts.platform')

@section('title', 'Onboarding')
@section('page-title', 'Onboarding de comercio')

@section('content')
    <form
        method="POST"
        action="{{ url('/platform/onboarding') }}"
        class="mx-auto max-w-5xl space-y-6"
        x-data="{
            name: @js(old('name', '')),
            slug: @js(old('slug', '')),
            slugTouched: {{ old('slug') ? 'true' : 'false' }},
            makeSlug() {
                if (this.slugTouched) return;
                this.slug = this.name.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
            }
        }"
    >
        @csrf

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-6">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan-700">Paso 1</p>
                <h3 class="mt-1 text-xl font-bold">Datos del comercio</h3>
                <p class="mt-1 text-sm text-slate-500">Crea el tenant activo; no provisiona cobros ni datos operativos.</p>
            </div>
            <div class="grid gap-5 sm:grid-cols-2">
                <label class="block">
                    <span class="text-sm font-semibold">Nombre</span>
                    <input name="name" x-model="name" @input="makeSlug()" required maxlength="255" class="mt-2 w-full rounded-lg border-slate-300 px-3 py-2 focus:border-cyan-500 focus:ring-cyan-500">
                </label>
                <label class="block">
                    <span class="text-sm font-semibold">Slug</span>
                    <input name="slug" x-model="slug" @input="slugTouched = true" required maxlength="100" pattern="[a-z0-9]+(?:-[a-z0-9]+)*" class="mt-2 w-full rounded-lg border-slate-300 px-3 py-2 font-mono focus:border-cyan-500 focus:ring-cyan-500">
                </label>
                <label class="block">
                    <span class="text-sm font-semibold">Perfil de rubro</span>
                    <input name="rubro_profile" value="{{ old('rubro_profile', 'base') }}" required maxlength="100" class="mt-2 w-full rounded-lg border-slate-300 px-3 py-2 focus:border-cyan-500 focus:ring-cyan-500">
                </label>
                <label class="block">
                    <span class="text-sm font-semibold">Zona horaria</span>
                    <input name="timezone" value="{{ old('timezone', $defaultTimezone) }}" required list="platform-timezones" class="mt-2 w-full rounded-lg border-slate-300 px-3 py-2 focus:border-cyan-500 focus:ring-cyan-500">
                    <datalist id="platform-timezones">
                        <option value="America/Argentina/Buenos_Aires">
                        <option value="America/Montevideo">
                        <option value="America/Santiago">
                        <option value="UTC">
                    </datalist>
                </label>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-6">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan-700">Paso 2</p>
                <h3 class="mt-1 text-xl font-bold">Administrador inicial</h3>
                <p class="mt-1 text-sm text-slate-500">Si el email ya existe, se reutiliza la identidad global y no se cambia su contraseña.</p>
            </div>
            <div class="grid gap-5 sm:grid-cols-2">
                <label class="block">
                    <span class="text-sm font-semibold">Nombre</span>
                    <input name="owner_name" value="{{ old('owner_name') }}" maxlength="255" autocomplete="name" class="mt-2 w-full rounded-lg border-slate-300 px-3 py-2 focus:border-cyan-500 focus:ring-cyan-500">
                </label>
                <label class="block">
                    <span class="text-sm font-semibold">Email</span>
                    <input type="email" name="owner_email" value="{{ old('owner_email') }}" required maxlength="255" autocomplete="email" class="mt-2 w-full rounded-lg border-slate-300 px-3 py-2 focus:border-cyan-500 focus:ring-cyan-500">
                </label>
                <label class="block sm:col-span-2">
                    <span class="text-sm font-semibold">Contraseña inicial</span>
                    <input type="password" name="owner_password" minlength="8" maxlength="72" autocomplete="new-password" class="mt-2 w-full rounded-lg border-slate-300 px-3 py-2 focus:border-cyan-500 focus:ring-cyan-500">
                    <span class="mt-1 block text-xs text-slate-500">Obligatoria sólo cuando el email todavía no pertenece a una identidad.</span>
                </label>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-6">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan-700">Paso 3</p>
                <h3 class="mt-1 text-xl font-bold">Grants iniciales</h3>
            </div>
            @php
                $defaultModules = $modules->where('default_enabled', true)->where('is_ready', true)->pluck('key')->all();
                $selectedModules = old('modules', $defaultModules);
            @endphp
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($modules as $module)
                    <label class="flex gap-3 rounded-xl border border-slate-200 p-4 {{ $module->is_ready ? 'cursor-pointer hover:border-cyan-300 hover:bg-cyan-50/50' : 'cursor-not-allowed bg-slate-50 opacity-60' }}">
                        <input type="checkbox" name="modules[]" value="{{ $module->key }}" @checked(in_array($module->key, $selectedModules, true)) @disabled(! $module->is_ready) class="mt-1 rounded border-slate-300 text-cyan-600 focus:ring-cyan-500">
                        <span>
                            <span class="block text-sm font-semibold">{{ $module->name }}</span>
                            <span class="mt-1 block text-xs leading-5 text-slate-500">{{ $module->description }}</span>
                            @if (! $module->is_ready)
                                <span class="mt-2 inline-block rounded-full bg-slate-200 px-2 py-0.5 text-[10px] font-semibold uppercase text-slate-600">Próximamente</span>
                            @endif
                        </span>
                    </label>
                @endforeach
            </div>
        </section>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ url('/platform/tenants') }}" class="rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold hover:bg-slate-50">Cancelar</a>
            <button class="rounded-lg bg-cyan-500 px-5 py-2.5 text-sm font-semibold text-slate-950 hover:bg-cyan-400">Crear comercio</button>
        </div>
    </form>
@endsection
