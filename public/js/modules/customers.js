/**
 * Customers Module
 * Handles customer management, profiles, and loyalty information
 */

const CustomersModule = {
    state: {
        customers: [],
        selectedCustomer: null,
        loading: false,
        error: null,
        filters: {
            search: '',
            status: null
        },
        pageSize: 20,
        currentPage: 1
    },

    async init() {
        console.log('Initializing Customers Module');
        await this.loadCustomers();
        this.setupEventListeners();
    },

    async loadCustomers(page = 1) {
        this.state.loading = true;
        this.state.error = null;
        this.state.currentPage = page;

        try {
            const filters = {
                ...this.state.filters,
                limit: this.state.pageSize,
                offset: (page - 1) * this.state.pageSize
            };

            this.state.customers = await window.api.getCustomers(filters);
            this.render();
        } catch (error) {
            this.state.error = error.message;
            this.showError(error.message);
        } finally {
            this.state.loading = false;
        }
    },

    async createCustomer(data) {
        this.state.loading = true;
        this.state.error = null;

        try {
            const customer = await window.api.createCustomer(data);
            this.state.customers.unshift(customer);
            this.render();
            this.showSuccess('Customer created successfully');
            return customer;
        } catch (error) {
            this.state.error = error.message;
            this.showError(error.message);
            throw error;
        } finally {
            this.state.loading = false;
        }
    },

    async updateCustomer(id, data) {
        this.state.loading = true;
        this.state.error = null;

        try {
            const result = await window.api.updateCustomer(id, data);
            const index = this.state.customers.findIndex(c => c.id === id);
            if (index > -1) {
                this.state.customers[index] = result;
            }
            this.render();
            this.showSuccess('Customer updated successfully');
            return result;
        } catch (error) {
            this.state.error = error.message;
            this.showError(error.message);
            throw error;
        } finally {
            this.state.loading = false;
        }
    },

    async getCustomerDetails(id) {
        try {
            this.state.selectedCustomer = await window.api.getCustomer(id);
            return this.state.selectedCustomer;
        } catch (error) {
            this.state.error = error.message;
            this.showError(error.message);
            throw error;
        }
    },

    async getLoyaltyInfo(customerId) {
        try {
            return await window.api.getCustomerLoyalty(customerId);
        } catch (error) {
            this.showError('Failed to load loyalty info: ' + error.message);
            return null;
        }
    },

    applyFilters() {
        this.loadCustomers(1);
    },

    clearFilters() {
        this.state.filters = { search: '', status: null };
        this.loadCustomers(1);
    },

    render() {
        const container = document.getElementById('customers-container');
        if (!container) return;

        if (this.state.loading) {
            container.innerHTML = '<div class="alert alert-info">Loading customers...</div>';
            return;
        }

        if (this.state.customers.length === 0) {
            container.innerHTML = '<div class="alert alert-warning">No customers found</div>';
            return;
        }

        container.innerHTML = `
            <div class="customers-list">
                ${this.state.customers.map(customer => this.renderCustomerCard(customer)).join('')}
            </div>
            ${this.renderPagination()}
        `;
    },

    renderCustomerCard(customer) {
        const statusBadge = {
            'active': 'success',
            'inactive': 'secondary',
            'blacklisted': 'danger'
        }[customer.status] || 'info';

        return `
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <h5 class="card-title">${customer.first_name} ${customer.last_name}</h5>
                            <p class="card-text">
                                <strong>Email:</strong> ${customer.email || '-'}<br>
                                <strong>Phone:</strong> ${customer.phone || '-'}<br>
                                <strong>Status:</strong> <span class="badge bg-${statusBadge}">${customer.status || 'active'}</span>
                            </p>
                            ${customer.date_of_birth ? `<p class="card-text"><small><strong>DOB:</strong> ${customer.date_of_birth}</small></p>` : ''}
                        </div>
                        <div class="col-md-4 text-end">
                            <button class="btn btn-sm btn-info" onclick="CustomersModule.viewLoyalty(${customer.id})">Loyalty</button>
                            <button class="btn btn-sm btn-primary" onclick="CustomersModule.editCustomer(${customer.id})">Edit</button>
                            <button class="btn btn-sm btn-secondary" onclick="CustomersModule.viewHistory(${customer.id})">History</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    },

    renderPagination() {
        const totalPages = Math.ceil(this.state.customers.length / this.state.pageSize);
        if (totalPages <= 1) return '';

        return `
            <nav>
                <ul class="pagination">
                    <li class="page-item ${this.state.currentPage === 1 ? 'disabled' : ''}">
                        <button class="page-link" onclick="CustomersModule.loadCustomers(${this.state.currentPage - 1})">Previous</button>
                    </li>
                    ${Array.from({ length: totalPages }, (_, i) => i + 1).map(page => `
                        <li class="page-item ${page === this.state.currentPage ? 'active' : ''}">
                            <button class="page-link" onclick="CustomersModule.loadCustomers(${page})">${page}</button>
                        </li>
                    `).join('')}
                    <li class="page-item ${this.state.currentPage === totalPages ? 'disabled' : ''}">
                        <button class="page-link" onclick="CustomersModule.loadCustomers(${this.state.currentPage + 1})">Next</button>
                    </li>
                </ul>
            </nav>
        `;
    },

    viewLoyalty(id) {
        alert(`View loyalty info for customer ${id} - to be implemented`);
    },

    editCustomer(id) {
        alert(`Edit customer ${id} - to be implemented`);
    },

    viewHistory(id) {
        alert(`View history for customer ${id} - to be implemented`);
    },

    setupEventListeners() {
        const searchInput = document.getElementById('customer-search');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                this.state.filters.search = e.target.value;
                this.applyFilters();
            });
        }

        const createBtn = document.getElementById('create-customer');
        if (createBtn) {
            createBtn.addEventListener('click', () => this.showCreateForm());
        }

        const refreshBtn = document.getElementById('refresh-customers');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => this.loadCustomers(1));
        }
    },

    showCreateForm() {
        alert('Create customer form - to be implemented');
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
    document.addEventListener('DOMContentLoaded', () => CustomersModule.init());
} else {
    CustomersModule.init();
}
