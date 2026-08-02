/**
 * Notification/Toast System
 * Replaces browser alerts with professional toast notifications
 */

class NotificationManager {
    constructor() {
        this.toasts = [];
        this.container = this.createContainer();
        this.defaultTimeout = 4000; // ms
    }

    createContainer() {
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 9999;
                max-width: 400px;
            `;
            document.body.appendChild(container);
        }
        return container;
    }

    show(message, type = 'info', timeout = this.defaultTimeout) {
        const toastId = `toast-${Date.now()}`;

        const toastHTML = `
            <div id="${toastId}" class="toast show" role="alert">
                <div class="toast-header">
                    <strong class="me-auto">
                        <i class="icon">${this.getIcon(type)}</i>
                        ${this.getTitle(type)}
                    </strong>
                    <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body">
                    ${message}
                </div>
            </div>
        `;

        const toastEl = document.createElement('div');
        toastEl.innerHTML = toastHTML;
        const toast = toastEl.querySelector('.toast');

        // Add type-specific styling
        toast.classList.add(`toast-${type}`);
        this.container.appendChild(toast);

        // Wire up close button
        toast.querySelector('.btn-close')?.addEventListener('click', () => {
            this.remove(toastId);
        });

        // Auto-dismiss
        if (timeout) {
            setTimeout(() => {
                this.remove(toastId);
            }, timeout);
        }

        this.toasts.push(toastId);
        return toastId;
    }

    success(message, timeout = this.defaultTimeout) {
        return this.show(message, 'success', timeout);
    }

    error(message, timeout = this.defaultTimeout) {
        return this.show(message, 'error', timeout);
    }

    warning(message, timeout = this.defaultTimeout) {
        return this.show(message, 'warning', timeout);
    }

    info(message, timeout = this.defaultTimeout) {
        return this.show(message, 'info', timeout);
    }

    remove(toastId) {
        const toastEl = document.getElementById(toastId);
        if (toastEl) {
            // Fade out animation
            toastEl.style.opacity = '0';
            toastEl.style.transition = 'opacity 0.3s ease-out';

            setTimeout(() => {
                toastEl.remove();
            }, 300);
        }

        this.toasts = this.toasts.filter(id => id !== toastId);
    }

    clear() {
        this.toasts.forEach(id => this.remove(id));
        this.toasts = [];
    }

    getIcon(type) {
        const icons = {
            'success': '✓',
            'error': '✕',
            'warning': '⚠',
            'info': 'ℹ'
        };
        return icons[type] || icons.info;
    }

    getTitle(type) {
        const titles = {
            'success': 'Success',
            'error': 'Error',
            'warning': 'Warning',
            'info': 'Info'
        };
        return titles[type] || titles.info;
    }
}

// Create global notification manager
const Notifications = new NotificationManager();

// Add CSS styles for toasts
const style = document.createElement('style');
style.textContent = `
    .toast {
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        margin-bottom: 0.75rem;
        min-width: 250px;
        animation: slideIn 0.3s ease-out;
    }

    .toast-header {
        background-color: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
        border-radius: 0.375rem 0.375rem 0 0;
        display: flex;
        align-items: center;
        padding: 0.75rem;
        font-weight: 600;
        font-size: 0.9rem;
    }

    .toast-body {
        padding: 0.75rem;
        color: #495057;
        font-size: 0.95rem;
    }

    .toast-success {
        border-left: 4px solid #198754;
    }

    .toast-success .toast-header {
        background-color: #f0f8f4;
        color: #0f5132;
    }

    .toast-error {
        border-left: 4px solid #dc3545;
    }

    .toast-error .toast-header {
        background-color: #f8f5f6;
        color: #842029;
    }

    .toast-warning {
        border-left: 4px solid #ffc107;
    }

    .toast-warning .toast-header {
        background-color: #fffbf0;
        color: #664d03;
    }

    .toast-info {
        border-left: 4px solid #0dcaf0;
    }

    .toast-info .toast-header {
        background-color: #f0f8fb;
        color: #055160;
    }

    .toast .icon {
        margin-right: 0.5rem;
        font-weight: bold;
    }

    .toast-success .icon { color: #198754; }
    .toast-error .icon { color: #dc3545; }
    .toast-warning .icon { color: #ffc107; }
    .toast-info .icon { color: #0dcaf0; }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateX(400px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    #toast-container {
        pointer-events: none;
    }

    #toast-container .toast {
        pointer-events: auto;
    }

    /* Dark mode support */
    @media (prefers-color-scheme: dark) {
        .toast {
            background-color: #2b2d31;
            border-color: #3f4147;
            color: #e8eaed;
        }

        .toast-header {
            background-color: #313338;
            border-bottom-color: #3f4147;
            color: #e8eaed;
        }

        .toast-body {
            color: #c5cad1;
        }

        .toast-success .toast-header {
            background-color: #1a3a2b;
            color: #31a24c;
        }

        .toast-error .toast-header {
            background-color: #3a2129;
            color: #f26f6f;
        }

        .toast-warning .toast-header {
            background-color: #3a3428;
            color: #f0ad4e;
        }

        .toast-info .toast-header {
            background-color: #1a3a47;
            color: #5eb3cc;
        }
    }
`;
document.head.appendChild(style);

// Export globally
window.Notifications = Notifications;
