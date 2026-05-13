<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#1d4ed8">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <title>@yield('title', 'El Cristo')</title>
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
                    <h1 class="text-xl font-bold">El Cristo</h1>
                    <button @click="sidebarOpen = false" class="lg:hidden text-gray-400 hover:text-white">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <nav class="flex-1 overflow-y-auto py-4">
                    @if(Auth::user()->hasPermission('dashboard.view'))
                        <a href="{{ route('dashboard') }}" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-700 {{ request()->routeIs('dashboard') ? 'bg-gray-700' : '' }}">
                            <span class="mr-3">📊</span> Dashboard
                        </a>
                    @endif
                    @if(Auth::user()->hasPermission('categorias.view'))
                        <a href="{{ route('categorias.index') }}" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-700 {{ request()->routeIs('categorias.*') ? 'bg-gray-700' : '' }}">
                            <span class="mr-3">📁</span> Categorías
                        </a>
                    @endif
                    @if(Auth::user()->hasPermission('productos.view'))
                        <a href="{{ route('productos.index') }}" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-700 {{ request()->routeIs('productos.*') ? 'bg-gray-700' : '' }}">
                            <span class="mr-3">📦</span> Productos
                        </a>
                    @endif
                    @if(Auth::user()->hasPermission('productos.manage'))
                        <a href="{{ route('aumento-masivo.index') }}" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-700 {{ request()->routeIs('aumento-masivo.*') ? 'bg-gray-700' : '' }}">
                            <span class="mr-3">📈</span> Aumento Masivo
                        </a>
                    @endif
                    @if(Auth::user()->hasPermission('proveedores.view'))
                        <a href="{{ route('proveedores.index') }}" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-700 {{ request()->routeIs('proveedores.*') ? 'bg-gray-700' : '' }}">
                            <span class="mr-3">🚚</span> Proveedores
                        </a>
                    @endif
                    @if(Auth::user()->hasPermission('clientes.view'))
                        <a href="{{ route('clientes.index') }}" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-700 {{ request()->routeIs('clientes.*') ? 'bg-gray-700' : '' }}">
                            <span class="mr-3">👥</span> Clientes
                        </a>
                    @endif
                    @if(Auth::user()->hasPermission('cajas.view'))
                        <a href="{{ route('cajas.index') }}" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-700 {{ request()->routeIs('cajas.index') ? 'bg-gray-700' : '' }}">
                            <span class="mr-3">💰</span> Cajas
                        </a>
                    @endif
                    @if(Auth::user()->hasPermission('cuentas_corrientes.view'))
                        <a href="{{ route('cuentas-corrientes.index') }}" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-700 {{ request()->routeIs('cuentas-corrientes.*') ? 'bg-gray-700' : '' }}">
                            <span class="mr-3">💳</span> Cuentas Corrientes
                        </a>
                    @endif
                    @if(Auth::user()->hasPermission('deudas.view'))
                        <a href="{{ route('deudas-clientes.index') }}" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-700 {{ request()->routeIs('deudas-clientes.*') ? 'bg-gray-700' : '' }}">
                            <span class="mr-3">📋</span> Deudas
                        </a>
                    @endif
                    @if(Auth::user()->hasPermission('stock.view'))
                        <a href="{{ route('movimientos-stock.index') }}" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-700 {{ request()->routeIs('movimientos-stock.*') ? 'bg-gray-700' : '' }}">
                            <span class="mr-3">📦</span> Stock
                        </a>
                    @endif
                    @if(Auth::user()->hasPermission('ventas.view'))
                        <a href="{{ route('ventas.index') }}" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-700 {{ request()->routeIs('ventas.*') ? 'bg-gray-700' : '' }}">
                            <span class="mr-3">🛒</span> Ventas
                        </a>
                    @endif
                    @if(Auth::user()->hasPermission('ventas.facturar'))
                        <a href="{{ route('facturacion.index') }}" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-700 {{ request()->routeIs('facturacion.*') ? 'bg-gray-700' : '' }}">
                            <span class="mr-3">🧾</span> Facturacion
                        </a>
                    @endif
                    @if(Auth::user()->hasPermission('cheques.view'))
                        <a href="{{ route('cheques.index') }}" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-700 {{ request()->routeIs('cheques.*') ? 'bg-gray-700' : '' }}">
                            <span class="mr-3">💵</span> Cheques
                        </a>
                    @endif
                    @if(Auth::user()->hasPermission('admin'))
                        <a href="{{ route('auditoria.index') }}" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-700 {{ request()->routeIs('auditoria.*') ? 'bg-gray-700' : '' }}">
                            <span class="mr-3">📜</span> Auditoría
                        </a>
                        <a href="{{ route('usuarios.index') }}" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-700 {{ request()->routeIs('usuarios.*') ? 'bg-gray-700' : '' }}">
                            <span class="mr-3">👤</span> Usuarios
                        </a>
                        <a href="{{ route('configuracion-fiscal.index') }}" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-700 {{ request()->routeIs('configuracion-fiscal.*') ? 'bg-gray-700' : '' }}">
                            <span class="mr-3">🧾</span> Configuracion Fiscal
                        </a>
                    @endif
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

            <div class="flex-1 flex flex-col lg:ml-0">
                <header class="bg-white shadow-sm h-16 flex items-center px-4 lg:px-6">
                    <button @click="sidebarOpen = true" class="lg:hidden text-gray-600 hover:text-gray-900 mr-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    <h2 class="text-xl font-semibold text-gray-800">@yield('page-title', 'El Cristo')</h2>
                </header>

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

    <script>
        (function () {
            if (!('serviceWorker' in navigator)) return;
            window.addEventListener('load', function () {
                navigator.serviceWorker.register(@json(asset('sw.js'))).catch(function () {});
            });
        })();
    </script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script>
        axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        axios.defaults.headers.common['Accept'] = 'application/json';
        axios.defaults.headers.common['Content-Type'] = 'application/json';
        axios.defaults.withCredentials = true;

        axios.interceptors.response.use(
            (r) => r,
            (error) => {
                const s = error.response?.status;
                if (s === 401) {
                    try {
                        localStorage.removeItem('token');
                    } catch (e) {}
                    delete axios.defaults.headers.common['Authorization'];
                }
                return Promise.reject(error);
            }
        );

        (function () {
            function isTypingTarget(el) {
                if (!el) return false;
                const tag = (el.tagName || '').toLowerCase();
                return tag === 'input' || tag === 'textarea' || tag === 'select' || el.isContentEditable;
            }

            window.addEventListener('keydown', function (event) {
                if (event.defaultPrevented || event.ctrlKey || event.altKey || event.metaKey) return;
                const key = (event.key || '').toLowerCase();
                if (isTypingTarget(document.activeElement)) return;

                const path = window.location.pathname || '';
                if (key === 'v') {
                    if (path === '/ventas') {
                        event.preventDefault();
                        window.dispatchEvent(new CustomEvent('shortcut-open-sale'));
                        return;
                    }

                    event.preventDefault();
                    window.location.href = '/ventas?nueva=1';
                    return;
                }

                if (key === 'n') {
                    if (path === '/productos') {
                        event.preventDefault();
                        window.dispatchEvent(new CustomEvent('shortcut-open-product'));
                        return;
                    }

                    event.preventDefault();
                    window.location.href = '/productos?nuevo=1';
                }
            });
        })();
    </script>
    <div id="pwa-install-wrap" class="fixed bottom-4 right-4 z-[100] flex flex-col items-end gap-2 max-w-[min(22rem,calc(100vw-2rem))]">
        <button type="button" id="pwa-install-btn" class="hidden shadow-lg rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2.5">
            Instalar aplicación (PWA)
        </button>
        <p id="pwa-install-tip" class="hidden text-xs text-gray-700 bg-white/95 border border-gray-200 rounded-lg px-3 py-2 shadow"></p>
    </div>
    <script>
        (function () {
            var btn = document.getElementById('pwa-install-btn');
            var tip = document.getElementById('pwa-install-tip');
            var deferred = null;

            function isStandalone() {
                return (
                    (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches) ||
                    window.navigator.standalone === true
                );
            }

            function insecureHttp() {
                if (location.protocol !== 'http:') return false;
                var h = location.hostname || '';
                return h !== 'localhost' && h !== '127.0.0.1';
            }

            if (!btn || isStandalone()) return;

            window.addEventListener('beforeinstallprompt', function (e) {
                e.preventDefault();
                deferred = e;
                btn.classList.remove('hidden');
                if (tip) {
                    tip.classList.remove('hidden');
                    tip.textContent =
                        'Instalá la app: Chrome guardará interfaz y últimas listas (Productos, APIs) para cuando se caiga el Wi‑Fi. Igual hay que abrir cada sección al menos una vez con Internet.';
                }
            });

            btn.addEventListener('click', function () {
                if (!deferred) return;
                deferred.prompt();
                deferred.userChoice.finally(function () {
                    deferred = null;
                    btn.classList.add('hidden');
                });
            });

            if (tip && 'serviceWorker' in navigator) {
                window.setTimeout(function () {
                    if (isStandalone() || deferred) return;
                    if (btn && !btn.classList.contains('hidden')) return;
                    if (insecureHttp()) return;
                    tip.classList.remove('hidden');
                    tip.textContent =
                        'Sin botón de instalar: en Chrome usá el menú ⋮ → «Instalar…» / «Crear acceso directo». Con Internet, entrá a Productos, Ventas y Stock una vez para que queden guardados en este equipo.';
                }, 8000);
            }
        })();
    </script>
    @stack('scripts')
</body>
</html>
