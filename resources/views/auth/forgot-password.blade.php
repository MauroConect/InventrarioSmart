@extends('layouts.app')
@section('title', 'Recuperar acceso — InventarioSmart')
@section('content')
<main class="min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md rounded-xl bg-white p-8 shadow">
        <h1 class="text-2xl font-bold">Recuperá tu acceso</h1>
        <p class="mt-2 text-gray-600">Ingresá tu email. Si tenés una cuenta, te enviaremos un enlace válido por 60 minutos.</p>
        @if(session('status'))<p role="status" class="mt-4 rounded bg-blue-50 p-3 text-blue-800">{{ session('status') }}</p>@endif
        <form action="{{ route('password.email') }}" method="POST" class="mt-6 space-y-4">
            @csrf
            <div><label for="email" class="block font-medium">Email</label><input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required class="mt-1 w-full rounded border p-3">@error('email')<p class="text-red-600">{{ $message }}</p>@enderror</div>
            <button class="w-full rounded bg-blue-600 p-3 text-white">Enviar enlace</button>
        </form>
        <a href="{{ route('login') }}" class="mt-4 block text-blue-600">Volver a ingresar</a>
    </div>
</main>
@endsection
