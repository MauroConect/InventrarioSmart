@extends('marketing.layout')

@section('title', 'Verificá tu email — InventarioSmart')

@section('content')
<main class="mx-auto max-w-lg px-6 py-20 text-center">
    <div class="rounded-2xl bg-white p-8 text-slate-900 shadow-2xl">
        <h1 class="text-2xl font-black">Revisá tu correo</h1>
        <p class="mt-3 text-slate-600">Enviamos un enlace de verificación a <strong>{{ auth()->user()->email }}</strong>. Al verificarlo vas a poder configurar tu comercio.</p>
        @if(session('success'))<p class="mt-4 rounded-lg bg-green-50 p-3 text-sm text-green-700">{{ session('success') }}</p>@endif
        <form method="POST" action="{{ route('verification.send') }}" class="mt-6">@csrf<button class="rounded-lg bg-blue-600 px-5 py-3 font-bold text-white">Reenviar email</button></form>
        <form method="POST" action="{{ route('logout') }}" class="mt-4">@csrf<button class="text-sm text-slate-500 underline">Cerrar sesión</button></form>
    </div>
</main>
@endsection
