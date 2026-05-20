const STATIC_CACHE = 'magna-static-v1';
const RUNTIME_CACHE = 'magna-runtime-v1';
const OFFLINE_URL = '/offline.html';
const PRECACHE_URLS = [
    '/',
    '/manifest.webmanifest',
    '/favicon.ico',
    '/icons/app-icon.svg',
    '/icons/app-icon-maskable.svg',
    OFFLINE_URL,
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then((cache) => cache.addAll(PRECACHE_URLS))
            .catch(() => Promise.resolve())
    );

    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => Promise.all(
            keys
                .filter((key) => ! [STATIC_CACHE, RUNTIME_CACHE].includes(key))
                .map((key) => caches.delete(key))
        ))
    );

    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    if (request.method !== 'GET' || url.origin !== self.location.origin) {
        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(networkFirst(request, OFFLINE_URL));

        return;
    }

    if (['style', 'script', 'worker', 'image', 'font'].includes(request.destination)) {
        event.respondWith(staleWhileRevalidate(request));
    }
});

async function networkFirst(request, fallbackUrl) {
    try {
        const response = await fetch(request);
        const cache = await caches.open(RUNTIME_CACHE);
        cache.put(request, response.clone());

        return response;
    } catch {
        const cached = await caches.match(request);

        if (cached) {
            return cached;
        }

        return caches.match(fallbackUrl);
    }
}

async function staleWhileRevalidate(request) {
    const cache = await caches.open(RUNTIME_CACHE);
    const cached = await cache.match(request);
    const networkFetch = fetch(request)
        .then((response) => {
            cache.put(request, response.clone());

            return response;
        })
        .catch(() => cached);

    return cached || networkFetch;
}
