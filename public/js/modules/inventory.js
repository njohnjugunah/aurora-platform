/**
 * Inventory Module
 * Handles product inventory, stock tracking, and low stock alerts
 */

const InventoryModule = {
    state: {
        products: [],
        selectedProduct: null,
        loading: false,
        error: null,
        filters: {
            search: '',
            status: null // 'low', 'out', 'normal'
        },
        pageSize: 20,
        currentPage: 1
    },

    async init() {
        await this.loadProducts();
        this.setupEventListeners();
    },

    async loadProducts(page = 1) {
        this.state.loading = true;
        this.state.error = null;
        this.state.currentPage = page;

        try {
            const filters = {
                search: this.state.filters.search,
                status: this.state.filters.status,
                limit: this.state.pageSize,
                offset: (page - 1) * this.state.pageSize
            };

            this.state.products = await window.api.getProducts(filters);
            this.render();
        } catch (error) {
            this.state.error = error.message;
            Notifications.error('Failed to load inventory: ' + error.message);
        } finally {
            this.state.loading = false;
        }
    },

    async updateStock(productId, quantity) {
        this.state.loading = true;

        try {
            await window.api.updateStock(productId, { quantity_on_hand: quantity });
            Notifications.success('Stock updated successfully');
            await this.loadProducts(this.state.currentPage);
        } catch (error) {
            Notifications.error('Failed to update stock: ' + error.message);
        } finally {
            this.state.loading = false;
        }
    },

    async getProductDetails(productId) {
        try {
            this.state.selectedProduct = await window.api.getProduct(productId);
            return this.state.selectedProduct;
        } catch (error) {
            Notifications.error('Failed to load product details: ' + error.message);
            throw error;
        }
    },

    applyFilters() {
        this.loadProducts(1);
    },

    clearFilters() {
        this.state.filters = { search: '', status: null };
        this.loadProducts(1);
    },

    render() {
        this.renderFilters();
        this.renderProducts();
    },

    renderFilters() {
        const container = document.getElementById('inventory-filters');
        if (!container) return;

        container.innerHTML = `
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="inventory-search"
                                placeholder="Search products..." value="${this.state.filters.search}">
                        </div>
                        <div class="col-md-4">
                            <select class="form-select" id="inventory-status">
                                <option value="">All Products</option>
                                <option value="low" ${this.state.filters.status === 'low' ? 'selected' : ''}>Low Stock</option>
                                <option value="out" ${this.state.filters.status === 'out' ? 'selected' : ''}>Out of Stock</option>
                                <option value="normal" ${this.state.filters.status === 'normal' ? 'selected' : ''}>In Stock</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-primary w-100" id="inventory-filter-btn">Filter</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Wire up filters
        document.getElementById('inventory-search')?.addEventListener('input', (e) => {
            this.state.filters.search = e.target.value;
        });

        document.getElementById('inventory-status')?.addEventListener('change', (e) => {
            this.state.filters.status = e.target.value || null;
        });

        document.getElementById('inventory-filter-btn')?.addEventListener('click', () => {
            this.applyFilters();
        });
    },

    renderProducts() {
        const container = document.getElementById('inventory-container');
        if (!container) return;

        if (this.state.loading) {
            container.innerHTML = '<div class="alert alert-info">Loading inventory...</div>';
            return;
        }

        if (this.state.products.length === 0) {
            container.innerHTML = '<div class="alert alert-warning">No products found</div>';
            return;
        }

        container.innerHTML = `
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Product</th>
                            <th>SKU</th>
                            <th>Stock Level</th>
                            <th>Reorder Point</th>
                            <th>Status</th>
                            <th>Unit Price</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${this.state.products.map(product => this.renderProductRow(product)).join('')}
                    </tbody>
                </table>
            </div>
            ${this.renderPagination()}
        `;
    },

    renderProductRow(product) {
        const stockLevel = product.quantity_on_hand || 0;
        const reorderPoint = product.reorder_point || 0;
        let status = 'normal';
        let statusBadge = 'success';

        if (stockLevel === 0) {
            status = 'Out of Stock';
            statusBadge = 'danger';
        } else if (stockLevel <= reorderPoint) {
            status = 'Low Stock';
            statusBadge = 'warning';
        } else {
            status = 'In Stock';
            statusBadge = 'success';
        }

        return `
            <tr>
                <td>
                    <strong>${product.name || 'N/A'}</strong>
                    ${product.description ? `<br><small class="text-muted">${product.description}</small>` : ''}
                </td>
                <td>${product.sku || '-'}</td>
                <td>
                    <strong>${stockLevel}</strong>
                    <div class="progress mt-1" style="height: 8px;">
                        <div class="progress-bar" style="width: ${Math.min(stockLevel / reorderPoint * 100, 100)}%"></div>
                    </div>
                </td>
                <td>${reorderPoint}</td>
                <td>
                    <span class="badge bg-${statusBadge}">${status}</span>
                </td>
                <td>KES ${(product.unit_price || 0).toFixed(2)}</td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="InventoryModule.showEditStockModal(${product.id})">
                        Update
                    </button>
                    <button class="btn btn-sm btn-info" onclick="InventoryModule.showDetailsModal(${product.id})">
                        Details
                    </button>
                </td>
            </tr>
        `;
    },

    renderPagination() {
        const totalPages = Math.ceil(this.state.products.length / this.state.pageSize);
        if (totalPages <= 1) return '';

        return `
            <nav class="mt-3">
                <ul class="pagination">
                    <li class="page-item ${this.state.currentPage === 1 ? 'disabled' : ''}">
                        <button class="page-link" onclick="InventoryModule.loadProducts(${this.state.currentPage - 1})">Previous</button>
                    </li>
                    ${Array.from({ length: totalPages }, (_, i) => i + 1).map(page => `
                        <li class="page-item ${page === this.state.currentPage ? 'active' : ''}">
                            <button class="page-link" onclick="InventoryModule.loadProducts(${page})">${page}</button>
                        </li>
                    `).join('')}
                    <li class="page-item ${this.state.currentPage === totalPages ? 'disabled' : ''}">
                        <button class="page-link" onclick="InventoryModule.loadProducts(${this.state.currentPage + 1})">Next</button>
                    </li>
                </ul>
            </nav>
        `;
    },

    showEditStockModal(productId) {
        const product = this.state.products.find(p => p.id === productId);
        if (!product) {
            Notifications.error('Product not found');
            return;
        }

        const modal = new Modal(`Update Stock: ${product.name}`);

        const form = new FormBuilder('edit-stock-form');
        form.addField(new FormInput('quantity', {
            label: 'Stock Level',
            type: 'number',
            required: true,
            value: product.quantity_on_hand || 0
        }));
        form.addField(new FormInput('reorder_point', {
            label: 'Reorder Point',
            type: 'number',
            required: true,
            value: product.reorder_point || 0
        }));

        modal.setContent(form.render());
        modal.setFooter(`
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" id="save-stock-btn">Update Stock</button>
        `);

        const instance = modal.show();

        document.getElementById('save-stock-btn')?.addEventListener('click', async () => {
            const quantity = parseInt(document.getElementById('quantity')?.value || 0);
            const reorderPoint = parseInt(document.getElementById('reorder_point')?.value || 0);

            if (!quantity || !reorderPoint) {
                Notifications.error('Please fill in all fields');
                return;
            }

            await this.updateStock(productId, quantity);
            instance.hide();
        });
    },

    async showDetailsModal(productId) {
        const product = await this.getProductDetails(productId);
        if (!product) return;

        const modal = new Modal(`Product Details: ${product.name}`);

        modal.setContent(`
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Product Name:</strong><br>${product.name}</p>
                    <p><strong>SKU:</strong><br>${product.sku || '-'}</p>
                    <p><strong>Description:</strong><br>${product.description || '-'}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>Stock Level:</strong><br>${product.quantity_on_hand || 0} units</p>
                    <p><strong>Reorder Point:</strong><br>${product.reorder_point || 0} units</p>
                    <p><strong>Unit Price:</strong><br>KES ${(product.unit_price || 0).toFixed(2)}</p>
                </div>
            </div>
            <hr>
            <h6>Stock Movements (Last 10)</h6>
            <div id="stock-movements-container">Loading...</div>
        `);

        const instance = modal.show();

        // Load stock movements
        try {
            const movements = await window.api.getStock?.(productId); // Would need API implementation
            const container = document.getElementById('stock-movements-container');
            if (container && movements) {
                container.innerHTML = `<pre>${JSON.stringify(movements, null, 2)}</pre>`;
            }
        } catch (error) {
            // Silently fail if movements API not available
            const container = document.getElementById('stock-movements-container');
            if (container) {
                container.innerHTML = '<small class="text-muted">Stock movements not available</small>';
            }
        }
    },

    setupEventListeners() {
        // Setup handlers if needed
    }
};

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => InventoryModule.init());
} else {
    InventoryModule.init();
}
