/*
 * LokaNusa Service Worker (Step 16 — PWA installation & offline).
 *
 * Strategi konservatif & aman:
 *  - Aset statis (CSS/JS/ikon/gambar) : stale-while-revalidate.
 *  - Navigasi dokumen                : network-first dengan fallback /offline.html.
 *  - Permintaan non-GET (POST/PUT/DELETE, mis. form/CSRF) TIDAK di-cache.
 *  - Respons dengan header Set-Cookie / no-store TIDAK di-cache (sesi aman).
 */

const CACHE_VERSION = 'lokanusa-v2';
const OFFLINE_URL = '/offline.html';
const PRECACHE_URLS = [
    OFFLINE_URL,
    '/manifest.webmanifest',
    '/icons/icon-192.png',
    '/icons/icon-512.png',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_VERSION).then((cache) => cache.addAll(PRECACHE_URLS)).then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(keys.filter((key) => key !== CACHE_VERSION).map((key) => caches.delete(key)))
        ).then(() => self.clients.claim())
    );
});

function isCacheable(response, request) {
    if (!response || !response.ok || response.type === 'opaque') {
        return false;
    }
    if (response.headers.has('Set-Cookie')) {
        return false;
    }
    const cacheControl = response.headers.get('Cache-Control') || '';
    if (/no-store|private/i.test(cacheControl)) {
        return false;
    }
    return request.method === 'GET';
}

function isStaticAsset(url) {
    return /\.(?:css|js|mjs|woff2?|ttf|otf|png|jpe?g|gif|webp|avif|svg|ico)$/i.test(url.pathname)
        || url.pathname.startsWith('/build/')
        || url.pathname.startsWith('/icons/');
}

self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // Hanya same-origin & GET yang ditangani.
    if (url.origin !== self.location.origin || request.method !== 'GET') {
        return;
    }

    // Jangan pernah cache area admin / aksi akun.
    if (url.pathname.startsWith('/admin') || url.pathname.startsWith('/livewire')) {
        return;
    }

    // Navigasi dokumen: network-first, fallback offline.
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request)
                .then((response) => {
                    if (isCacheable(response, request)) {
                        const copy = response.clone();
                        caches.open(CACHE_VERSION).then((cache) => cache.put(request, copy));
                    }
                    return response;
                })
                .catch(() => caches.match(request).then((cached) => cached || caches.match(OFFLINE_URL)))
        );
        return;
    }

    // Aset statis: stale-while-revalidate.
    if (isStaticAsset(url)) {
        event.respondWith(
            caches.match(request).then((cached) => {
                const network = fetch(request)
                    .then((response) => {
                        if (isCacheable(response, request)) {
                            const copy = response.clone();
                            caches.open(CACHE_VERSION).then((cache) => cache.put(request, copy));
                        }
                        return response;
                    })
                    .catch(() => cached);

                return cached || network;
            })
        );
    }
});
