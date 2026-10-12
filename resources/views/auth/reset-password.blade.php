@extends('layouts.app')
@section('title', 'Nueva contraseña — InventarioSmart')
@section('content')
<main class="min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md rounded-xl bg-white p-8 shadow">
        <h1 class="text-2xl font-bold">Elegí una nueva contraseña</h1>
        <p class="mt-2 text-gray-600">Usá al menos 12 caracteres, mayúsculas, minúsculas y números.</p>
        <form action="{{ route('password.update') }}" method="POST" class="mt-6 space-y-4">
            @csrf<input type="hidden" name="token" value="{{ $token }}">
            <div><label for="email">Email</label><input id="email" name="email" type="email" value="{{ old('email', $email) }}" autocomplete="email" required class="mt-1 w-full rounded border p-3">@error('email')<p class="text-red-600">{{ $message }}</p>@enderror</div>
            <div><label for="password">Nueva contraseña</label><input id="password" name="password" type="password" autocomplete="new-password" required class="mt-1 w-full rounded border p-3">@error('password')<p class="text-red-600">{{ $message }}</p>@enderror</div>
            <div><label for="password_confirmation">Repetí la contraseña</label><input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required class="mt-1 w-full rounded border p-3"></div>
            <button class="w-full rounded bg-blue-600 p-3 text-white">Actualizar contraseña</button>
        </form>
        <a href="{{ route('password.request') }}" class="mt-4 block text-blue-600">Solicitar otro enlace</a>
    </div>
</main>
@endsection
