# Aurora Platform - Frontend Developer Guide

**Phase 2: Digital Channels Frontend**

---

## QUICK START

### Entry Points

1. **Login Page**: `public/login.html`
   - Email: `demo@aurora.local`
   - Password: `password`
   - Redirects to dashboard on successful auth

2. **Dashboard**: `public/dashboard.html`
   - Main application interface
   - Sidebar navigation to modules
   - Content area for each section
   - Requires valid auth token (set by login)

### Setup

```bash
# Ensure backend API is running
npm install              # Install any frontend dependencies
# Serve public/ directory on localhost:8000+ (any web server)

# Test by visiting:
# http://localhost:3000/login.html
```

---

## MODULE ARCHITECTURE

### Global API Client

```javascript
// window.api is available globally after api-client.js loads

// Authentication
await window.api.login(email, password)          // Returns {token}
window.api.setToken(token)                       // Set auth token
window.api.clearToken()                          // Clear token

// All endpoints use automatic Authorization header
await window.api.getAppointments(filters)
await window.api.createAppointment(data)
await window.api.processPayment(saleId, paymentData)
// ... see api-client.js for complete method list
```

### Module Pattern

Each module (admin-dashboard, appointments, pos, customers) follows this pattern:

```javascript
const ModuleName = {
    // 1. State container
    state: {
        data: [],
        loading: false,
        error: null
    },

    // 2. Initialize on page load
    async init() {
        console.log('Initializing...');
        await this.loadData();
        this.setupEventListeners();
    },

    // 3. Data fetching
    async loadData() {
        this.state.loading = true;
        try {
            this.state.data = await window.api.getSomething();
            this.render();
        } catch (error) {
            this.state.error = error.message;
        } finally {
            this.state.loading = false;
        }
    },

    // 4. DOM rendering
    render() {
        const container = document.getElementById('container-id');
        container.innerHTML = `
            ${this.state.data.map(item => this.renderItem(item)).join('')}
        `;
    },

    // 5. Event setup
    setupEventListeners() {
        document.getElementById('button-id')?.addEventListener('click', 
            () => this.handleClick()
        );
    },

    // 6. Error display
    showError(message) {
        console.error('✗', message);
        // TODO: Implement toast notification
    }
};
```

---

## COMMON TASKS

### Adding a New Module

1. **Create the file**: `public/js/modules/new-feature.js`

```javascript
const NewFeatureModule = {
    state: {
        items: [],
        loading: false,
        error: null
    },

    async init() {
        console.log('Initializing NewFeature Module');
        await this.loadItems();
        this.setupEventListeners();
    },

    async loadItems() {
        this.state.loading = true;
        try {
            this.state.items = await window.api.getItems();
            this.render();
        } catch (error) {
            this.state.error = error.message;
            this.showError(error.message);
        } finally {
            this.state.loading = false;
        }
    },

    render() {
        const container = document.getElementById('new-feature-container');
        if (!container) return;
        
        container.innerHTML = `
            <div class="items-list">
                ${this.state.items.map(item => `
                    <div class="card">${item.name}</div>
                `).join('')}
            </div>
        `;
    },

    setupEventListeners() {
        // Wire up event handlers
    },

    showError(message) {
        console.error('✗', message);
    }
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => NewFeatureModule.init());
} else {
    NewFeatureModule.init();
}
```

2. **Add to dashboard.html**:
   - Add section HTML: `<section class="section" id="new-feature-section">`
   - Add nav link: `<a class="nav-link" onclick="navigateTo('new-feature', event)">`
   - Include script: `<script src="js/modules/new-feature.js"></script>`

### Adding an API Endpoint

1. **Update api-client.js** with new method:

```javascript
// Add to appropriate section (e.g., INVENTORY)
getInventoryItems(filters = {}) {
    const params = new URLSearchParams(filters).toString();
    return this.get(`/inventory/items${params ? '?' + params : ''}`);
}

updateInventoryItem(id, data) {
    return this.put(`/inventory/items/${id}`, data);
}
```

2. **Use in module**:

```javascript
async loadItems(filters) {
    this.state.items = await window.api.getInventoryItems(filters);
}
```

