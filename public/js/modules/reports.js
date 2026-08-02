/**
 * Advanced Reports Module
 * Generate revenue, appointment, and staff performance reports
 */

const ReportsModule = {
    state: {
        currentReport: 'revenue', // revenue, appointments, staff
        reports: {
            revenue: {
                startDate: this.getDefaultStartDate(),
                endDate: this.getDefaultEndDate(),
                data: [],
                total: 0
            },
            appointments: {
                startDate: this.getDefaultStartDate(),
                endDate: this.getDefaultEndDate(),
                status: null, // pending, confirmed, completed, cancelled
                data: [],
                total: 0
            },
            staff: {
                startDate: this.getDefaultStartDate(),
                endDate: this.getDefaultEndDate(),
                data: [],
                sortBy: 'revenue' // revenue, appointments, rating
            }
        },
        emailSchedule: {
            enabled: false,
            recipient: '',
            frequency: 'weekly', // daily, weekly, monthly
            time: '09:00',
            reports: ['revenue', 'appointments'] // which reports to include
        }
    },

    getDefaultStartDate() {
        const date = new Date();
        date.setDate(date.getDate() - 30);
        return date.toISOString().split('T')[0];
    },

    getDefaultEndDate() {
        return new Date().toISOString().split('T')[0];
    },

    async init() {
        this.render();
        this.setupEventListeners();
        await this.loadRevenueReport();
    },

    render() {
        const container = document.getElementById('reports-section');
        if (!container) return;

        container.innerHTML = `
            <div class="row">
                <div class="col-md-12">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Report Builder</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="btn-group-vertical w-100" role="group">
                                        <button class="btn btn-outline-primary text-start active" id="report-revenue">
                                            <i class="fas fa-chart-line"></i> Revenue Report
                                        </button>
                                        <button class="btn btn-outline-primary text-start" id="report-appointments">
                                            <i class="fas fa-calendar"></i> Appointment Report
                                        </button>
                                        <button class="btn btn-outline-primary text-start" id="report-staff">
                                            <i class="fas fa-users"></i> Staff Performance
                                        </button>
                                        <button class="btn btn-outline-info text-start" id="report-email-schedule">
                                            <i class="fas fa-envelope"></i> Email Schedule
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-9">
                                    <div id="report-filters"></div>
                                    <div id="report-content" class="mt-3"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    },

    setupEventListeners() {
        document.getElementById('report-revenue')?.addEventListener('click', () => {
            this.selectReport('revenue');
        });

        document.getElementById('report-appointments')?.addEventListener('click', () => {
            this.selectReport('appointments');
        });

        document.getElementById('report-staff')?.addEventListener('click', () => {
            this.selectReport('staff');
        });

        document.getElementById('report-email-schedule')?.addEventListener('click', () => {
            this.selectReport('email-schedule');
        });
    },

    selectReport(reportType) {
        this.state.currentReport = reportType;

        // Update active button
        document.querySelectorAll('[id^="report-"]').forEach(btn => {
            btn.classList.remove('active');
        });
        document.getElementById(`report-${reportType}`)?.classList.add('active');

        if (reportType === 'revenue') {
            this.renderRevenueReport();
        } else if (reportType === 'appointments') {
            this.renderAppointmentsReport();
        } else if (reportType === 'staff') {
            this.renderStaffReport();
        } else if (reportType === 'email-schedule') {
            this.renderEmailSchedule();
        }
    },

    async loadRevenueReport() {
        const { startDate, endDate } = this.state.reports.revenue;

        try {
            // Fetch from API
            const data = await window.api.getReports?.('revenue', {
                start_date: startDate,
                end_date: endDate
            }) || this.generateMockRevenueData(startDate, endDate);

            this.state.reports.revenue.data = data;
            this.state.reports.revenue.total = data.reduce((sum, row) => sum + (row.total_amount || 0), 0);
            this.renderRevenueReport();
        } catch (error) {
            Notifications.error('Failed to load revenue report: ' + error.message);
        }
    },

    generateMockRevenueData(startDate, endDate) {
        const data = [];
        const start = new Date(startDate);
        const end = new Date(endDate);
        const current = new Date(start);

        while (current <= end) {
            const dayOfWeek = current.getDay();
            // Weekend sales are lower
            const baseSales = dayOfWeek === 0 || dayOfWeek === 6 ? 4000 : 8000;
            const variance = Math.random() * 3000;
            const totalAmount = baseSales + variance;

            data.push({
                date: current.toISOString().split('T')[0],
                transactions: Math.floor(Math.random() * 15) + 8,
                customers: Math.floor(Math.random() * 12) + 5,
                average_transaction: (totalAmount / 10).toFixed(2),
                total_amount: totalAmount.toFixed(2)
            });

            current.setDate(current.getDate() + 1);
        }

        return data;
    },

    renderRevenueReport() {
        const { startDate, endDate, data, total } = this.state.reports.revenue;

        const filtersHTML = `
            <div class="row">
                <div class="col-md-3">
                    <label class="form-label">Start Date</label>
                    <input type="date" class="form-control" id="revenue-start-date" value="${startDate}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">End Date</label>
                    <input type="date" class="form-control" id="revenue-end-date" value="${endDate}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button class="btn btn-primary w-100" id="revenue-filter-btn">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                </div>
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <div class="btn-group w-100" role="group">
                        <button class="btn btn-outline-secondary btn-sm" id="export-csv-revenue">
                            <i class="fas fa-download"></i> CSV
                        </button>
                        <button class="btn btn-outline-secondary btn-sm" id="export-html-revenue">
                            <i class="fas fa-print"></i> Print
                        </button>
                    </div>
                </div>
            </div>
        `;

        const contentHTML = `
            <div class="mt-3">
                <div class="row">
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body">
                                <small class="text-muted">Total Revenue</small>
                                <h3>KES ${ExportUtils.formatCurrency(total).replace('KES ', '')}</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body">
                                <small class="text-muted">Transactions</small>
                                <h3>${data.reduce((sum, row) => sum + (row.transactions || 0), 0)}</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body">
                                <small class="text-muted">Customers</small>
                                <h3>${data.reduce((sum, row) => sum + (row.customers || 0), 0)}</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body">
                                <small class="text-muted">Average Transaction</small>
                                <h3>KES ${data.length > 0 ? (data.reduce((sum, row) => sum + parseFloat(row.average_transaction || 0), 0) / data.length).toFixed(2) : '0'}</h3>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive mt-3">
                    <table class="table table-sm table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Transactions</th>
                                <th>Customers</th>
                                <th>Avg Transaction</th>
                                <th>Total Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${data.map(row => `
                                <tr>
                                    <td>${ExportUtils.formatDate(row.date)}</td>
                                    <td>${row.transactions}</td>
                                    <td>${row.customers}</td>
                                    <td>KES ${parseFloat(row.average_transaction).toFixed(2)}</td>
                                    <td><strong>KES ${parseFloat(row.total_amount).toFixed(2)}</strong></td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
        `;

        const filtersContainer = document.getElementById('report-filters');
        const contentContainer = document.getElementById('report-content');

        if (filtersContainer) filtersContainer.innerHTML = filtersHTML;
        if (contentContainer) contentContainer.innerHTML = contentHTML;

        // Wire up filters
        document.getElementById('revenue-filter-btn')?.addEventListener('click', async () => {
            this.state.reports.revenue.startDate = document.getElementById('revenue-start-date')?.value || this.state.reports.revenue.startDate;
            this.state.reports.revenue.endDate = document.getElementById('revenue-end-date')?.value || this.state.reports.revenue.endDate;
            await this.loadRevenueReport();
        });

        document.getElementById('export-csv-revenue')?.addEventListener('click', () => {
            ExportUtils.exportToCSV(this.state.reports.revenue.data, `revenue-report-${this.state.reports.revenue.startDate}-to-${this.state.reports.revenue.endDate}.csv`);
        });

        document.getElementById('export-html-revenue')?.addEventListener('click', () => {
            const html = ExportUtils.exportToHTML(this.state.reports.revenue.data, 'Revenue Report', {
                'Start Date': this.state.reports.revenue.startDate,
                'End Date': this.state.reports.revenue.endDate,
                'Total Revenue': `KES ${ExportUtils.formatCurrency(this.state.reports.revenue.total).replace('KES ', '')}`
            });
            ExportUtils.printReport(html, 'Revenue Report');
        });
    },

    async loadAppointmentsReport() {
        const { startDate, endDate, status } = this.state.reports.appointments;

        try {
            const data = await window.api.getReports?.('appointments', {
                start_date: startDate,
                end_date: endDate,
                status: status
            }) || this.generateMockAppointmentData(startDate, endDate, status);

            this.state.reports.appointments.data = data;
            this.state.reports.appointments.total = data.length;
            this.renderAppointmentsReport();
        } catch (error) {
            Notifications.error('Failed to load appointments report: ' + error.message);
        }
    },

    generateMockAppointmentData(startDate, endDate, filterStatus) {
        const statuses = ['pending', 'confirmed', 'completed', 'cancelled'];
        const data = [];

        for (let i = 0; i < 25; i++) {
            const appointmentStatus = filterStatus || statuses[Math.floor(Math.random() * statuses.length)];
            const start = new Date(startDate);
            const end = new Date(endDate);
            const randomDate = new Date(start.getTime() + Math.random() * (end.getTime() - start.getTime()));

            data.push({
                id: i + 1,
                customer: `Customer ${i + 1}`,
                service: ['Hair Cut', 'Spa', 'Massage', 'Facial'][Math.floor(Math.random() * 4)],
                date: randomDate.toISOString().split('T')[0],
                time: `${String(Math.floor(Math.random() * 12) + 8).padStart(2, '0')}:${String(Math.floor(Math.random() * 60)).padStart(2, '0')}`,
                duration: [30, 60, 90][Math.floor(Math.random() * 3)],
                status: appointmentStatus,
                notes: 'Regular customer'
            });
        }

        return data;
    },

    renderAppointmentsReport() {
        const { startDate, endDate, status, data, total } = this.state.reports.appointments;

        const filtersHTML = `
            <div class="row">
                <div class="col-md-2">
                    <label class="form-label">Start Date</label>
                    <input type="date" class="form-control" id="appt-start-date" value="${startDate}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">End Date</label>
                    <input type="date" class="form-control" id="appt-end-date" value="${endDate}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select class="form-select" id="appt-status">
                        <option value="">All Statuses</option>
                        <option value="pending" ${status === 'pending' ? 'selected' : ''}>Pending</option>
                        <option value="confirmed" ${status === 'confirmed' ? 'selected' : ''}>Confirmed</option>
                        <option value="completed" ${status === 'completed' ? 'selected' : ''}>Completed</option>
                        <option value="cancelled" ${status === 'cancelled' ? 'selected' : ''}>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button class="btn btn-primary w-100" id="appt-filter-btn">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                </div>
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <div class="btn-group w-100" role="group">
                        <button class="btn btn-outline-secondary btn-sm" id="export-csv-appt">
                            <i class="fas fa-download"></i> CSV
                        </button>
                        <button class="btn btn-outline-secondary btn-sm" id="export-html-appt">
                            <i class="fas fa-print"></i> Print
                        </button>
                    </div>
                </div>
            </div>
        `;

        const statusColors = {
            'pending': 'warning',
            'confirmed': 'info',
            'completed': 'success',
            'cancelled': 'danger'
        };

        const contentHTML = `
            <div class="mt-3">
                <div class="row">
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body">
                                <small class="text-muted">Total Appointments</small>
                                <h3>${total}</h3>
                            </div>
                        </div>
                    </div>
                    ${Object.entries(statusColors).map(([statusKey, color]) => {
                        const count = data.filter(a => a.status === statusKey).length;
                        return `
                            <div class="col-md-3">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <small class="text-muted">${statusKey.charAt(0).toUpperCase() + statusKey.slice(1)}</small>
                                        <h3><span class="badge bg-${color}">${count}</span></h3>
                                    </div>
                                </div>
                            </div>
                        `;
                    }).join('')}
                </div>

                <div class="table-responsive mt-3">
                    <table class="table table-sm table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Customer</th>
                                <th>Service</th>
                                <th>Duration</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${data.map(row => `
                                <tr>
                                    <td>${ExportUtils.formatDate(row.date)}</td>
                                    <td>${row.time}</td>
                                    <td>${row.customer}</td>
                                    <td>${row.service}</td>
                                    <td>${row.duration} min</td>
                                    <td>
                                        <span class="badge bg-${statusColors[row.status]}">
                                            ${row.status.charAt(0).toUpperCase() + row.status.slice(1)}
                                        </span>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
        `;

        const filtersContainer = document.getElementById('report-filters');
        const contentContainer = document.getElementById('report-content');

        if (filtersContainer) filtersContainer.innerHTML = filtersHTML;
        if (contentContainer) contentContainer.innerHTML = contentHTML;

        // Wire up filters
        document.getElementById('appt-filter-btn')?.addEventListener('click', async () => {
            this.state.reports.appointments.startDate = document.getElementById('appt-start-date')?.value || this.state.reports.appointments.startDate;
            this.state.reports.appointments.endDate = document.getElementById('appt-end-date')?.value || this.state.reports.appointments.endDate;
            this.state.reports.appointments.status = document.getElementById('appt-status')?.value || null;
            await this.loadAppointmentsReport();
        });

        document.getElementById('export-csv-appt')?.addEventListener('click', () => {
            ExportUtils.exportToCSV(this.state.reports.appointments.data, `appointments-report-${this.state.reports.appointments.startDate}-to-${this.state.reports.appointments.endDate}.csv`);
        });

        document.getElementById('export-html-appt')?.addEventListener('click', () => {
            const html = ExportUtils.exportToHTML(this.state.reports.appointments.data, 'Appointments Report', {
                'Start Date': this.state.reports.appointments.startDate,
                'End Date': this.state.reports.appointments.endDate,
                'Total Appointments': this.state.reports.appointments.total
            });
            ExportUtils.printReport(html, 'Appointments Report');
        });
    },

    async loadStaffReport() {
        const { startDate, endDate } = this.state.reports.staff;

        try {
            const data = await window.api.getReports?.('staff', {
                start_date: startDate,
                end_date: endDate
            }) || this.generateMockStaffData();

            this.state.reports.staff.data = data;
            this.renderStaffReport();
        } catch (error) {
            Notifications.error('Failed to load staff report: ' + error.message);
        }
    },

    generateMockStaffData() {
        return [
            { id: 1, name: 'Alice Johnson', appointments: 45, revenue: 18500, rating: 4.8 },
            { id: 2, name: 'Bob Smith', appointments: 38, revenue: 15200, rating: 4.6 },
            { id: 3, name: 'Carol White', appointments: 52, revenue: 21000, rating: 4.9 },
            { id: 4, name: 'David Brown', appointments: 41, revenue: 16800, rating: 4.7 },
            { id: 5, name: 'Emily Davis', appointments: 35, revenue: 14200, rating: 4.5 }
        ];
    },

    renderStaffReport() {
        const { startDate, endDate, data, sortBy } = this.state.reports.staff;

        // Sort data
        let sortedData = [...data];
        if (sortBy === 'revenue') {
            sortedData.sort((a, b) => b.revenue - a.revenue);
        } else if (sortBy === 'appointments') {
            sortedData.sort((a, b) => b.appointments - a.appointments);
        } else if (sortBy === 'rating') {
            sortedData.sort((a, b) => b.rating - a.rating);
        }

        const filtersHTML = `
            <div class="row">
                <div class="col-md-3">
                    <label class="form-label">Start Date</label>
                    <input type="date" class="form-control" id="staff-start-date" value="${startDate}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">End Date</label>
                    <input type="date" class="form-control" id="staff-end-date" value="${endDate}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Sort By</label>
                    <select class="form-select" id="staff-sort">
                        <option value="revenue" ${sortBy === 'revenue' ? 'selected' : ''}>Revenue</option>
                        <option value="appointments" ${sortBy === 'appointments' ? 'selected' : ''}>Appointments</option>
                        <option value="rating" ${sortBy === 'rating' ? 'selected' : ''}>Rating</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button class="btn btn-primary w-100" id="staff-filter-btn">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button class="btn btn-outline-secondary btn-sm w-100" id="export-csv-staff">
                        <i class="fas fa-download"></i> CSV
                    </button>
                </div>
            </div>
        `;

        const contentHTML = `
            <div class="mt-3">
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Staff Member</th>
                                <th>Appointments</th>
                                <th>Total Revenue</th>
                                <th>Avg per Appointment</th>
                                <th>Rating</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${sortedData.map(staff => {
                                const avgRevenue = staff.appointments > 0 ? (staff.revenue / staff.appointments).toFixed(2) : '0';
                                return `
                                    <tr>
                                        <td><strong>${staff.name}</strong></td>
                                        <td>${staff.appointments}</td>
                                        <td>KES ${parseFloat(staff.revenue).toFixed(2)}</td>
                                        <td>KES ${parseFloat(avgRevenue).toFixed(2)}</td>
                                        <td>
                                            <span class="badge bg-success">${staff.rating} ⭐</span>
                                        </td>
                                    </tr>
                                `;
                            }).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
        `;

        const filtersContainer = document.getElementById('report-filters');
        const contentContainer = document.getElementById('report-content');

        if (filtersContainer) filtersContainer.innerHTML = filtersHTML;
        if (contentContainer) contentContainer.innerHTML = contentHTML;

        // Wire up filters
        document.getElementById('staff-filter-btn')?.addEventListener('click', async () => {
            this.state.reports.staff.startDate = document.getElementById('staff-start-date')?.value || this.state.reports.staff.startDate;
            this.state.reports.staff.endDate = document.getElementById('staff-end-date')?.value || this.state.reports.staff.endDate;
            this.state.reports.staff.sortBy = document.getElementById('staff-sort')?.value || 'revenue';
            await this.loadStaffReport();
        });

        document.getElementById('export-csv-staff')?.addEventListener('click', () => {
            ExportUtils.exportToCSV(this.state.reports.staff.data, `staff-performance-report-${this.state.reports.staff.startDate}-to-${this.state.reports.staff.endDate}.csv`);
        });
    },

    renderEmailSchedule() {
        const { enabled, recipient, frequency, time, reports } = this.state.emailSchedule;

        const filtersHTML = '';
        const contentHTML = `
            <div class="mt-3">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Email Report Schedule</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="email-enabled" ${enabled ? 'checked' : ''}>
                                <label class="form-check-label" for="email-enabled">
                                    Enable automatic email reports
                                </label>
                            </div>
                        </div>

                        <div id="email-config" ${enabled ? '' : 'style="display:none"'}>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Recipient Email</label>
                                        <input type="email" class="form-control" id="email-recipient" value="${recipient}" placeholder="admin@example.com">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Frequency</label>
                                        <select class="form-select" id="email-frequency">
                                            <option value="daily" ${frequency === 'daily' ? 'selected' : ''}>Daily</option>
                                            <option value="weekly" ${frequency === 'weekly' ? 'selected' : ''}>Weekly</option>
                                            <option value="monthly" ${frequency === 'monthly' ? 'selected' : ''}>Monthly</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Send Time</label>
                                        <input type="time" class="form-control" id="email-time" value="${time}">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Include Reports</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="report-revenue-email" ${reports.includes('revenue') ? 'checked' : ''}>
                                    <label class="form-check-label" for="report-revenue-email">
                                        Revenue Report
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="report-appt-email" ${reports.includes('appointments') ? 'checked' : ''}>
                                    <label class="form-check-label" for="report-appt-email">
                                        Appointment Report
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="report-staff-email" ${reports.includes('staff') ? 'checked' : ''}>
                                    <label class="form-check-label" for="report-staff-email">
                                        Staff Performance Report
                                    </label>
                                </div>
                            </div>

                            <button class="btn btn-primary" id="save-email-schedule">
                                <i class="fas fa-save"></i> Save Schedule
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        const filtersContainer = document.getElementById('report-filters');
        const contentContainer = document.getElementById('report-content');

        if (filtersContainer) filtersContainer.innerHTML = filtersHTML;
        if (contentContainer) contentContainer.innerHTML = contentHTML;

        // Wire up email schedule
        document.getElementById('email-enabled')?.addEventListener('change', (e) => {
            const config = document.getElementById('email-config');
            if (config) {
                config.style.display = e.target.checked ? 'block' : 'none';
            }
        });

        document.getElementById('save-email-schedule')?.addEventListener('click', () => {
            this.state.emailSchedule.enabled = document.getElementById('email-enabled')?.checked || false;
            this.state.emailSchedule.recipient = document.getElementById('email-recipient')?.value || '';
            this.state.emailSchedule.frequency = document.getElementById('email-frequency')?.value || 'weekly';
            this.state.emailSchedule.time = document.getElementById('email-time')?.value || '09:00';
            this.state.emailSchedule.reports = [
                ...(document.getElementById('report-revenue-email')?.checked ? ['revenue'] : []),
                ...(document.getElementById('report-appt-email')?.checked ? ['appointments'] : []),
                ...(document.getElementById('report-staff-email')?.checked ? ['staff'] : [])
            ];

            Notifications.success('Email schedule saved successfully');
        });
    }
};

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => ReportsModule.init());
} else {
    ReportsModule.init();
}
