@extends('layouts.app')

@section('title', 'Iniciar Sesión - Inventario Inteligente')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-100 p-4" 
     x-data="{ loading: false }">
    <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-6 sm:p-8">
        <h2 class="text-2xl sm:text-3xl font-bold text-center mb-6 sm:mb-8 text-gray-800">
            Inventario Inteligente
        </h2>
        
        @if ($errors->any())
            <div class="mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded text-sm">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif
        
        <form method="POST" action="{{ route('login') }}" @submit="loading = true">
            @csrf
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">
                    Email
                </label>
                <input
                    type="email"
                    name="email"
                    value="{{ old('email', 'admin@inventario.com') }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-base @error('email') border-red-500 @enderror"
                    required
                    autofocus
                >
                @error('email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2">
                    Contraseña
                </label>
                <input
                    type="password"
                    name="password"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-base @error('password') border-red-500 @enderror"
                    required
                >
                @error('password')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            
            <div class="mb-4">
                <label class="flex items-center">
                    <input type="checkbox" name="remember" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    <span class="ml-2 text-sm text-gray-600">Recordarme</span>
                </label>
            </div>
            
            <button
                type="submit"
                :disabled="loading"
                class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 text-base"
            >
                <span x-show="!loading">Iniciar Sesión</span>
                <span x-show="loading" x-cloak>Iniciando sesión...</span>
            </button>
        </form>
    </div>
</div>
@endsection
