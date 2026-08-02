/**
 * Point of Sale (POS) Module
 * Handles sales transactions, cart management, and payment processing
 */

const POSModule = {
    state: {
        cart: [],
        services: [],
        customers: [],
        selectedCustomer: null,
        loading: false,
        error: null,
        subtotal: 0,
        tax: 0,
        total: 0
    },

    TAX_RATE: 0.16, // 16% VAT

    async init() {
        console.log('Initializing POS Module');
        await this.loadServices();
        await this.loadCustomers();
        this.setupEventListeners();
        this.render();
    },

    async loadServices() {
        try {
            this.state.services = await window.api.getServices();
        } catch (error) {
            this.showError('Failed to load services: ' + error.message);
        }
    },

    async loadCustomers() {
        try {
            this.state.customers = await window.api.getCustomers();
        } catch (error) {
            this.showError('Failed to load customers: ' + error.message);
        }
    },

    addToCart(serviceId, quantity = 1) {
        const service = this.state.services.find(s => s.id === serviceId);
        if (!service) {
            this.showError('Service not found');
            return;
        }

        const existingItem = this.state.cart.find(item => item.id === serviceId);
        if (existingItem) {
            existingItem.quantity += quantity;
        } else {
            this.state.cart.push({
                id: serviceId,
                name: service.name,
                price: service.base_price,
                quantity,
                duration: service.duration_minutes
            });
        }

        this.updateTotals();
        this.render();
        this.showSuccess(`${service.name} added to cart`);
    },

    removeFromCart(serviceId) {
        this.state.cart = this.state.cart.filter(item => item.id !== serviceId);
        this.updateTotals();
        this.render();
    },

    updateQuantity(serviceId, quantity) {
        const item = this.state.cart.find(item => item.id === serviceId);
        if (item) {
            item.quantity = Math.max(1, quantity);
            this.updateTotals();
            this.render();
        }
    },

    updateTotals() {
        this.state.subtotal = this.state.cart.reduce((sum, item) => {
            return sum + (item.price * item.quantity);
        }, 0);

        this.state.tax = this.state.subtotal * this.TAX_RATE;
        this.state.total = this.state.subtotal + this.state.tax;
    },

    async checkout(paymentMethod = 'cash', paymentReference = null) {
        if (this.state.cart.length === 0) {
            this.showError('Cart is empty');
            return;
        }

        this.state.loading = true;
        this.state.error = null;

        try {
            // Create sale
            const saleData = {
                customer_id: this.state.selectedCustomer?.id,
                line_items: this.state.cart.map(item => ({
                    service_id: item.id,
                    quantity: item.quantity,
                    unit_price: item.price,
                    amount: item.price * item.quantity
                })),
                subtotal: this.state.subtotal,
                tax_amount: this.state.tax,
                total_amount: this.state.total
            };

            const sale = await window.api.createSale(saleData);

            // Process payment
            const paymentData = {
                method: paymentMethod,
                amount: this.state.total,
                reference: paymentReference,
                status: paymentMethod === 'cash' ? 'completed' : 'pending'
            };

            await window.api.processPayment(sale.id, paymentData);

            // Clear cart
            this.state.cart = [];
            this.state.selectedCustomer = null;
            this.updateTotals();
            this.render();
            this.showSuccess(`Sale completed - Total: KES ${this.state.total.toFixed(2)}`);

            return sale;
        } catch (error) {
            this.state.error = error.message;
            this.showError('Checkout failed: ' + error.message);
            throw error;
        } finally {
            this.state.loading = false;
        }
    },

    selectCustomer(customerId) {
        this.state.selectedCustomer = this.state.customers.find(c => c.id === customerId) || null;
        this.render();
    },

    applyDiscount(percentage) {
        this.state.subtotal *= (1 - percentage / 100);
        this.updateTotals();
        this.render();
    },

    render() {
        this.renderCart();
        this.renderServices();
        this.renderSummary();
    },

    renderCart() {
        const container = document.getElementById('pos-cart');
        if (!container) return;

        if (this.state.cart.length === 0) {
            container.innerHTML = '<div class="alert alert-info">Cart is empty</div>';
            return;
        }

        container.innerHTML = `
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Service</th>
                            <th>Qty</th>
                            <th>Price</th>
                            <th>Amount</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${this.state.cart.map((item, index) => `
                            <tr>
                                <td>${item.name}</td>
                                <td><input type="number" min="1" value="${item.quantity}"
                                    onchange="POSModule.updateQuantity(${item.id}, this.value)" class="form-control form-control-sm" style="width: 60px;"></td>
                                <td>KES ${item.price.toFixed(2)}</td>
                                <td>KES ${(item.price * item.quantity).toFixed(2)}</td>
                                <td><button class="btn btn-sm btn-danger" onclick="POSModule.removeFromCart(${item.id})">Remove</button></td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        `;
    },

    renderServices() {
        const container = document.getElementById('pos-services');
        if (!container) return;

        container.innerHTML = `
            <div class="services-grid">
                ${this.state.services.map(service => `
                    <button class="btn btn-outline-primary" onclick="POSModule.addToCart(${service.id})">
                        ${service.name}<br>
                        <small>KES ${service.base_price.toFixed(2)}</small>
                    </button>
                `).join('')}
            </div>
        `;
    },

    renderSummary() {
        const container = document.getElementById('pos-summary');
        if (!container) return;

        container.innerHTML = `
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Order Summary</h5>

                    <div class="mb-3">
                        <label class="form-label">Customer</label>
                        <select class="form-select form-select-sm" onchange="POSModule.selectCustomer(this.value)">
                            <option value="">Walk-in Customer</option>
                            ${this.state.customers.map(c => `
                                <option value="${c.id}">${c.first_name} ${c.last_name}</option>
                            `).join('')}
                        </select>
                    </div>

                    <div class="row mb-2">
                        <div class="col-6">Subtotal:</div>
                        <div class="col-6 text-end">KES ${this.state.subtotal.toFixed(2)}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-6">Tax (16%):</div>
                        <div class="col-6 text-end">KES ${this.state.tax.toFixed(2)}</div>
                    </div>
                    <div class="row mb-3 border-top pt-2">
                        <div class="col-6"><strong>Total:</strong></div>
                        <div class="col-6 text-end"><strong>KES ${this.state.total.toFixed(2)}</strong></div>
                    </div>

                    <button class="btn btn-success w-100 mb-2" onclick="POSModule.checkout('cash')"
                        ${this.state.cart.length === 0 ? 'disabled' : ''}>
                        Cash Payment
                    </button>
                    <button class="btn btn-info w-100" onclick="POSModule.checkout('mpesa')"
                        ${this.state.cart.length === 0 ? 'disabled' : ''}>
                        M-Pesa Payment
                    </button>
                </div>
            </div>
        `;
    },

    setupEventListeners() {
        // Event listeners setup
    },

    showSuccess(message) {
        console.log('✓', message);
    },

    showError(message) {
        console.error('✗', message);
    }
};

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => POSModule.init());
} else {
    POSModule.init();
}
