const cacheName = 'filerun-cache-v1'

self.addEventListener('install', ( event ) => {
	self.skipWaiting();
});

/*
self.addEventListener('activate', event => {
	const currentCaches = [cacheName];
	event.waitUntil(
		caches.keys().then(cacheNames => {
			return cacheNames.filter(cacheName => !currentCaches.includes(cacheName));
		}).then(cachesToDelete => {
			return Promise.all(cachesToDelete.map(cacheToDelete => {
				return caches.delete(cacheToDelete);
			}));
		}).then(() => self.clients.claim())
	);
});
*/

self.addEventListener('fetch', (event) => {
	/*
	if (event.request.url.startsWith(self.location.origin)) {
	}
	*/
});