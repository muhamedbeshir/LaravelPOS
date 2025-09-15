const cacheName = 'v1';
const cacheAssets = [
  '/',
  '/css/app.css',
  '/css/all.min.css',
  '/js/app.js',
  '/js/all.min.js',
  '/webfonts/fa-solid-900.woff2',
  '/webfonts/fa-brands-400.woff2'
];

// Cache safely, skipping 404s
async function cacheSafe(cache, url) {
  try {
    const response = await fetch(url);
    if (response.ok) {
      await cache.put(url, response);
      console.log('[SW] Cached:', url);
    } else {
      console.warn('[SW] Skipped (not OK):', url);
    }
  } catch (error) {
    console.warn('[SW] Skipped (fetch failed):', url, error);
  }
}

self.addEventListener('install', (e) => {
  console.log('[SW] Installing service worker and caching static assets...');
  e.waitUntil(
    (async () => {
      const cache = await caches.open(cacheName);
      console.log('[SW] Caching local assets');
      await Promise.all(cacheAssets.map((url) => cacheSafe(cache, url)));
    })()
  );
  self.skipWaiting();
});

self.addEventListener('activate', (e) => {
  console.log('[SW] Activated');
  e.waitUntil(
    caches.keys().then((keys) => {
      return Promise.all(
        keys.map((key) => {
          if (key !== cacheName) {
            console.log('[SW] Clearing old cache:', key);
            return caches.delete(key);
          }
        })
      );
    })
  );
});

self.addEventListener('fetch', (e) => {
  e.respondWith(
    caches.match(e.request).then((response) => {
      return response || fetch(e.request);
    })
  );
});
