// Service Worker — KehadiranApp Push Notifications
self.addEventListener('push', event => {
  const data = event.data ? event.data.json() : {};
  const title   = data.title   || 'KehadiranApp';
  const options = {
    body: data.body || 'Ada notifikasi baru.',
    icon: '/KehadiranApp/public/img/icon-192.png',
    badge: '/KehadiranApp/public/img/badge-72.png',
    data: { url: data.url || '/KehadiranApp/public/dashboard' },
    vibrate: [200, 100, 200],
  };
  event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', event => {
  event.notification.close();
  event.waitUntil(clients.openWindow(event.notification.data.url));
});
