@extends('marketing.layout')

@section('title', 'Crear cuenta — InventarioSmart')

@section('content')
<main class="mx-auto max-w-md px-6 py-16">
    <div class="rounded-2xl bg-white p-8 text-slate-900 shadow-2xl">
        <h1 class="text-2xl font-black">Creá tu cuenta</h1>
        <p class="mt-2 text-sm text-slate-500">Después contanos cómo trabaja tu comercio. Nosotros armamos la propuesta: modelo base y, si hace falta, desarrollo a medida.</p>

        @if ($errors->any())
            <div class="mt-4 rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ $errors->first() }}</div>
        @endif
        @if (session('status'))
            <div class="mt-4 rounded-lg bg-blue-50 p-3 text-sm text-blue-700">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('register.store') }}" class="mt-6 space-y-4">
            @csrf
            <label class="block"><span class="text-sm font-semibold">Nombre</span><input name="name" value="{{ old('name') }}" required autofocus class="mt-1 w-full rounded-lg border px-3 py-2"></label>
            <label class="block"><span class="text-sm font-semibold">Email</span><input type="email" name="email" value="{{ old('email') }}" required class="mt-1 w-full rounded-lg border px-3 py-2"></label>
            <label class="block"><span class="text-sm font-semibold">Contraseña</span><input type="password" name="password" required class="mt-1 w-full rounded-lg border px-3 py-2"></label>
            <label class="block"><span class="text-sm font-semibold">Confirmar contraseña</span><input type="password" name="password_confirmation" required class="mt-1 w-full rounded-lg border px-3 py-2"></label>
            <button class="w-full rounded-lg bg-blue-600 px-4 py-3 font-bold text-white">Continuar</button>
        </form>
        <p class="mt-5 text-center text-sm text-slate-500">¿Ya tenés cuenta? <a href="{{ route('login') }}" class="font-semibold text-blue-600">Ingresá</a></p>
    </div>
</main>
@endsection
