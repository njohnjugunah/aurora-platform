/**
 * Admin Dashboard Module
 * Displays business metrics, reports, and management interfaces
 */

const AdminDashboardModule = {
    state: {
        dashboard: null,
        revenueReport: null,
        appointmentReport: null,
        staffReport: null,
        loading: false,
        error: null,
        dateRange: {
            start: null,
            end: null
        }
    },

    async init() {
        this.setDefaultDateRange();
        await this.loadDashboard();
        this.setupEventListeners();
    },

    setDefaultDateRange() {
        const end = new Date();
        const start = new Date();
        start.setDate(start.getDate() - 30);

        this.state.dateRange = {
            start: start.toISOString().split('T')[0],
            end: end.toISOString().split('T')[0]
        };
    },

    async loadDashboard() {
        this.state.loading = true;
        this.state.error = null;

        try {
            this.state.dashboard = await window.api.getDashboard();
            this.state.revenueReport = await window.api.getRevenueReport(this.state.dateRange);
            this.state.appointmentReport = await window.api.getAppointmentReport(this.state.dateRange);
            this.state.staffReport = await window.api.getStaffReport(this.state.dateRange);

            this.render();
        } catch (error) {
            this.state.error = error.message;
            this.showError('Failed to load dashboard: ' + error.message);
        } finally {
            this.state.loading = false;
        }
    },

    updateDateRange(start, end) {
        this.state.dateRange = { start, end };
        this.loadDashboard();
    },

    render() {
        const container = document.getElementById('admin-dashboard');
        if (!container) return;

        if (this.state.loading) {
            container.innerHTML = '<div class="alert alert-info">Loading dashboard...</div>';
            return;
        }

        container.innerHTML = `
            <div class="dashboard-container">
                ${this.renderDateFilter()}
                ${this.renderKPIs()}
                <div class="row mt-4">
                    <div class="col-md-6">
                        ${this.renderRevenueChart()}
                    </div>
                    <div class="col-md-6">
                        ${this.renderAppointmentChart()}
                    </div>
                </div>
                <div class="row mt-4">
                    <div class="col-12">
                        ${this.renderStaffPerformance()}
                    </div>
                </div>
            </div>
        `;
    },

    renderDateFilter() {
        return `
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start-date" value="${this.state.dateRange.start}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end-date" value="${this.state.dateRange.end}">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button class="btn btn-primary w-100" onclick="AdminDashboardModule.applyDateFilter()">Apply Filter</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    },

    renderKPIs() {
        const kpis = [
            {
                title: 'Total Revenue',
                value: `KES ${(this.state.revenueReport?.total_revenue || 0).toLocaleString()}`,
                trend: this.state.revenueReport?.revenue_trend || 0,
                icon: '💰'
            },
            {
                title: 'Total Appointments',
                value: this.state.appointmentReport?.total_appointments || 0,
                trend: this.state.appointmentReport?.appointment_trend || 0,
                icon: '📅'
            },
            {
                title: 'Active Customers',
                value: this.state.dashboard?.active_customers || 0,
                trend: 0,
                icon: '👥'
            },
            {
                title: 'Staff Count',
                value: this.state.dashboard?.staff_count || 0,
                trend: 0,
                icon: '👔'
            }
        ];

        return `
            <div class="row">
                ${kpis.map(kpi => `
                    <div class="col-md-3 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <small class="text-muted">${kpi.title}</small>
                                        <h4 class="mb-0">${kpi.value}</h4>
                                    </div>
                                    <div style="font-size: 2rem;">${kpi.icon}</div>
                                </div>
                                ${kpi.trend !== 0 ? `
                                    <small class="${kpi.trend > 0 ? 'text-success' : 'text-danger'}">
                                        ${kpi.trend > 0 ? '↑' : '↓'} ${Math.abs(kpi.trend)}%
                                    </small>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    },

    renderRevenueChart() {
        const data = this.state.revenueReport?.daily_revenue || [];
        const maxRevenue = Math.max(...data.map(d => d.revenue || 0), 1);

        return `
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Revenue Trend</h5>
                </div>
                <div class="card-body">
                    <div style="height: 300px; position: relative;">
                        <div class="chart-placeholder">
                            <p class="text-muted text-center">Revenue chart - ${data.length} data points</p>
                            ${data.slice(-7).map(d => `
                                <div style="display: inline-block; margin: 5px; text-align: center;">
                                    <div style="height: ${(d.revenue / maxRevenue) * 200}px; width: 30px; background: #007bff; display: inline-block;"></div>
                                    <div style="font-size: 0.75rem;">KES ${(d.revenue / 1000).toFixed(0)}k</div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                </div>
            </div>
        `;
    },

    renderAppointmentChart() {
        const data = this.state.appointmentReport?.by_status || {};

        return `
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Appointments by Status</h5>
                </div>
                <div class="card-body">
                    <div class="chart-placeholder">
                        ${Object.entries(data).map(([status, count]) => `
                            <div class="mb-2">
                                <div class="d-flex justify-content-between mb-1">
                                    <small>${status}</small>
                                    <small><strong>${count}</strong></small>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar" style="width: ${(count / Object.values(data).reduce((a, b) => a + b, 1)) * 100}%"></div>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            </div>
        `;
    },

    renderStaffPerformance() {
        const staff = this.state.staffReport?.staff_performance || [];

        return `
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Staff Performance</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Staff Member</th>
                                    <th>Appointments</th>
                                    <th>Revenue</th>
                                    <th>Rating</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${staff.map(s => `
                                    <tr>
                                        <td>${s.name || '-'}</td>
                                        <td>${s.appointments || 0}</td>
                                        <td>KES ${(s.revenue || 0).toLocaleString()}</td>
                                        <td>
                                            ${this.renderStars(s.rating || 0)}
                                            <small>${s.rating || 0}</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-${s.status === 'active' ? 'success' : 'secondary'}">
                                                ${s.status || 'active'}
                                            </span>
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;
    },

    renderStars(rating) {
        const fullStars = Math.floor(rating);
        const hasHalfStar = rating % 1 >= 0.5;
        let stars = '';

        for (let i = 0; i < fullStars; i++) {
            stars += '★';
        }
        if (hasHalfStar && fullStars < 5) {
            stars += '½';
        }
        for (let i = fullStars + (hasHalfStar ? 1 : 0); i < 5; i++) {
            stars += '☆';
        }

        return stars;
    },

    applyDateFilter() {
        const startInput = document.getElementById('start-date');
        const endInput = document.getElementById('end-date');

        if (startInput && endInput) {
            this.updateDateRange(startInput.value, endInput.value);
        }
    },

    setupEventListeners() {
        // Setup auto-refresh (optional)
        // setInterval(() => this.loadDashboard(), 60000); // Refresh every minute
    },

    showSuccess(message) {
    },

    showError(message) {
        console.error('✗', message);
    }
};

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => AdminDashboardModule.init());
} else {
    AdminDashboardModule.init();
}