### Creating a Form Modal

1. **Add modal HTML to dashboard.html**:

```html
<div class="modal" id="create-item-modal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="create-item-form" onsubmit="ModuleName.handleFormSubmit(event)">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Item Name</label>
                        <input type="text" class="form-control" id="item-name" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>
```

2. **Handle submission in module**:

```javascript
async handleFormSubmit(event) {
    event.preventDefault();
    const itemName = document.getElementById('item-name').value;
    
    try {
        await window.api.createItem({ name: itemName });
        // Close modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('create-item-modal'));
        modal.hide();
        // Reload list
        this.loadItems();
    } catch (error) {
        this.showError(error.message);
    }
}
```

3. **Show modal on button click**:

```javascript
document.getElementById('create-item-btn').addEventListener('click', () => {
    const modal = new bootstrap.Modal(document.getElementById('create-item-modal'));
    modal.show();
});
```

### Adding Error Notifications

Replace console-based errors with toast notifications:

```javascript
showError(message) {
    // Using Bootstrap alerts (add to top of content area)
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-danger alert-dismissible fade show';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.querySelector('.content').prepend(alertDiv);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => alertDiv.remove(), 5000);
}

showSuccess(message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-success alert-dismissible fade show';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.querySelector('.content').prepend(alertDiv);
    
    setTimeout(() => alertDiv.remove(), 3000);
}
```

---

## API REFERENCE

### Authentication

```javascript
// Login
const response = await window.api.login('user@example.com', 'password');
// Returns: { token: 'jwt.token.here', user: {...} }

// Verify token
const valid = await window.api.verifyToken();

// Logout
window.api.logout();
```

### Appointments

```javascript
// List
const appointments = await window.api.getAppointments({ 
    status: 'confirmed',
    staff_id: 5 
});

// Get single
const appointment = await window.api.getAppointment(123);

// Create
const newApt = await window.api.createAppointment({
    customer_id: 1,
    service_id: 2,
    staff_id: 3,
    start_time: '2026-08-15 10:00:00'
});

// Update
const updated = await window.api.updateAppointment(123, {
    status: 'completed'
});

// Cancel
await window.api.cancelAppointment(123, 'Customer requested');
```

### Sales & Payments

```javascript
// Create sale
const sale = await window.api.createSale({
    customer_id: 1,
    line_items: [
        { service_id: 1, quantity: 1, unit_price: 50 }
    ],
    subtotal: 50,
    tax_amount: 8,
    total_amount: 58
});

// Process payment
const payment = await window.api.processPayment(saleId, {
    method: 'cash',
    amount: 58,
    status: 'completed'
});

// List payments
const payments = await window.api.getPayments({ 
    status: 'completed' 
});
```

### Customers

```javascript
// List
const customers = await window.api.getCustomers({ 
    search: 'John',
    limit: 20 
});

// Get single
const customer = await window.api.getCustomer(123);

// Create
const newCustomer = await window.api.createCustomer({
    first_name: 'Jane',
    last_name: 'Doe',
    email: 'jane@example.com',
    phone: '254712345678'
});

// Update
const updated = await window.api.updateCustomer(123, {
    phone: '254712345678'
});

// Loyalty info
const loyalty = await window.api.getCustomerLoyalty(123);

// Award points
await window.api.awardLoyaltyPoints(123, 100);

// Redeem points
await window.api.redeemLoyaltyPoints(123, 50);
```

### Reporting

```javascript
// Dashboard KPIs
const dashboard = await window.api.getDashboard();
// Returns: { active_customers, staff_count, ... }

// Revenue report
const revenue = await window.api.getRevenueReport({
    start_date: '2026-08-01',
    end_date: '2026-08-31'
});

// Appointment report
const appointments = await window.api.getAppointmentReport({
    start_date: '2026-08-01',
    end_date: '2026-08-31'
});

// Staff report
const staff = await window.api.getStaffReport({
    start_date: '2026-08-01',
    end_date: '2026-08-31'
});
```

---

## STYLING GUIDE

### Bootstrap Utilities

