// Minimalny Service Worker dla Push Notifications
// Plik: web/js/service-worker.js

const CACHE_NAME = 'glass-system-v1';

// Instalacja
self.addEventListener('install', event => {
  console.log('Service Worker installing...');
  self.skipWaiting();
});

// Aktywacja
self.addEventListener('activate', event => {
  console.log('Service Worker activating...');
  event.waitUntil(clients.claim());
});

// Obsługa push notifications
self.addEventListener('push', event => {
  console.log('Push received:', event);
  
  const data = event.data ? event.data.json() : {};
  
  const title = data.title || 'GlassSystem';
  const options = {
    body: data.body || 'Nowe powiadomienie',
    icon: '/favicon.ico',
    badge: '/favicon.ico',
    data: data.data || {},
    tag: data.tag || 'notification',
    requireInteraction: false,
  };

  event.waitUntil(
    self.registration.showNotification(title, options)
  );
});

// Obsługa kliknięć w powiadomienia
self.addEventListener('notificationclick', event => {
  console.log('Notification clicked:', event);
  event.notification.close();

  if (event.action === 'close') {
    return;
  }

  // Otwórz aplikację
  const urlToOpen = event.notification.data.url || '/';
  
  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true })
      .then(clientList => {
        // Jeśli aplikacja jest już otwarta
        for (let i = 0; i < clientList.length; i++) {
          const client = clientList[i];
          if (client.url.includes(self.location.origin) && 'focus' in client) {
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

console.log('Service Worker loaded');