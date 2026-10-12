<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Plataforma') · InventarioSmart</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>[x-cloak] { display: none !important; }</style>
    @stack('styles')
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 antialiased">
    <div x-data="{ sidebarOpen: false }" class="min-h-screen lg:flex">
        <div
            x-cloak
            x-show="sidebarOpen"
            x-transition.opacity
            class="fixed inset-0 z-40 bg-slate-950/60 lg:hidden"
            @click="sidebarOpen = false"
        ></div>

        <aside
            x-cloak
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col bg-slate-950 text-white transition-transform duration-200 lg:static lg:translate-x-0"
        >
            <div class="border-b border-white/10 px-6 py-6">
                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-cyan-400">InventarioSmart</p>
                <h1 class="mt-2 text-xl font-bold">Plataforma SaaS</h1>
            </div>

            <nav class="flex-1 space-y-1 overflow-y-auto px-4 py-6">
                <a href="{{ url('/platform') }}" class="block rounded-lg px-4 py-3 text-sm font-medium {{ request()->is('platform') ? 'bg-cyan-500 text-slate-950' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                    Dashboard
                </a>
                <a href="{{ url('/platform/tenants') }}" class="block rounded-lg px-4 py-3 text-sm font-medium {{ request()->is('platform/tenants*') ? 'bg-cyan-500 text-slate-950' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                    Comercios
                </a>
                <a href="{{ url('/platform/modules') }}" class="block rounded-lg px-4 py-3 text-sm font-medium {{ request()->is('platform/modules*') ? 'bg-cyan-500 text-slate-950' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                    Módulos y grants
                </a>
                <a href="{{ url('/platform/onboarding') }}" class="block rounded-lg px-4 py-3 text-sm font-medium {{ request()->is('platform/onboarding*') ? 'bg-cyan-500 text-slate-950' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                    Onboarding
                </a>
                <a href="{{ url('/platform/support') }}" class="block rounded-lg px-4 py-3 text-sm font-medium {{ request()->is('platform/support*') ? 'bg-cyan-500 text-slate-950' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                    Soporte
                </a>
                <a href="{{ url('/platform/audit') }}" class="block rounded-lg px-4 py-3 text-sm font-medium {{ request()->is('platform/audit*') ? 'bg-cyan-500 text-slate-950' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                    Auditoría
                </a>
            </nav>

            <div class="border-t border-white/10 p-4">
                <p class="truncate px-2 text-sm font-medium">{{ auth()->user()?->name }}</p>
                <p class="mb-3 px-2 text-xs text-slate-400">Superadministración</p>
                <form method="POST" action="{{ url('/logout') }}">
                    @csrf
                    <button class="w-full rounded-lg border border-white/10 px-4 py-2 text-left text-sm text-slate-300 hover:bg-white/10 hover:text-white">
                        Cerrar sesión
                    </button>
                </form>
            </div>
        </aside>

        <div class="min-w-0 flex-1">
            <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur">
                <div class="flex min-h-16 items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                    <div class="flex min-w-0 items-center gap-3">
                        <button type="button" class="rounded-lg p-2 text-slate-600 hover:bg-slate-100 lg:hidden" @click="sidebarOpen = true" aria-label="Abrir menú">
                            <span class="block h-0.5 w-5 bg-current"></span>
                            <span class="mt-1 block h-0.5 w-5 bg-current"></span>
                            <span class="mt-1 block h-0.5 w-5 bg-current"></span>
                        </button>
                        <h2 class="truncate text-lg font-semibold">@yield('page-title', 'Plataforma')</h2>
                    </div>
                    <div class="flex items-center gap-3">@yield('actions')</div>
                </div>
            </header>

            <main class="p-4 sm:p-6 lg:p-8">
                @if (session('success'))
                    <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('status'))
                    <div class="mb-6 rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                        <p class="font-semibold">Revisá los datos ingresados:</p>
                        <ul class="mt-2 list-inside list-disc">
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
    @stack('scripts')
</body>
</html>