```html
<!-- Spacing -->
<div class="m-3">Margin all sides</div>
<div class="mb-2">Margin bottom</div>
<div class="p-4">Padding all sides</div>

<!-- Grid -->
<div class="row">
    <div class="col-md-6">Half width on medium+</div>
    <div class="col-md-6">Half width on medium+</div>
</div>

<!-- Alerts -->
<div class="alert alert-success">Success message</div>
<div class="alert alert-danger">Error message</div>
<div class="alert alert-warning">Warning message</div>
<div class="alert alert-info">Info message</div>

<!-- Buttons -->
<button class="btn btn-primary">Primary</button>
<button class="btn btn-secondary">Secondary</button>
<button class="btn btn-success">Success</button>
<button class="btn btn-danger">Danger</button>

<!-- Forms -->
<div class="mb-3">
    <label class="form-label">Label</label>
    <input type="text" class="form-control">
</div>

<!-- Cards -->
<div class="card">
    <div class="card-header">Header</div>
    <div class="card-body">Content</div>
    <div class="card-footer">Footer</div>
</div>

<!-- Tables -->
<table class="table table-sm">
    <thead>
        <tr><th>Header</th></tr>
    </thead>
    <tbody>
        <tr><td>Data</td></tr>
    </tbody>
</table>
```

### Custom Colors

Edit in `dashboard.html` or add CSS:

```css
:root {
    --primary: #667eea;
    --secondary: #764ba2;
    --sidebar: #2c3e50;
}
```

---

## TROUBLESHOOTING

### Module Not Loading

**Problem**: Module methods not available in browser console

**Solution**:
1. Check script tag is included in dashboard.html
2. Verify module initialization runs (look for "Initializing..." log)
3. Ensure module name is correct globally

### API Returns 401

**Problem**: "Unauthorized - please log in again"

**Solution**:
1. Login page should have set token
2. Check localStorage for `auth_token`
3. Verify token format is not corrupted
4. Re-login and try again

### State Not Updating

**Problem**: Data loaded but not visible on page

**Solution**:
1. Check render() is called after data loads
2. Verify container element ID matches in HTML
3. Check for JavaScript errors in console
4. Add console.log in render() to debug

### CORS Errors

**Problem**: "Access to fetch blocked by CORS policy"

**Solution**:
1. Ensure backend has CORS enabled
2. Check Access-Control-Allow-Origin header
3. Verify API baseURL in api-client.js is correct
4. Add credentials: 'include' if needed

---

## PERFORMANCE TIPS

### Optimize Rendering

```javascript
// Bad: Re-renders entire list on every change
render() {
    this.state.items = await this.loadItems();
    this.renderList();
}

// Good: Only update changed items
async addItem(item) {
    this.state.items.push(item);
    this.renderItem(item);  // Add single item
}
```

### Lazy Load Modules

```javascript
// Load module only when section is visible
document.getElementById('inventory-section')?.addEventListener('click', () => {
    if (!window.InventoryModule) {
        const script = document.createElement('script');
        script.src = 'js/modules/inventory.js';
        document.body.appendChild(script);
    }
});
```

### Cache API Responses

```javascript
const ModuleWithCache = {
    cache: {},
    
    async loadItems() {
        if (this.cache.items) {
            this.state.items = this.cache.items;
            this.render();
            return;
        }
        
        this.state.items = await window.api.getItems();
        this.cache.items = this.state.items;
        this.render();
    }
};
```

---

## DEPLOYMENT

### Development
```bash
python -m http.server 8000 -d public/
# Visit http://localhost:8000/login.html
```

### Production
1. Ensure `.env` has production API endpoint
2. Update `api-client.js` baseURL if needed
3. Minify JavaScript files
4. Enable gzip compression
5. Add cache headers for static files
6. Test with production API

---

## RESOURCES

- [Bootstrap 5 Docs](https://getbootstrap.com/docs/5.3/)
- [MDN Fetch API](https://developer.mozilla.org/en-US/docs/Web/API/Fetch_API)
- [JavaScript async/await](https://developer.mozilla.org/en-US/docs/Learn/JavaScript/Asynchronous/Promises)

---

**For questions or issues, refer to PHASE_2_COMPLETION.md for architecture details.**
