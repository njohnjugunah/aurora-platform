/**
 * API Client - Frontend to Backend Communication
 * Handles all API calls with JWT authentication
 */

const APIClient = {
    // API Configuration
    baseURL: '/api/v1',
    accessToken: localStorage.getItem('access_token'),
    refreshToken: localStorage.getItem('refresh_token'),
    deviceId: localStorage.getItem('device_id') || generateDeviceId(),

    /**
     * Generate unique device ID
     */
    generateDeviceId() {
        return 'device_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    },

    /**
     * GET request
     */
    get(endpoint, callback, errorCallback) {
        this._request('GET', endpoint, null, callback, errorCallback);
    },

    /**
     * POST request
     */
    post(endpoint, data, callback, errorCallback) {
        this._request('POST', endpoint, data, callback, errorCallback);
    },

    /**
     * PUT request
     */
    put(endpoint, data, callback, errorCallback) {
        this._request('PUT', endpoint, data, callback, errorCallback);
    },

    /**
     * DELETE request
     */
    delete(endpoint, callback, errorCallback) {
        this._request('DELETE', endpoint, null, callback, errorCallback);
    },

    /**
     * Internal request handler
     */
    _request(method, endpoint, data, callback, errorCallback) {
        const self = this;
        const url = this.baseURL + endpoint;

        const options = {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-Device-ID': this.deviceId,
            },
        };

        // Add authorization header if token exists
        if (this.accessToken) {
            options.headers['Authorization'] = 'Bearer ' + this.accessToken;
        }

        // Add request body for non-GET requests
        if (data && method !== 'GET') {
            options.body = JSON.stringify(data);
        }

        fetch(url, options)
            .then(response => {
                // Handle 401 - token expired
                if (response.status === 401) {
                    self._handleUnauthorized(callback, errorCallback);
                    return;
                }

                // Handle 429 - rate limit
                if (response.status === 429) {
                    showAlert('Too many requests. Please try again later.', 'warning');
                    if (errorCallback) errorCallback({ error: 'Rate limited' });
                    return;
                }

                return response.json().then(json => {
                    if (response.ok) {
                        callback(json);
                    } else {
                        errorCallback ? errorCallback(json) : console.error('API Error:', json);
                    }
                });
            })
            .catch(error => {
                console.error('Network Error:', error);
                if (errorCallback) {
                    errorCallback({ error: error.message });
                } else {
                    showAlert('Network error. Please check your connection.', 'danger');
                }
            });
    },

    /**
     * Handle 401 - Unauthorized (token expired)
     */
    _handleUnauthorized(callback, errorCallback) {
        if (this.refreshToken) {
            // Try to refresh token
            this._refreshToken(() => {
                // Retry the request with new token
                // This is simplified; in production, would need to store the original request
                location.reload();
            });
        } else {
            // No refresh token - redirect to login
            showAlert('Session expired. Please login again.', 'warning');
            setTimeout(() => {
                window.location.href = '/auth/login.php';
            }, 2000);
        }
    },

    /**
     * Refresh access token
     */
    _refreshToken(callback) {
        const data = {
            refresh_token: this.refreshToken,
            device_id: this.deviceId,
        };

        const options = {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data),
        };

        fetch(this.baseURL + '/auth?action=refresh', options)
            .then(response => response.json())
            .then(json => {
                if (json.success) {
                    this.accessToken = json.access_token;
                    localStorage.setItem('access_token', json.access_token);
                    if (callback) callback();
                } else {
                    // Refresh failed - clear tokens and redirect
                    this.logout();
                }
            })
            .catch(error => {
                console.error('Token refresh error:', error);
                this.logout();
            });
    },

    /**
     * Login
     */
    login(email, password, callback, errorCallback) {
        const userAgent = navigator.userAgent;

        const data = {
            email: email,
            password: password,
            device_id: this.deviceId,
            device_name: this._getDeviceName(),
            os_type: this._getOSType(),
            os_version: navigator.appVersion.split(' ')[1],
            app_version: '1.0.0',
            push_token: 'web_' + Date.now(), // Placeholder for web
        };

        this.post('/auth?action=login', data, (response) => {
            if (response.success) {
                // Store tokens
                this.accessToken = response.data.access_token;
                this.refreshToken = response.data.refresh_token;

                localStorage.setItem('access_token', response.data.access_token);
                localStorage.setItem('refresh_token', response.data.refresh_token);
                localStorage.setItem('customer_id', response.data.customer_id);
                localStorage.setItem('customer_name', response.data.name);
                localStorage.setItem('device_id', this.deviceId);

                if (callback) callback(response.data);
            } else {
                if (errorCallback) errorCallback(response);
            }
        }, errorCallback);
    },

    /**
     * Register
     */
    register(name, email, password, phone, callback, errorCallback) {
        const data = {
            name: name,
            email: email,
            password: password,
            phone: phone,
            device_id: this.deviceId,
            device_name: this._getDeviceName(),
            os_type: this._getOSType(),
            os_version: navigator.appVersion.split(' ')[1],
            app_version: '1.0.0',
            push_token: 'web_' + Date.now(),
        };

        this.post('/auth?action=register', data, (response) => {
            if (response.success) {
                // Store tokens
                this.accessToken = response.data.access_token;
                this.refreshToken = response.data.refresh_token;

                localStorage.setItem('access_token', response.data.access_token);
                localStorage.setItem('refresh_token', response.data.refresh_token);
                localStorage.setItem('customer_id', response.data.customer_id);
                localStorage.setItem('customer_name', response.data.name);
                localStorage.setItem('device_id', this.deviceId);

                if (callback) callback(response.data);
            } else {
                if (errorCallback) errorCallback(response);
            }
        }, errorCallback);
    },

    /**
     * Logout
     */
    logout(callback) {
        // Clear local storage
        localStorage.removeItem('access_token');
        localStorage.removeItem('refresh_token');
        localStorage.removeItem('customer_id');
        localStorage.removeItem('customer_name');

        this.accessToken = null;
        this.refreshToken = null;

        // Redirect to home
        if (callback) {
            callback();
        } else {
            window.location.href = '/';
        }
    },

    /**
     * Get device name
     */
    _getDeviceName() {
        if (navigator.userAgent.includes('Windows')) return 'Windows PC';
        if (navigator.userAgent.includes('Mac')) return 'Mac';
        if (navigator.userAgent.includes('Linux')) return 'Linux';
        if (navigator.userAgent.includes('iPhone')) return 'iPhone';
        if (navigator.userAgent.includes('iPad')) return 'iPad';
        if (navigator.userAgent.includes('Android')) return 'Android Device';
        return 'Web Browser';
    },

    /**
     * Get OS type
     */
    _getOSType() {
        if (navigator.userAgent.includes('Android')) return 'android';
        if (navigator.userAgent.includes('iPhone') || navigator.userAgent.includes('iPad')) return 'ios';
        return 'web';
    },

    /**
     * Check if user is authenticated
     */
    isAuthenticated() {
        return !!this.accessToken;
    },

    /**
     * Get current user ID
     */
    getCustomerId() {
        return localStorage.getItem('customer_id');
    },

    /**
     * Get current user name
     */
    getCustomerName() {
        return localStorage.getItem('customer_name');
    },
};

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Restore tokens from localStorage if page refreshed
    APIClient.accessToken = localStorage.getItem('access_token');
    APIClient.refreshToken = localStorage.getItem('refresh_token');
    APIClient.deviceId = localStorage.getItem('device_id') || APIClient.generateDeviceId();

    // If token exists, validate it
    if (APIClient.accessToken) {
        // Token validation would happen on first API call
    }
});

/**
 * Helper function to show alerts
 */
function showAlert(message, type = 'info') {
    // Create alert element
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} fade-in`;
    alert.innerHTML = `
        <div class="container">
            <button type="button" class="btn-close" data-dismiss="alert"></button>
            ${message}
        </div>
    `;

    // Insert at top of page
    document.body.insertBefore(alert, document.body.firstChild);

    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        alert.remove();
    }, 5000);

    // Manual dismiss
    alert.querySelector('.btn-close')?.addEventListener('click', () => {
        alert.remove();
    });
}

/**
 * Helper function to format currency
 */
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-KE', {
        style: 'currency',
        currency: 'KES',
        minimumFractionDigits: 0,
    }).format(amount);
}

/**
 * Helper function to format date
 */
function formatDate(date) {
    return new Intl.DateTimeFormat('en-KE', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    }).format(new Date(date));
}

/**
 * Helper function to format time
 */
function formatTime(time) {
    return new Intl.DateTimeFormat('en-KE', {
        hour: 'numeric',
        minute: '2-digit',
        hour12: true,
    }).format(new Date('2000-01-01 ' + time));
}
