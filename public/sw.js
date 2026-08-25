const CACHE_NAME = 'couple-finance-static-v2';
const STATIC_ASSETS = [
    '/manifest.json',
    '/icons/icon-192.png',
    '/icons/icon-512.png',
    '/icons/icon.svg',
    '/favicon.svg',
    '/apple-touch-icon.png',
];

// Install Service Worker
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(STATIC_ASSETS);
        })
    );
    self.skipWaiting();
});

// Activate Service Worker & cleanup old caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys.map((key) => {
                    if (key !== CACHE_NAME) {
                        return caches.delete(key);
                    }
                })
            );
        })
    );
    self.clients.claim();
});

// Fetch strategy: Network first for HTML/Inertia requests, Stale-while-revalidate for static assets
self.addEventListener('fetch', (event) => {
    const request = event.request;

    // Only handle GET requests
    if (request.method !== 'GET') return;

    // Skip chrome-extension / non-http
    if (!request.url.startsWith('http')) return;

    // Static assets (fonts, build assets, icons)
    if (
        request.url.includes('/build/') ||
        request.url.includes('/icons/') ||
        request.url.includes('/fonts/') ||
        request.url.match(/\.(png|jpg|jpeg|svg|gif|webp|woff|woff2|ttf|eot)$/)
    ) {
        event.respondWith(
            caches.match(request).then((cachedResponse) => {
                if (cachedResponse) {
                    // Update cache in background
                    fetch(request).then((networkResponse) => {
                        if (networkResponse && networkResponse.status === 200) {
                            caches.open(CACHE_NAME).then((cache) => cache.put(request, networkResponse));
                        }
                    }).catch(() => {});
                    return cachedResponse;
                }
                return fetch(request).then((networkResponse) => {
                    if (networkResponse && networkResponse.status === 200) {
                        const clone = networkResponse.clone();
                        caches.open(CACHE_NAME).then((cache) => cache.put(request, clone));
                    }
                    return networkResponse;
                });
            })
        );
        return;
    }

    // Authenticated pages and Inertia responses must never be persisted in Cache Storage.
    event.respondWith(fetch(request));
});

self.addEventListener('push', (event) => {
    const payload = event.data?.json() || {};

    event.waitUntil(self.registration.showNotification(payload.title || 'Couple Finance', {
        body: payload.body || 'Ada kabar baru dari pasanganmu.',
        icon: '/icons/icon-192.png',
        badge: '/icons/icon-192.png',
        data: { url: payload.url || '/trips' },
    }));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    event.waitUntil(self.clients.openWindow(event.notification.data?.url || '/trips'));
});
