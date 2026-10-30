const CACHE_NAME = 'wadahngopi-v4';
const STATIC_ASSETS = [
    '/icons/icon-192x192.png',
    '/icons/icon-512x512.png',
    '/wadahngopi.png',
    '/offline.html',
    // Add other critical static assets here
];

// Install Event
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(STATIC_ASSETS);
        })
    );
    self.skipWaiting();
});

// Activate Event (Cleanup Old Caches)
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cache) => {
                    if (cache !== CACHE_NAME) {
                        return caches.delete(cache);
                    }
                })
            );
        })
    );
    return self.clients.claim();
});

// Fetch Event
self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);

    // 1. Navigation Requests (HTML) - Network First, allow offline fallback
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request)
                .catch(() => {
                    return caches.match(request)
                        .then((cachedResponse) => {
                            if (cachedResponse) return cachedResponse;
                            return caches.match('/offline.html');
                        });
                })
        );
        return;
    }

    // 2. Static Assets (Images, CMD, JS, Fonts) - Stale While Revalidate
    // Check extension or path
    if (url.pathname.match(/\.(png|jpg|jpeg|svg|css|js|woff|woff2)$/) || url.pathname.startsWith('/build/')) {
        event.respondWith(
            caches.open(CACHE_NAME).then((cache) => {
                return cache.match(request).then((cachedResponse) => {
                    const fetchPromise = fetch(request).then((networkResponse) => {
                        // Only cache valid responses
                        if (networkResponse && networkResponse.status === 200 && networkResponse.type === 'basic') {
                            cache.put(request, networkResponse.clone());
                        }
                        return networkResponse;
                    });

                    return cachedResponse || fetchPromise;
                });
            })
        );
        return;
    }

    // 3. Default (APIs, Livewire, etc) - Network Only (Do not cache dynamic data permanently)
    // We explicitly do NOT cache Livewire updates to avoid stale state issues.
    return;
});
