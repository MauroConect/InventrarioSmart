<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Inventario Inteligente')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
    @stack('styles')
</head>
<body class="bg-gray-100">
    @auth
        <div class="min-h-screen flex" x-data="{ sidebarOpen: window.innerWidth >= 1024 }" x-init="window.addEventListener('resize', () => { if (window.innerWidth >= 1024) sidebarOpen = true; else sidebarOpen = false; })">
            <!-- Sidebar -->
            <aside 
                x-show="sidebarOpen" 
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="-translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transition ease-in duration-300"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="-translate-x-full"
                class="fixed lg:static inset-y-0 left-0 z-50 w-64 bg-gray-800 text-white flex flex-col"
                x-cloak
            >
                <div class="flex items-center justify-between h-16 px-6 border-b border-gray-700">
                    <h1 class="text-xl font-bold">Inventario</h1>
                    <button @click="sidebarOpen = false" class="lg:hidden text-gray-400 hover:text-white">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <nav class="flex-1 overflow-y-auto py-4">
                    <a href="{{ route('dashboard') }}" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-700 {{ request()->routeIs('dashboard') ? 'bg-gray-700' : '' }}">
                        <span class="mr-3">📊</span> Dashboard
                    </a>
                    <a href="{{ route('categorias.index') }}" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-700 {{ request()->routeIs('categorias.*') ? 'bg-gray-700' : '' }}">
                        <span class="mr-3">📁</span> Categorías
                    </a>
                    <a href="{{ route('productos.index') }}" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-700 {{ request()->routeIs('productos.*') ? 'bg-gray-700' : '' }}">
                        <span class="mr-3">📦</span> Productos
                    </a>
                    <a href="{{ route('aumento-masivo.index') }}" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-700 {{ request()->routeIs('aumento-masivo.*') ? 'bg-gray-700' : '' }}">
                        <span class="mr-3">📈</span> Aumento Masivo
                    </a>
                    <a href="{{ route('proveedores.index') }}" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-700 {{ request()->routeIs('proveedores.*') ? 'bg-gray-700' : '' }}">
                        <span class="mr-3">🚚</span> Proveedores
                    </a>
                    <a href="{{ route('clientes.index') }}" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-700 {{ request()->routeIs('clientes.*') ? 'bg-gray-700' : '' }}">
                        <span class="mr-3">👥</span> Clientes
                    </a>
                    <a href="{{ route('cajas.index') }}" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-700 {{ request()->routeIs('cajas.*') ? 'bg-gray-700' : '' }}">
                        <span class="mr-3">💰</span> Cajas
                    </a>
                    <a href="{{ route('cuentas-corrientes.index') }}" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-700 {{ request()->routeIs('cuentas-corrientes.*') ? 'bg-gray-700' : '' }}">
                        <span class="mr-3">💳</span> Cuentas Corrientes
                    </a>
                    <a href="{{ route('deudas-clientes.index') }}" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-700 {{ request()->routeIs('deudas-clientes.*') ? 'bg-gray-700' : '' }}">
                        <span class="mr-3">📋</span> Deudas
                    </a>
                    <a href="{{ route('movimientos-stock.index') }}" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-700 {{ request()->routeIs('movimientos-stock.*') ? 'bg-gray-700' : '' }}">
                        <span class="mr-3">📊</span> Stock
                    </a>
                    <a href="{{ route('ventas.index') }}" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-700 {{ request()->routeIs('ventas.*') ? 'bg-gray-700' : '' }}">
                        <span class="mr-3">🛒</span> Ventas
                    </a>
                    <a href="{{ route('cheques.index') }}" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-700 {{ request()->routeIs('cheques.*') ? 'bg-gray-700' : '' }}">
                        <span class="mr-3">💵</span> Cheques
                    </a>
                </nav>
                
                <div class="border-t border-gray-700 p-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm text-gray-400">{{ Auth::user()->name }}</span>
                    </div>
                    <form action="{{ route('logout') }}" method="POST" class="w-full">
                        @csrf
                        <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white py-2 px-4 rounded text-sm">
                            Cerrar Sesión
                        </button>
                    </form>
                </div>
            </aside>

            <!-- Overlay para móvil -->
            <div 
                x-show="sidebarOpen" 
                @click="sidebarOpen = false"
                x-transition:enter="transition-opacity ease-linear duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition-opacity ease-linear duration-300"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-gray-600 bg-opacity-75 z-40 lg:hidden"
                x-cloak
            ></div>

            <!-- Main Content -->
            <div class="flex-1 flex flex-col lg:ml-0">
                <!-- Header -->
                <header class="bg-white shadow-sm h-16 flex items-center px-4 lg:px-6">
                    <button @click="sidebarOpen = true" class="lg:hidden text-gray-600 hover:text-gray-900 mr-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    <h2 class="text-xl font-semibold text-gray-800">@yield('page-title', 'Inventario Inteligente')</h2>
                </header>

                <!-- Page Content -->
                <main class="flex-1 overflow-y-auto p-4 lg:p-6">
                    @if(session('success'))
                        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                            {{ session('error') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                            <ul class="list-disc list-inside">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @yield('content')
                </main>
            </div>
        </div>
    @else
        @yield('content')
    @endauth

    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script>
        // Configurar Axios para usar CSRF token y cookies
        axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        axios.defaults.headers.common['Accept'] = 'application/json';
        axios.defaults.headers.common['Content-Type'] = 'application/json';
        axios.defaults.withCredentials = true;
    </script>
    @stack('scripts')
</body>
</html>
