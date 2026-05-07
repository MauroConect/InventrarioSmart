/* InventarioInteligente — app shell y datos para uso sin conexión.
 * Bump SW_CACHE_VERSION al desplegar para forzar actualización de cachés.
 */
const SW_CACHE_VERSION = 'inv-offline-v3';
const PAGE_CACHE = 'pages-' + SW_CACHE_VERSION;
const API_CACHE = 'api-get-' + SW_CACHE_VERSION;
const STATIC_CACHE = 'static-' + SW_CACHE_VERSION;
const CDN_CACHE = 'cdn-' + SW_CACHE_VERSION;

const CDN_HOSTS = ['cdn.jsdelivr.net', 'cdn.tailwindcss.com', 'unpkg.com'];

function isCdnRequest(url) {
    return CDN_HOSTS.indexOf(url.hostname) !== -1;
}

function isSameOrigin(url) {
    return url.origin === self.location.origin;
}

function matchPage(request) {
    return caches.match(request).then(function (r) {
        if (r) return r;
        return caches.match(request, { ignoreSearch: true });
    });
}

self.addEventListener('install', function (event) {
    event.waitUntil(
        caches.open(STATIC_CACHE).then(function (cache) {
            return cache
                .addAll(['/offline.html', '/manifest.webmanifest', '/icons/app-icon.svg'])
                .catch(function () {});
        })
    );
    self.skipWaiting();
});

self.addEventListener('activate', function (event) {
    event.waitUntil(
        caches
            .keys()
            .then(function (keys) {
                return Promise.all(
                    keys.map(function (key) {
                        if ([PAGE_CACHE, API_CACHE, STATIC_CACHE, CDN_CACHE].indexOf(key) === -1) {
                            return caches.delete(key);
                        }
                    })
                );
            })
            .then(function () {
                return self.clients.claim();
            })
    );
});

self.addEventListener('fetch', function (event) {
    var req = event.request;
    if (req.method !== 'GET') {
        return;
    }

    var url;
    try {
        url = new URL(req.url);
    } catch (e) {
        return;
    }

    if (url.pathname === '/sw.js') {
        return;
    }

    // 1) HTML (navegación)
    if (isSameOrigin(url) && (req.mode === 'navigate' || req.destination === 'document')) {
        event.respondWith(
            fetch(req)
                .then(function (response) {
                    if (response && response.status === 200 && response.type === 'basic') {
                        caches.open(PAGE_CACHE).then(function (cache) {
                            return cache.put(req, response.clone());
                        });
                    }
                    return response;
                })
                .catch(function () {
                    return matchPage(req).then(function (cached) {
                        if (cached) return cached;
                        return caches.match('/offline.html');
                    });
                })
        );
        return;
    }

    // 2) API GET → última respuesta JSON sin conexión
    if (isSameOrigin(url) && url.pathname.indexOf('/api/') === 0) {
        event.respondWith(
            fetch(req)
                .then(function (response) {
                    if (response && response.status === 200) {
                        caches.open(API_CACHE).then(function (cache) {
                            return cache.put(req, response.clone());
                        });
                    }
                    return response;
                })
                .catch(function () {
                    return caches.match(req);
                })
        );
        return;
    }

    // 3) CDNs (Tailwind, Alpine, axios, Chart, html5-qrcode…)
    if (isCdnRequest(url)) {
        event.respondWith(
            fetch(req)
                .then(function (response) {
                    if (response && (response.ok || response.type === 'opaque')) {
                        try {
                            caches.open(CDN_CACHE).then(function (cache) {
                                return cache.put(req, response.clone());
                            });
                        } catch (e) {}
                    }
                    return response;
                })
                .catch(function () {
                    return caches.match(req);
                })
        );
        return;
    }

    // 4) Resto mismo origen (JS local, /public, imágenes…)
    if (isSameOrigin(url)) {
        event.respondWith(
            fetch(req)
                .then(function (response) {
                    if (response && response.status === 200 && response.type === 'basic') {
                        var dest = req.destination;
                        if (
                            dest === 'script' ||
                            dest === 'style' ||
                            dest === 'font' ||
                            dest === 'image' ||
                            dest === 'manifest' ||
                            url.pathname.endsWith('.webmanifest') ||
                            url.pathname.endsWith('.svg') ||
                            url.pathname.indexOf('/build/') === 0 ||
                            url.pathname.indexOf('/js/') === 0
                        ) {
                            caches.open(STATIC_CACHE).then(function (cache) {
                                return cache.put(req, response.clone());
                            });
                        }
                    }
                    return response;
                })
                .catch(function () {
                    return caches.match(req);
                })
        );
    }
});
