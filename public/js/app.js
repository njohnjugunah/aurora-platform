// Aurora Platform - Main Application
class AuroraApp {
    constructor() {
        this.isAuthenticated = this.checkAuthentication();
        this.init();
    }

    checkAuthentication() {
        const token = localStorage.getItem('auth_token');
        return !!token;
    }

    async init() {
        if (!this.isAuthenticated) {
            this.showLoginPage();
        } else {
            this.showDashboard();
        }
    }

    showLoginPage() {
        const app = document.getElementById('app');
        app.innerHTML = `
            <div class="login-container">
                <div class="login-card">
                    <div class="login-header">
                        <h2>Aurora</h2>
                        <p>Beauty Salon Management System</p>
                    </div>
                    <form id="loginForm" class="login-body">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" id="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" id="password" name="password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Sign In</button>
                    </form>
                </div>
            </div>
        `;

        document.getElementById('loginForm').addEventListener('submit', (e) => this.handleLogin(e));
    }

    async handleLogin(e) {
        e.preventDefault();

        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;

        try {
            const response = await fetch('/api/v1/auth/login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ email, password })
            });

            const result = await response.json();

            if (result.success) {
                localStorage.setItem('auth_token', result.data.token);
                this.isAuthenticated = true;
                this.showDashboard();
            } else {
                alert('Login failed: ' + result.error.message);
            }
        } catch (error) {
            alert('Login error: ' + error.message);
        }
    }

    showDashboard() {
        const app = document.getElementById('app');
        app.innerHTML = `
            <div class="d-flex">
                <nav class="sidebar bg-dark text-white">
                    <div class="sidebar-header p-3 border-bottom">
                        <h5>Aurora</h5>
                    </div>
                    <ul class="nav flex-column p-2">
                        <li class="nav-item">
                            <a href="#" class="nav-link active" onclick="app.showPage('dashboard')">
                                <i class="bi bi-speedometer2 me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link" onclick="app.showPage('appointments')">
                                <i class="bi bi-calendar-check me-2"></i>Appointments
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link" onclick="app.showPage('sales')">
                                <i class="bi bi-receipt me-2"></i>Sales
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link" onclick="app.logout()">
                                <i class="bi bi-box-arrow-right me-2"></i>Logout
                            </a>
                        </li>
                    </ul>
                </nav>

                <main class="flex-grow-1">
                    <header class="bg-light border-bottom sticky-top">
                        <div class="container-fluid p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <h1 class="h4 mb-0">Dashboard</h1>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                        <i class="bi bi-person-circle me-2"></i>Admin
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" href="#" onclick="app.logout()">Logout</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </header>

                    <div class="container-fluid p-4">
                        <div id="pageContent"></div>
                    </div>
                </main>
            </div>
        `;

        this.showPage('dashboard');
    }

    showPage(page) {
        const content = document.getElementById('pageContent');

        if (page === 'dashboard') {
            content.innerHTML = `
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <p class="text-muted mb-1">Today's Revenue</p>
                                <h4>KES 0</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <p class="text-muted mb-1">Appointments</p>
                                <h4>0</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <p class="text-muted mb-1">New Customers</p>
                                <h4>0</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <p class="text-muted mb-1">Pending Tasks</p>
                                <h4>0</h4>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">Welcome to Aurora Platform</h6>
                    </div>
                    <div class="card-body">
                        <p>This is your dashboard. Navigate using the menu on the left to access appointments, sales, and customers.</p>
                    </div>
                </div>
            `;
        } else if (page === 'appointments') {
            content.innerHTML = `
                <h2 class="mb-4">Appointments</h2>
                <div class="card shadow-sm">
                    <div class="card-body text-center py-5">
                        <p class="text-muted">No appointments to display</p>
                    </div>
                </div>
            `;
        } else if (page === 'sales') {
            content.innerHTML = `
                <h2 class="mb-4">Sales</h2>
                <div class="card shadow-sm">
                    <div class="card-body text-center py-5">
                        <p class="text-muted">No sales to display</p>
                    </div>
                </div>
            `;
        }
    }

    logout() {
        localStorage.removeItem('auth_token');
        this.isAuthenticated = false;
        location.reload();
    }
}

// Initialize app when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.app = new AuroraApp();
});
