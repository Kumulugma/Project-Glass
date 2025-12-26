// Service Worker dla PWA
const CACHE_NAME = 'task-reminder-v1';
const urlsToCache = [
  '/',
  '/css/site.css',
  '/js/push-notifications.js',
  '/images/icon-192.png',
  '/images/icon-512.png',
];

// Instalacja service workera
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => cache.addAll(urlsToCache))
  );
});

// Aktywacja
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheName !== CACHE_NAME) {
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
});

// Obsługa requestów - cache first, then network
self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request)
      .then(response => {
        if (response) {
          return response;
        }
        return fetch(event.request);
      })
  );
});

// Obsługa push notifications
self.addEventListener('push', event => {
  const data = event.data ? event.data.json() : {};
  
  const title = data.title || 'Przypomnienie';
  const options = {
    body: data.body || 'Masz nowe przypomnienie',
    icon: data.icon || '/images/icon-192.png',
    badge: data.badge || '/images/badge-72.png',
    data: data.data || {},
    actions: data.actions || [
      { action: 'open', title: 'Otwórz' },
      { action: 'close', title: 'Zamknij' }
    ],
    requireInteraction: data.priority <= 3, // Wysokie priorytety wymagają interakcji
  };

  event.waitUntil(
    self.registration.showNotification(title, options)
  );
});

// Obsługa kliknięć w powiadomienia
self.addEventListener('notificationclick', event => {
  event.notification.close();

  if (event.action === 'close') {
    return;
  }

  // Otwórz aplikację
  const urlToOpen = event.notification.data.url || '/';
  
  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true })
      .then(clientList => {
        // Jeśli aplikacja jest już otwarta, przełącz na nią
        for (let i = 0; i < clientList.length; i++) {
          const client = clientList[i];
          if (client.url === urlToOpen && 'focus' in client) {
            return client.focus();
          }
        }
        
        // Jeśli nie, otwórz nowe okno
        if (clients.openWindow) {
          return clients.openWindow(urlToOpen);
        }
      })
  );
});
