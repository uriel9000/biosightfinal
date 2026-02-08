const CACHE_NAME = 'biosight-v2';
const ASSETS = [
    './',
    './index.php',
    './assets/css/style.css',
    './assets/js/app.js',
    './assets/js/offline.js',
    './assets/img/icon-192.png',
    './assets/img/icon-512.png',
    './assets/img/sample-xray.jpg',
    './assets/img/sample-micro.jpg',
    'https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap'
];

/**
 * Install: Cache core assets for offline usage
 */
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            console.log('[SW] Pre-caching research bench assets...');
            return cache.addAll(ASSETS);
        })
    );
});

/**
 * Activate: Purge old versions of the lab shell
 */
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys.filter(key => key !== CACHE_NAME).map(key => caches.delete(key))
            );
        })
    );
});

/**
 * Fetch: Stale-While-Revalidate for performance
 * Ensures the laboratory dashboard is always fast, with background updates.
 */
self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);

    // API requests are never cached to ensure data integrity
    if (url.pathname.includes('/api/')) {
        return;
    }

    event.respondWith(
        caches.match(event.request).then((cachedResponse) => {
            const fetchPromise = fetch(event.request).then((networkResponse) => {
                // Background update: Cache the new version
                if (networkResponse && networkResponse.status === 200) {
                    const responseClone = networkResponse.clone();
                    caches.open(CACHE_NAME).then((cache) => {
                        cache.put(event.request, responseClone);
                    });
                }
                return networkResponse;
            }).catch(() => {
                // Offline fallback logic
                console.warn('[SW] Connectivity lost. Serving cached asset.');
            });

            // Return cached version immediately if available, otherwise wait for network
            return cachedResponse || fetchPromise;
        })
    );
});
