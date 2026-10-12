@extends('onboarding.layout')
@section('title', 'Personalizá tu sistema')
@section('onboarding-content')
<h1 class="text-3xl font-black">Dale identidad a tu sistema</h1>
<p class="mt-2 text-slate-500">Estos datos aparecerán en la navegación y los tickets.</p>
<form method="POST" action="{{ route('onboarding.store', 'branding') }}" enctype="multipart/form-data" class="mt-8 grid gap-5 md:grid-cols-2">
    @csrf
    <label class="md:col-span-2"><span class="text-sm font-semibold">Nombre visible</span><input name="nombre_comercio" value="{{ old('nombre_comercio', $commerce->nombre_comercio ?? $tenant->name) }}" required class="mt-1 w-full rounded-lg border px-3 py-2"></label>
    <label class="md:col-span-2"><span class="text-sm font-semibold">Slogan</span><input name="slogan" value="{{ old('slogan', $commerce->slogan ?? '') }}" class="mt-1 w-full rounded-lg border px-3 py-2"></label>
    <label><span class="text-sm font-semibold">Color principal</span><input type="color" name="color_primario" value="{{ old('color_primario', $commerce->color_primario ?? '#2563eb') }}" class="mt-1 h-11 w-full rounded-lg border"></label>
    <label><span class="text-sm font-semibold">Color lateral</span><input type="color" name="color_sidebar" value="{{ old('color_sidebar', $commerce->color_sidebar ?? '#111827') }}" class="mt-1 h-11 w-full rounded-lg border"></label>
    <label class="md:col-span-2"><span class="text-sm font-semibold">Logo</span><input type="file" name="logo" accept=".jpg,.jpeg,.png,.svg,.webp" class="mt-1 w-full rounded-lg border px-3 py-2"></label>
    <label class="md:col-span-2"><span class="text-sm font-semibold">Mensaje del ticket</span><textarea name="mensaje_ticket" rows="2" class="mt-1 w-full rounded-lg border px-3 py-2">{{ old('mensaje_ticket', $commerce->mensaje_ticket ?? 'Gracias por tu compra') }}</textarea></label>
    <label class="md:col-span-2"><span class="text-sm font-semibold">Pie del ticket</span><textarea name="mensaje_footer" rows="2" class="mt-1 w-full rounded-lg border px-3 py-2">{{ old('mensaje_footer', $commerce->mensaje_footer ?? '') }}</textarea></label>
    <div class="md:col-span-2 flex justify-between"><a href="{{ route('onboarding.step', 'modules') }}" class="px-4 py-3 text-slate-500">Volver</a><button class="rounded-lg bg-blue-600 px-6 py-3 font-bold text-white">Continuar</button></div>
</form>
@endsection
