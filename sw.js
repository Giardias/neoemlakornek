// Service Worker - PWA Offline Support
// Cache versiyonunu güncelledim ki tarayıcı yeni dosyaları hemen alsın
const CACHE_NAME = 'neoyapi-v7-secure-' + new Date().getTime();
const urlsToCache = [
    './',
    './index.html',
    './style.css',
    './app.js',
    './project.html',
    './about.html',
    './number.html',
    './yoneticipan07.html', // YENİ DOSYA ADI
    './yoneticipas15.html', // YENİ DOSYA ADI
    './manifest.json',
    'https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Playfair+Display:wght@600;700&display=swap',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
    'https://cdn-icons-png.flaticon.com/512/609/609803.png'
];

// Install Event
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('📦 Cache açıldı:', CACHE_NAME);
                return cache.addAll(urlsToCache);
            })
            .then(() => self.skipWaiting())
    );
});

// Activate Event
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (cacheName !== CACHE_NAME) {
                        console.log('🗑️ Eski cache siliniyor:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// Fetch Event
self.addEventListener('fetch', event => {
    // Skip non-GET requests
    if (event.request.method !== 'GET') return;

    // Skip Chrome extensions
    if (event.request.url.startsWith('chrome-extension://')) return;

    // Skip Firebase requests (keep real-time)
    if (event.request.url.includes('firebaseio.com')) {
        event.respondWith(fetch(event.request));
        return;
    }

    event.respondWith(
        caches.match(event.request)
            .then(response => {
                // Cache hit - return response
                if (response) {
                    return response;
                }

                // Clone the request
                const fetchRequest = event.request.clone();

                return fetch(fetchRequest)
                    .then(response => {
                        // Check if valid response
                        if (!response || response.status !== 200 || response.type !== 'basic') {
                            return response;
                        }

                        // Clone the response
                        const responseToCache = response.clone();

                        // Cache the new resource
                        caches.open(CACHE_NAME)
                            .then(cache => {
                                // Don't cache API responses or large files
                                if (!event.request.url.includes('api.') && 
                                    !event.request.url.includes('unsplash.com')) {
                                    cache.put(event.request, responseToCache);
                                }
                            });

                        return response;
                    })
                    .catch(() => {
                        // If fetch fails and request is for HTML, return offline page
                        if (event.request.headers.get('accept').includes('text/html')) {
                            return caches.match('./index.html');
                        }
                        
                        // For images, return placeholder
                        if (event.request.headers.get('accept').includes('image')) {
                            return caches.match('https://cdn-icons-png.flaticon.com/512/609/609803.png');
                        }
                    });
            })
    );
});

// Push Notification Event
self.addEventListener('push', event => {
    if (!event.data) return;

    const data = event.data.json();
    const options = {
        body: data.body || 'Yeni bildirim',
        icon: 'https://cdn-icons-png.flaticon.com/512/609/609803.png',
        badge: 'https://cdn-icons-png.flaticon.com/512/609/609803.png',
        vibrate: [100, 50, 100],
        data: {
            url: data.url || './'
        },
        actions: [
            {
                action: 'open',
                title: 'Aç'
            },
            {
                action: 'close',
                title: 'Kapat'
            }
        ]
    };

    event.waitUntil(
        self.registration.showNotification(data.title || 'NEO YAPI', options)
    );
});

// Notification Click Event
self.addEventListener('notificationclick', event => {
    event.notification.close();

    if (event.action === 'open') {
        event.waitUntil(
            clients.openWindow(event.notification.data.url)
        );
    }
});

// Background Sync
self.addEventListener('sync', event => {
    if (event.tag === 'sync-data') {
        event.waitUntil(syncData());
    }
});

async function syncData() {
    console.log('🔄 Background sync çalışıyor...');
    // Burada offline değişiklikleri senkronize edebilirsin
}

// Periodic Sync (for updates)
if ('periodicSync' in self.registration) {
    try {
        await self.registration.periodicSync.register('update-content', {
            minInterval: 24 * 60 * 60 * 1000 // 24 hours
        });
    } catch (error) {
        console.log('Periodic sync kaydı başarısız:', error);
    }
}