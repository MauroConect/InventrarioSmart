@extends('marketing.layout')
@section('title', 'InventarioSmart — Tu comercio, más simple')
@section('content')
@php
    $startHref = !empty($publicLanding) ? '#configurable' : route('register');
@endphp
<main class="marketing-home">
    <section class="hero section-wrap">
        <div class="hero-copy">
            <span class="eyebrow"><span class="status-dot"></span> Gestión para comercios y servicios</span>
            <h1>Tu negocio en marcha.<br><span>Todo bajo control.</span></h1>
            <p class="hero-description">Vendé, organizá tu stock y llevá la caja desde un mismo lugar. Activá las funciones que usa tu comercio y construí una forma de trabajar más ordenada, con un equipo que te acompaña.</p>
            <div class="hero-actions"><a href="{{ $startHref }}" class="button button-primary">{{ !empty($publicLanding) ? 'Explorá tu configuración' : 'Contanos tu comercio' }} <span aria-hidden="true">↗</span></a><a href="#producto" class="button button-secondary">Conocé el sistema <span aria-hidden="true">↓</span></a></div>
            <div class="hero-details"><span>✓ Configuración acompañada</span><span>✓ Módulos según tu rubro</span></div>
        </div>
        <div class="hero-visual">
            <div class="hero-photo-frame"><img src="{{ asset('images/marketing/comercio-almacen.webp') }}" alt="Atención en el mostrador de un almacén de barrio" width="1200" height="800" fetchpriority="high" class="hero-photo"><div class="photo-caption"><span class="status-dot"></span> Para el movimiento de todos los días</div></div>
            <a href="#producto" class="hero-screen" aria-label="Ver las pantallas de InventarioSmart"><div class="screen-bar"><span class="screen-dots" aria-hidden="true">● ● ●</span><span>InventarioSmart · Tu comercio</span><span aria-hidden="true">↗</span></div><img src="{{ asset('images/marketing/pantalla-dashboard.png') }}" alt="Dashboard de InventarioSmart con indicadores de ejemplo" width="1440" height="950"></a>
            <div class="hero-note"><span class="note-icon" aria-hidden="true">✓</span><div><strong>Tu operación, conectada</strong><span>Ventas · Stock · Caja</span></div></div>
        </div>
    </section>
    <div class="benefits-strip section-wrap" aria-label="Beneficios del sistema">
        <div><span class="benefit-icon" aria-hidden="true">↗</span><p><strong>Vendé con agilidad</strong><span>Productos, cobros y tickets.</span></p></div>
        <div><span class="benefit-icon" aria-hidden="true">▦</span><p><strong>Sabé qué tenés</strong><span>Catálogo y movimientos de stock.</span></p></div>
        <div><span class="benefit-icon" aria-hidden="true">◎</span><p><strong>Entendé tu día</strong><span>Ventas y caja a la vista.</span></p></div>
    </div>
    <section id="producto" class="product-section section-wrap">
        <div class="section-heading"><span class="eyebrow">Una mirada por dentro</span><h2>Así se ve trabajar<br>con InventarioSmart.</h2><p>Menos planillas sueltas. Más claridad para saber qué pasa en tu comercio.</p></div>
        @php
            $screens = [
                ['key'=>'productos','label'=>'Productos y stock','title'=>'Tu catálogo, ordenado.','description'=>'Buscá productos, consultá precios y revisá las existencias. Identificá lo que necesita reposición antes de que falte en el mostrador.','image'=>asset('images/marketing/pantalla-productos.png'),'alt'=>'Pantalla real del catálogo con productos, precios y stock de ejemplo','points'=>['Búsqueda por nombre o código','Precios y cantidades en una misma vista','Categorías para organizar el catálogo']],
                ['key'=>'ventas','label'=>'Ventas','title'=>'Cada venta, en su lugar.','description'=>'Consultá las ventas, su estado y los medios de pago. Desde esta pantalla también iniciás una nueva operación.','image'=>asset('images/marketing/pantalla-ventas.png'),'alt'=>'Pantalla real de ventas con operaciones y medios de pago de ejemplo','points'=>['Historial de operaciones','Importes y medios de pago visibles','Detalle de cada venta']],
                ['key'=>'dashboard','label'=>'Resumen del negocio','title'=>'Una vista para empezar el día.','description'=>'Revisá la caja, las ventas y los productos desde el dashboard. Los indicadores te ayudan a decidir dónde poner la atención.','image'=>asset('images/marketing/pantalla-dashboard.png'),'alt'=>'Dashboard real con indicadores y gráficos de ejemplo','points'=>['Ventas del día y del mes','Productos con stock bajo','Resumen de la caja']],
                ['key'=>'modulos','label'=>'Configuración','title'=>'Tus funciones, a un clic.','description'=>'Desde Ajustes → Módulos, activás o desactivás funciones opcionales. La base del sistema queda siempre incluida para que puedas seguir vendiendo, controlando el stock y trabajando con tu caja.','image'=>asset('images/marketing/pantalla-modulos.png'),'alt'=>'Captura real del panel Módulos en Ajustes, con cuatro funciones base incluidas y módulos opcionales','points'=>['Catálogo, stock, POS y caja incluidos','Funciones opcionales según tu operación','Una configuración propia para cada comercio']],
            ];
            if (!empty($publicLanding)) {
                foreach ($screens as &$screen) {
                    $screen['image'] = parse_url($screen['image'], PHP_URL_PATH);
                }
                unset($screen);
            }
        @endphp
        <div class="product-showcase" x-data="{ active: 0, zoom: false, screens: @js($screens) }" @keydown.escape.window="if (zoom) { zoom = false; $refs.zoomButton.focus(); }" x-effect="document.body.style.overflow = zoom ? 'hidden' : ''">
            <div class="screen-tabs" role="tablist" aria-label="Pantallas del sistema">
                @foreach($screens as $index => $screen)
                <button type="button" id="tab-{{ $screen['key'] }}" role="tab" aria-controls="screen-panel" :aria-selected="active === {{ $index }}" :tabindex="active === {{ $index }} ? 0 : -1" :class="{ 'is-active': active === {{ $index }} }" @click="active = {{ $index }}" @keydown.arrow-right.prevent="active = (active + 1) % screens.length; $el.parentElement.children[active].focus()" @keydown.arrow-left.prevent="active = (active + screens.length - 1) % screens.length; $el.parentElement.children[active].focus()">{{ $screen['label'] }}</button>
                @endforeach
            </div>
            <div id="screen-panel" role="tabpanel" :aria-labelledby="'tab-' + screens[active].key" class="screen-panel">
                <div class="screen-description"><span class="mini-label">El sistema en acción</span><h3 x-text="screens[active].title">Tu catálogo, ordenado.</h3><p x-text="screens[active].description">Buscá productos, consultá precios y revisá las existencias.</p><ul><template x-for="point in screens[active].points" :key="point"><li><span aria-hidden="true">✓</span><span x-text="point"></span></li></template></ul><button type="button" class="text-link" x-ref="zoomButton" @click="zoom = true; $nextTick(() => $refs.closeZoom.focus())">Ampliar pantalla <span aria-hidden="true">↗</span></button></div>
                <div class="product-screen-frame"><div class="screen-bar"><span class="screen-dots" aria-hidden="true">● ● ●</span><span x-text="screens[active].label">Productos y stock</span><span class="demo-tag">Vista de ejemplo</span></div><img src="{{ asset('images/marketing/pantalla-productos.png') }}" :src="screens[active].image" :alt="screens[active].alt" width="1440" height="950" loading="lazy"></div>
            </div>
            <p class="capture-caption">Capturas del sistema con datos ilustrativos. Los módulos disponibles dependen de la configuración de tu comercio.</p>
            <div class="screen-lightbox" x-show="zoom" x-cloak role="dialog" aria-modal="true" aria-label="Pantalla ampliada del sistema" @click.self="zoom = false; $refs.zoomButton.focus()" @keydown.tab.prevent="$refs.closeZoom.focus()">
                <div class="lightbox-content"><button type="button" x-ref="closeZoom" class="lightbox-close" @click="zoom = false; $refs.zoomButton.focus()" aria-label="Cerrar pantalla ampliada">Cerrar <span aria-hidden="true">×</span></button><img :src="screens[active].image" :alt="screens[active].alt" width="1440" height="950"><p>InventarioSmart · Datos ilustrativos</p></div>
            </div>
        </div>
    </section>
    <section id="como-se-usa" class="workflow-section"><div class="section-wrap">
        <div class="section-heading"><span class="eyebrow">En el día a día</span><h2>De abrir el comercio<br>a cerrar la caja.</h2><p>Un recorrido simple para acompañar tu forma de trabajar.</p></div>
        <div class="workflow-grid">
            @foreach([
                ['01','Prepará tus productos','Organizá el catálogo, definí los precios y consultá el stock disponible.','Catálogo listo','Productos · Precios · Existencias','▦'],
                ['02','Vendé y cobrá','Buscá o escaneá el producto cuando el módulo esté habilitado, armá la venta y elegí el medio de pago.','Una nueva venta','Productos · Cobro · Ticket','↗'],
                ['03','Revisá y cerrá','Consultá los movimientos, revisá las ventas de la jornada y realizá el cierre de caja.','El día, a la vista','Ventas · Movimientos · Cierre','◎'],
            ] as [$number,$title,$text,$example,$details,$icon])
            <article><span class="step-number">{{ $number }}</span><h3>{{ $title }}</h3><p>{{ $text }}</p><div class="workflow-example"><span class="example-icon" aria-hidden="true">{{ $icon }}</span><div><strong>{{ $example }}</strong><span>{{ $details }}</span></div><span class="example-check" aria-hidden="true">✓</span></div></article>
            @endforeach
        </div>
    </div></section>
    <section id="rubros" class="business-section section-wrap">
        <div class="section-heading"><span class="eyebrow">Distintas formas de trabajar</span><h2>Para negocios como el tuyo.</h2><p>Un mismo producto base. Distintas combinaciones de funciones para acompañar cómo trabaja tu equipo.</p></div>
        <div class="business-grid">
            @foreach([
                ['image'=>'comercio-almacen.webp','alt'=>'Almacén de barrio con atención en el mostrador','tag'=>'Comercio y retail','title'=>'Del almacén al polirubro.','text'=>'Catálogo, ventas de mostrador, scanner y control de stock para el movimiento cotidiano.','features'=>['Punto de venta','Stock','Scanner']],
                ['image'=>'comercio-heladeria.webp','alt'=>'Atención en el mostrador de una heladería','tag'=>'Heladería y gastronomía','title'=>'Pedidos que siguen abiertos.','text'=>'Cargá productos, seguí sumando al pedido y cobrá al cerrar. También hay soporte para productos pesables.','features'=>['Ventas abiertas','Pesables','Tickets']],
                ['image'=>'comercio-servicios.webp','alt'=>'Recepción de un comercio de servicios con agenda en una computadora','tag'=>'Servicios y agenda','title'=>'Un lugar para cada turno.','text'=>'Organizá citas, consultá la agenda y registrá el estado de atención desde el módulo de turnos.','features'=>['Agenda','Turnos','Estados']],
            ] as $business)
            <article class="business-card"><div class="business-photo"><img src="{{ asset('images/marketing/'.$business['image']) }}" alt="{{ $business['alt'] }}" width="1200" height="800" loading="lazy"><span>{{ $business['tag'] }}</span></div><div class="business-copy"><h3>{{ $business['title'] }}</h3><p>{{ $business['text'] }}</p><div class="feature-tags">@foreach($business['features'] as $feature)<span>{{ $feature }}</span>@endforeach</div></div></article>
            @endforeach
        </div>
    </section>
    <section id="configurable" class="configuration-section section-wrap">
        <div class="configuration-story">
            <div class="configuration-photo"><img src="{{ asset('images/marketing/equipo-configurando.webp') }}" alt="Dos personas organizando la operación de su comercio con una computadora" width="1200" height="800" loading="lazy"><span class="configuration-photo-caption">Tu equipo. Tus procesos. Tus herramientas.</span></div>
            <div class="configuration-copy"><span class="eyebrow">Configurable desde Ajustes</span><h2>Elegís las funciones.<br>El sistema se adapta.</h2><p>No todos los comercios necesitan lo mismo. Desde <strong>Ajustes → Módulos</strong>, quien administra tu comercio puede activar o desactivar funciones opcionales.</p><ol class="configuration-steps"><li><span>01</span><div><h3>Una base que siempre está</h3><p>Catálogo, stock, POS de mostrador y caja quedan incluidos y activos.</p></div></li><li><span>02</span><div><h3>Sumá lo que usás</h3><p>Scanner, pesables, comandas, turnos o herramientas financieras: elegí los módulos disponibles que tienen sentido para tu operación.</p></div></li><li><span>03</span><div><h3>Adaptalo a tu momento</h3><p>Al guardar los cambios, el menú y las funciones disponibles acompañan la configuración de tu comercio.</p></div></li></ol></div>
        </div>
        <div class="configuration-demo" x-data="moduleConfigurator(@js($marketingOptionalModules))">
            <div class="configuration-demo-heading"><div><span class="mini-label">Probá una combinación</span><h3>¿Cómo trabaja tu comercio?</h3></div><span class="configuration-example-tag">Ejemplo interactivo</span></div>
            <div class="configuration-profiles" role="group" aria-label="Ejemplos por tipo de comercio"><template x-for="profile in profiles" :key="profile.key"><button type="button" :aria-pressed="activeProfile === profile.key" :class="{ 'is-active': activeProfile === profile.key }" @click="chooseProfile(profile.key)"><span x-text="profile.name"></span></button></template></div>
            <div class="configuration-demo-grid">
                <div class="configuration-options"><h4>Producto base · Siempre incluido</h4><div class="configuration-base">@foreach($marketingBaseModules as $module)<span><span aria-hidden="true">✓</span>{{ $module['name'] }}</span>@endforeach</div><h4>Funciones opcionales</h4><div class="configuration-toggles"><template x-for="module in modules" :key="module.key"><button type="button" role="switch" :aria-checked="isEnabled(module.key)" :aria-label="module.name" :class="{ 'is-enabled': isEnabled(module.key) }" @click="toggleModule(module.key)"><span class="configuration-toggle-name" x-text="module.name"></span><span class="configuration-switch" aria-hidden="true"><span></span></span></button></template></div></div>
                <div class="configuration-preview"><img :src="profileImage" src="{{ asset('images/marketing/comercio-almacen.webp') }}" alt="Personas trabajando en un comercio del rubro seleccionado" width="1200" height="800" loading="lazy"><div class="configuration-preview-copy"><span class="mini-label">Tu operación, más simple</span><h4 x-text="profileTitle">Un mostrador con todo a mano.</h4><p x-text="profileDescription">Ventas, existencias y caja en una misma rutina.</p><div class="configuration-selected" aria-live="polite"><span class="configuration-base-label">Base incluida</span><template x-for="module in selectedModules" :key="module.key"><span x-text="module.name"></span></template></div><p class="configuration-count" x-text="selected.length === 0 ? 'Tu base, lista para trabajar.' : selected.length + (selected.length === 1 ? ' función opcional seleccionada.' : ' funciones opcionales seleccionadas.')"></p></div></div>
            </div>
            <div class="configuration-demo-footer"><p>Este ejemplo muestra combinaciones posibles. La configuración real se guarda desde Ajustes en tu comercio.</p><button type="button" class="text-link" @click="chooseProfile(activeProfile)">Restablecer ejemplo <span aria-hidden="true">↺</span></button></div>
        </div>
    </section>
    <section id="modulos" class="modules-section section-wrap"><div><span class="eyebrow">Crece con tu operación</span><h2>El núcleo que necesitás.<br>Los módulos que te sirven.</h2><p>Partimos de catálogo, stock, ventas y caja. Después evaluamos qué sumar según tu rubro y tus procesos.</p></div><div class="module-pills">@foreach(['Scanner y códigos','Productos pesables','Ventas abiertas','Turnos y agenda','Cuenta corriente','Cheques','AFIP / ARCA','Soporte'] as $module)<span><span aria-hidden="true">＋</span>{{ $module }}</span>@endforeach</div></section>
    <section id="a-medida" class="custom-section section-wrap"><div class="custom-visual" aria-hidden="true"><div class="custom-core">IS<span>Tu producto base</span></div><div class="custom-chip chip-one">Tus procesos</div><div class="custom-chip chip-two">Tus integraciones</div><div class="custom-chip chip-three">Tu forma de trabajar</div></div><div><span class="eyebrow">También, a tu medida</span><h2>Tu negocio tiene su forma.<br>La herramienta puede acompañarla.</h2><p>Además de los módulos configurables, podemos relevar un flujo particular, un reporte o una integración para tu operación. Definimos juntos el alcance antes de desarrollar una extensión.</p><a href="{{ $startHref }}" class="text-link">{{ !empty($publicLanding) ? 'Explorá las funciones disponibles' : 'Contanos qué necesitás' }} <span aria-hidden="true">↗</span></a></div></section>
    <section id="como-trabajamos" class="onboarding-section section-wrap"><div class="section-heading"><span class="eyebrow">No tenés que hacerlo solo</span><h2>Te acompañamos desde el comienzo.</h2></div><ol class="onboarding-grid">@foreach([['01','Nos contás','Conocemos tu comercio, tus procesos y lo que necesitás resolver.'],['02','Te proponemos','Definimos qué cubre el sistema y qué módulos conviene sumar.'],['03','Lo configuramos','Preparamos el catálogo, los usuarios y la operación con vos.'],['04','Seguimos cerca','Acompañamos el uso y evaluamos nuevas necesidades.']] as [$number,$title,$text])<li><span>{{ $number }}</span><h3>{{ $title }}</h3><p>{{ $text }}</p></li>@endforeach</ol></section>
    <section class="cta-section section-wrap"><span class="eyebrow">Una herramienta para tu forma de trabajar</span><h2>Dale más espacio a tu negocio.<br>Y menos a las tareas sueltas.</h2><p>Una base para organizar la operación. Las funciones que necesitás para acompañar el crecimiento de tu comercio.</p><a href="{{ $startHref }}" class="button button-primary">{{ !empty($publicLanding) ? 'Encontrá tu combinación' : 'Empezar el relevamiento' }} <span aria-hidden="true">↗</span></a></section>
</main>
<script src="{{ asset('js/marketing-configurator.js') }}"></script>
@endsection
