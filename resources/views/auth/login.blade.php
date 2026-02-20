@extends('layouts.app')

@section('title', 'Iniciar Sesión - Inventario Inteligente')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-100 p-4" x-data="{ loading: false, error: '' }">
    <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-6 sm:p-8">
        <h2 class="text-2xl sm:text-3xl font-bold text-center mb-6 sm:mb-8 text-gray-800">
            Inventario Inteligente
        </h2>
        <form @submit.prevent="handleLogin" method="POST" action="{{ route('login') }}">
            @csrf
            <div x-show="error" x-cloak class="mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded text-sm">
                <span x-text="error"></span>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">
                    Email
                </label>
                <input
                    type="email"
                    name="email"
                    x-model="email"
                    value="admin@inventario.com"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-base"
                    required
                >
            </div>
            
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2">
                    Contraseña
                </label>
                <input
                    type="password"
                    name="password"
                    x-model="password"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-base"
                    required
                >
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

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('login', () => ({
        email: 'admin@inventario.com',
        password: '',
        loading: false,
        error: '',
        
        async handleLogin() {
            this.loading = true;
            this.error = '';
            
            try {
                const response = await axios.post('/api/login', {
                    email: this.email,
                    password: this.password
                });
                
                if (response.data.token) {
                    localStorage.setItem('token', response.data.token);
                    axios.defaults.headers.common['Authorization'] = `Bearer ${response.data.token}`;
                    window.location.href = '/';
                }
            } catch (err) {
                this.error = err.response?.data?.message || err.response?.data?.error?.email?.[0] || 'Error al iniciar sesión';
            } finally {
                this.loading = false;
            }
        }
    }));
});
</script>
@endpush
@endsection
