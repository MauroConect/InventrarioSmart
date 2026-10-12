@php
    $isPublicLanding = $publicLanding ?? false;
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @unless($isPublicLanding)<meta name="csrf-token" content="{{ csrf_token() }}">@endunless
    <meta name="description" content="InventarioSmart: ventas, stock y caja para tu comercio. Activá módulos desde Ajustes, adaptá funciones a tu rubro y organizá el trabajo de tu equipo.">
    <title>@yield('title', 'InventarioSmart')</title>
    @unless($isPublicLanding)<script src="https://cdn.tailwindcss.com"></script>@endunless
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="{{ asset('css/marketing.css') }}?v=20261012">
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 antialiased marketing-shell">
    <a class="skip-link" href="#main-content">Ir al contenido</a>
    <header class="marketing-header" x-data="{ menuOpen: false }" @keydown.escape.window="menuOpen = false">
        <nav class="section-wrap main-nav" aria-label="Navegación principal">
            <a href="{{ route('landing') }}" class="brand"><span class="brand-mark" aria-hidden="true">IS</span><span>Inventario<span class="brand-light">Smart</span></span></a>
            <div class="desktop-nav"><a href="{{ route('landing') }}#producto">El sistema</a><a href="{{ route('landing') }}#rubros">Tu rubro</a><a href="{{ route('landing') }}#configurable">Configurable</a><a href="{{ route('landing') }}#a-medida">A medida</a></div>
            <div class="nav-actions">@if($isPublicLanding)<a href="#configurable" class="button button-primary button-small">Explorar <span aria-hidden="true">↗</span></a>@else @auth<a href="{{ route('home') }}" class="button button-primary button-small">Ir al sistema <span aria-hidden="true">↗</span></a>@else<a href="{{ route('login') }}" class="login-link">Ingresar</a><a href="{{ route('register') }}" class="button button-primary button-small">Empezar <span aria-hidden="true">↗</span></a>@endauth @endif<button type="button" class="mobile-menu-toggle" @click="menuOpen = !menuOpen" :aria-expanded="menuOpen" aria-controls="mobile-nav" aria-label="Abrir o cerrar navegación"><span aria-hidden="true" x-text="menuOpen ? '×' : '☰'">☰</span></button></div>
        </nav>
        <div id="mobile-nav" class="mobile-nav" x-show="menuOpen" x-cloak @click="menuOpen = false"><a href="{{ route('landing') }}#producto">El sistema</a><a href="{{ route('landing') }}#como-se-usa">Cómo se usa</a><a href="{{ route('landing') }}#rubros">Tu rubro</a><a href="{{ route('landing') }}#configurable">Configurable</a><a href="{{ route('landing') }}#a-medida">A medida</a></div>
    </header>
    <div id="main-content">@yield('content')</div>
    <footer class="marketing-footer"><div class="section-wrap footer-grid"><div><a href="{{ route('landing') }}" class="brand"><span class="brand-mark" aria-hidden="true">IS</span><span>Inventario<span class="brand-light">Smart</span></span></a><p>La gestión de tu comercio, en un mismo lugar.<br>Un producto base y un equipo para acompañarte.</p></div><div><strong>Conocé el producto</strong><a href="{{ route('landing') }}#producto">Pantallas del sistema</a><a href="{{ route('landing') }}#como-se-usa">Cómo se usa</a><a href="{{ route('landing') }}#modulos">Módulos disponibles</a></div><div><strong>{{ $isPublicLanding ? 'Tu forma de trabajar' : 'Hablemos de tu comercio' }}</strong><a href="{{ $isPublicLanding ? '#configurable' : route('register') }}">{{ $isPublicLanding ? 'Explorá una configuración ↗' : 'Empezar el relevamiento ↗' }}</a><a href="{{ route('landing') }}#como-trabajamos">Cómo te acompañamos</a>@if($isPublicLanding)<a href="#a-medida">Extensiones a medida</a>@else<a href="{{ route('login') }}">Ingresar al sistema</a>@endif</div></div><div class="section-wrap footer-bottom"><span>InventarioSmart · Gestión para comercios</span><span>Producto base + desarrollo a medida</span></div></footer>
</body>
</html>
