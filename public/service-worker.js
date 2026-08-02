/**
 * Service Worker for Push Notifications
 */

self.addEventListener('install', (event) => {
    console.log('Service Worker installing...');
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    console.log('Service Worker activated');
    self.clients.claim();
});

self.addEventListener('push', (event) => {
    console.log('Push notification received:', event.data);

    let notificationData = {
        title: 'GlamByMariga',
        body: 'You have a new notification',
        icon: '/images/logo-192x192.png',
        badge: '/images/badge-72x72.png',
        tag: 'glambymariga-notification'
    };

    if (event.data) {
        try {
            notificationData = { ...notificationData, ...event.data.json() };
        } catch (e) {
            notificationData.body = event.data.text();
        }
    }

    event.waitUntil(
        self.registration.showNotification(notificationData.title, {
            body: notificationData.body,
            icon: notificationData.icon,
            badge: notificationData.badge,
            tag: notificationData.tag,
            requireInteraction: notificationData.requireInteraction || false,
            data: notificationData.data || {}
        })
    );
});

self.addEventListener('notificationclick', (event) => {
    console.log('Notification clicked:', event.notification);
    event.notification.close();

    const urlToOpen = event.notification.data.url || '/';

    event.waitUntil(
        clients.matchAll({ type: 'window' }).then((clientList) => {
            // Check if there's already a window/tab open with the target URL
            for (let i = 0; i < clientList.length; i++) {
                const client = clientList[i];
                if (client.url === urlToOpen && 'focus' in client) {
                    return client.focus();
                }
            }
            // If not, open a new window/tab with the target URL
            if (clients.openWindow) {
                return clients.openWindow(urlToOpen);
            }
        })
    );
});

self.addEventListener('notificationclose', (event) => {
    console.log('Notification closed:', event.notification);
});

// Handle messages from clients
self.addEventListener('message', (event) => {
    console.log('Service Worker received message:', event.data);

    if (event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});
