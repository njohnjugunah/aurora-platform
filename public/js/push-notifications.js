/**
 * Push Notification Manager
 * Handles web push registration, display, and in-app notifications
 */

class PushNotificationManager {
    constructor(apiEndpoint = '/ajax/communication/push-notifications.php') {
        this.apiEndpoint = apiEndpoint;
        this.serviceWorkerRegistration = null;
        this.unreadCount = 0;
    }

    /**
     * Initialize push notifications
     */
    async initialize() {
        if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
            console.warn('Push notifications not supported');
            return false;
        }

        try {
            // Register service worker
            this.serviceWorkerRegistration = await navigator.serviceWorker.register('/service-worker.js', {
                scope: '/'
            });

            // Request permission
            const permission = Notification.permission;
            if (permission === 'default') {
                const result = await Notification.requestPermission();
                if (result !== 'granted') {
                    return false;
                }
            }

            if (permission === 'granted' || Notification.permission === 'granted') {
                this.subscribeToPushNotifications();
            }

            // Listen for service worker messages
            navigator.serviceWorker.addEventListener('message', (event) => {
                this.handleNotificationMessage(event.data);
            });

            return true;

        } catch (error) {
            console.error('Push notification initialization error:', error);
            return false;
        }
    }

    /**
     * Subscribe to push notifications
     */
    async subscribeToPushNotifications() {
        try {
            const subscription = await this.serviceWorkerRegistration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: this.urlBase64ToUint8Array(
                    this.getPublicKey() || 'BCEllJwtPDhpJFdIgXHvJuJXINmfVMCcaGT7NrPi7BzEWdLl1qBZF1vZXqGhKT-N1PVQ8wEHJyRnHYC0qM5Wqbg='
                )
            });

            // Send subscription to server
            await this.registerSubscription(subscription);

        } catch (error) {
            console.error('Push subscription error:', error);
        }
    }

    /**
     * Register subscription with server
     */
    async registerSubscription(subscription) {
        try {
            const response = await fetch(this.apiEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'register_subscription',
                    subscription: subscription.toJSON()
                })
            });

            return await response.json();

        } catch (error) {
            console.error('Register subscription error:', error);
        }
    }

    /**
     * Load and display in-app notifications
     */
    async loadNotifications(limit = 20) {
        try {
            const response = await fetch(`${this.apiEndpoint}?limit=${limit}`);
            const data = await response.json();

            if (data.success) {
                this.displayNotifications(data.notifications);
                this.unreadCount = data.notifications.filter(n => !n.is_read).length;
                this.updateNotificationBadge();
            }

        } catch (error) {
            console.error('Load notifications error:', error);
        }
    }

    /**
     * Display notifications in notification center
     */
    displayNotifications(notifications) {
        const notificationCenter = document.getElementById('notification-center');
        if (!notificationCenter) return;

        notificationCenter.innerHTML = '';

        if (notifications.length === 0) {
            notificationCenter.innerHTML = '<div class="notification-empty">No notifications</div>';
            return;
        }

        notifications.forEach(notification => {
            const element = this.createNotificationElement(notification);
            notificationCenter.appendChild(element);
        });
    }

    /**
     * Create notification element
     */
    createNotificationElement(notification) {
        const div = document.createElement('div');
        div.className = `notification-item ${!notification.is_read ? 'unread' : ''}`;
        div.dataset.notificationId = notification.id;

        const icon = this.getNotificationIcon(notification.type);
        const timeAgo = this.getTimeAgo(notification.created_at);

        div.innerHTML = `
            <div class="notification-icon">${icon}</div>
            <div class="notification-content">
                <h4>${notification.title}</h4>
                <p>${notification.message}</p>
                <span class="notification-time">${timeAgo}</span>
            </div>
            <button class="notification-close" onclick="pushNotificationManager.dismissNotification(${notification.id})">×</button>
        `;

        if (notification.action_url) {
            div.style.cursor = 'pointer';
            div.onclick = () => {
                window.location.href = notification.action_url;
                this.markAsRead(notification.id);
            };
        } else {
            div.onclick = () => this.markAsRead(notification.id);
        }

        return div;
    }

    /**
     * Mark notification as read
     */
    async markAsRead(notificationId) {
        try {
            const response = await fetch(this.apiEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'mark_read',
                    notification_id: notificationId
                })
            });

            if (response.ok) {
                const element = document.querySelector(`[data-notification-id="${notificationId}"]`);
                if (element) {
                    element.classList.remove('unread');
                }
                this.unreadCount = Math.max(0, this.unreadCount - 1);
                this.updateNotificationBadge();
            }

        } catch (error) {
            console.error('Mark read error:', error);
        }
    }

    /**
     * Dismiss notification
     */
    async dismissNotification(notificationId) {
        const element = document.querySelector(`[data-notification-id="${notificationId}"]`);
        if (element) {
            element.style.animation = 'slideOut 0.3s ease-out';
            setTimeout(() => element.remove(), 300);
        }
    }

    /**
     * Handle notification message from service worker
     */
    handleNotificationMessage(data) {
        if (data.type === 'notification') {
            this.showInAppNotification(data);
            this.unreadCount++;
            this.updateNotificationBadge();
        }
    }

    /**
     * Show in-app notification toast
     */
    showInAppNotification(notification) {
        const toast = document.createElement('div');
        toast.className = `notification-toast ${notification.notificationType || 'info'}`;
        toast.innerHTML = `
            <div class="toast-title">${notification.title}</div>
            <div class="toast-body">${notification.body}</div>
        `;

        document.body.appendChild(toast);

        setTimeout(() => {
            toast.classList.add('show');
        }, 100);

        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    }

    /**
     * Update notification badge
     */
    updateNotificationBadge() {
        const badge = document.querySelector('.notification-badge');
        if (badge) {
            badge.textContent = this.unreadCount;
            badge.style.display = this.unreadCount > 0 ? 'block' : 'none';
        }
    }

    /**
     * Get notification icon based on type
     */
    getNotificationIcon(type) {
        const icons = {
            'info': '📬',
            'success': '✅',
            'warning': '⚠️',
            'error': '❌',
            'order': '📦',
            'appointment': '📅',
            'promotion': '🎉',
            'alert': '🔔'
        };
        return icons[type] || '📬';
    }

    /**
     * Get time ago string
     */
    getTimeAgo(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const seconds = Math.floor((now - date) / 1000);

        if (seconds < 60) return 'just now';
        if (seconds < 3600) return Math.floor(seconds / 60) + 'm ago';
        if (seconds < 86400) return Math.floor(seconds / 3600) + 'h ago';
        return Math.floor(seconds / 86400) + 'd ago';
    }

    /**
     * Convert base64 to Uint8Array (for VAPID key)
     */
    urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding)
            .replace(/\-/g, '+')
            .replace(/_/g, '/');

        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);

        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }

        return outputArray;
    }

    /**
     * Get public VAPID key (should be defined in HTML or loaded from config)
     */
    getPublicKey() {
        return document.querySelector('[data-push-public-key]')?.dataset.pushPublicKey || null;
    }
}

// Initialize push notification manager
let pushNotificationManager;

document.addEventListener('DOMContentLoaded', async () => {
    pushNotificationManager = new PushNotificationManager();
    await pushNotificationManager.initialize();
    await pushNotificationManager.loadNotifications();

    // Refresh notifications every 30 seconds
    setInterval(() => {
        pushNotificationManager.loadNotifications();
    }, 30000);
});
