/**
 * Aurora API Client
 * Handles all HTTP communication with backend API
 * Manages authentication, error handling, and request/response transformation
 */

class APIClient {
    constructor(baseURL = 'http://localhost:9000/api/v1') {
        this.baseURL = baseURL;
        this.token = localStorage.getItem('auth_token');
        this.headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        };

        if (this.token) {
            this.headers['Authorization'] = `Bearer ${this.token}`;
        }
    }

    /**
     * Set authentication token
     */
    setToken(token) {
        this.token = token;
        this.headers['Authorization'] = `Bearer ${token}`;
        localStorage.setItem('auth_token', token);
    }

    /**
     * Clear authentication token
     */
    clearToken() {
        this.token = null;
        delete this.headers['Authorization'];
        localStorage.removeItem('auth_token');
    }

    /**
     * Make HTTP request
     */
    async request(method, endpoint, data = null) {
        const url = `${this.baseURL}${endpoint}`;
        const options = {
            method,
            headers: this.headers
        };

        if (data && (method === 'POST' || method === 'PUT' || method === 'PATCH')) {
            options.body = JSON.stringify(data);
        }

        try {
            const response = await fetch(url, options);

            // Handle 401 Unauthorized
            if (response.status === 401) {
                this.clearToken();
                window.location.href = '/login';
                throw new Error('Unauthorized - please log in again');
            }

            if (!response.ok) {
                const error = await response.json().catch(() => ({}));
                throw new APIError(
                    error.message || response.statusText,
                    response.status,
                    error
                );
            }

            // Handle 204 No Content
            if (response.status === 204) {
                return null;
            }

            return await response.json();
        } catch (error) {
            if (error instanceof APIError) {
                throw error;
            }
            throw new APIError(error.message || 'Network error', 0, error);
        }
    }

    /**
     * GET request
     */
    get(endpoint) {
        return this.request('GET', endpoint);
    }

    /**
     * POST request
     */
    post(endpoint, data) {
        return this.request('POST', endpoint, data);
    }

    /**
     * PUT request
     */
    put(endpoint, data) {
        return this.request('PUT', endpoint, data);
    }

    /**
     * PATCH request
     */
    patch(endpoint, data) {
        return this.request('PATCH', endpoint, data);
    }

    /**
     * DELETE request
     */
    delete(endpoint) {
        return this.request('DELETE', endpoint);
    }

    // ===== AUTHENTICATION =====

    login(email, password) {
        return this.post('/auth/login', { email, password });
    }

    logout() {
        this.clearToken();
        return this.post('/auth/logout', {});
    }

    verifyToken() {
        return this.get('/auth/verify');
    }

    // ===== APPOINTMENTS =====

    getAppointments(filters = {}) {
        const params = new URLSearchParams(filters).toString();
        return this.get(`/appointments${params ? '?' + params : ''}`);
    }

    getAppointment(id) {
        return this.get(`/appointments/${id}`);
    }

    createAppointment(data) {
        return this.post('/appointments', data);
    }

    updateAppointment(id, data) {
        return this.put(`/appointments/${id}`, data);
    }

    cancelAppointment(id, reason = '') {
        return this.post(`/appointments/${id}/cancel`, { reason });
    }

    // ===== SALES & PAYMENTS =====

    getSales(filters = {}) {
        const params = new URLSearchParams(filters).toString();
        return this.get(`/sales${params ? '?' + params : ''}`);
    }

    getSale(id) {
        return this.get(`/sales/${id}`);
    }

    createSale(data) {
        return this.post('/sales', data);
    }

    processPayment(saleId, data) {
        return this.post(`/sales/${saleId}/pay`, data);
    }

    getPayments(filters = {}) {
        const params = new URLSearchParams(filters).toString();
        return this.get(`/payments${params ? '?' + params : ''}`);
    }

    // ===== CUSTOMERS =====

    getCustomers(filters = {}) {
        const params = new URLSearchParams(filters).toString();
        return this.get(`/customers${params ? '?' + params : ''}`);
    }

    getCustomer(id) {
        return this.get(`/customers/${id}`);
    }

    createCustomer(data) {
        return this.post('/customers', data);
    }

    updateCustomer(id, data) {
        return this.put(`/customers/${id}`, data);
    }

    // ===== INVENTORY =====

    getProducts(filters = {}) {
        const params = new URLSearchParams(filters).toString();
        return this.get(`/products${params ? '?' + params : ''}`);
    }

    getProduct(id) {
        return this.get(`/products/${id}`);
    }

    getStock(productId) {
        return this.get(`/stock/${productId}`);
    }

    updateStock(productId, data) {
        return this.put(`/stock/${productId}`, data);
    }

    getLowStockItems() {
        return this.get('/stock/low-stock');
    }

    // ===== LOYALTY =====

    getCustomerLoyalty(customerId) {
        return this.get(`/customers/${customerId}/loyalty`);
    }

    awardLoyaltyPoints(customerId, points) {
        return this.post(`/customers/${customerId}/loyalty/award`, { points });
    }

    redeemLoyaltyPoints(customerId, points) {
        return this.post(`/customers/${customerId}/loyalty/redeem`, { points });
    }

    // ===== SERVICES =====

    getServices() {
        return this.get('/services');
    }

    getService(id) {
        return this.get(`/services/${id}`);
    }

    // ===== STAFF =====

    getStaff() {
        return this.get('/staff');
    }

    getStaffMember(id) {
        return this.get(`/staff/${id}`);
    }

    getStaffPerformance(id) {
        return this.get(`/staff/${id}/performance`);
    }

    // ===== REPORTING =====

    getDashboard() {
        return this.get('/reports/dashboard');
    }

    getRevenueReport(filters = {}) {
        const params = new URLSearchParams(filters).toString();
        return this.get(`/reports/revenue${params ? '?' + params : ''}`);
    }

    getAppointmentReport(filters = {}) {
        const params = new URLSearchParams(filters).toString();
        return this.get(`/reports/appointments${params ? '?' + params : ''}`);
    }

    getStaffReport(filters = {}) {
        const params = new URLSearchParams(filters).toString();
        return this.get(`/reports/staff${params ? '?' + params : ''}`);
    }
}

/**
 * Custom API Error class
 */
class APIError extends Error {
    constructor(message, status, details = {}) {
        super(message);
        this.name = 'APIError';
        this.status = status;
        this.details = details;
    }
}

// Create global instance
window.api = new APIClient();
