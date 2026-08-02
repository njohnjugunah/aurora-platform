/**
 * Appointments Module
 * Handles appointment booking, viewing, and management
 */

const AppointmentsModule = {
    state: {
        appointments: [],
        selectedAppointment: null,
        loading: false,
        error: null,
        filters: {
            date: null,
            status: null,
            staff_id: null
        }
    },

    async init() {
        console.log('Initializing Appointments Module');
        await this.loadAppointments();
        this.setupEventListeners();
    },

    async loadAppointments(filters = {}) {
        this.state.loading = true;
        this.state.error = null;

        try {
            this.state.appointments = await window.api.getAppointments(filters);
            this.render();
        } catch (error) {
            this.state.error = error.message;
            this.showError(error.message);
        } finally {
            this.state.loading = false;
        }
    },

    async createAppointment(data) {
        this.state.loading = true;
        this.state.error = null;

        try {
            const result = await window.api.createAppointment(data);
            this.state.appointments.push(result);
            this.render();
            this.showSuccess('Appointment created successfully');
            return result;
        } catch (error) {
            this.state.error = error.message;
            this.showError(error.message);
            throw error;
        } finally {
            this.state.loading = false;
        }
    },

    async updateAppointment(id, data) {
        this.state.loading = true;
        this.state.error = null;

        try {
            const result = await window.api.updateAppointment(id, data);
            const index = this.state.appointments.findIndex(a => a.id === id);
            if (index > -1) {
                this.state.appointments[index] = result;
            }
            this.render();
            this.showSuccess('Appointment updated successfully');
            return result;
        } catch (error) {
            this.state.error = error.message;
            this.showError(error.message);
            throw error;
        } finally {
            this.state.loading = false;
        }
    },

    async cancelAppointment(id, reason = '') {
        if (!confirm('Are you sure you want to cancel this appointment?')) {
            return;
        }

        this.state.loading = true;
        this.state.error = null;

        try {
            await window.api.cancelAppointment(id, reason);
            this.state.appointments = this.state.appointments.filter(a => a.id !== id);
            this.render();
            this.showSuccess('Appointment cancelled');
        } catch (error) {
            this.state.error = error.message;
            this.showError(error.message);
            throw error;
        } finally {
            this.state.loading = false;
        }
    },

    async getAppointmentDetails(id) {
        try {
            this.state.selectedAppointment = await window.api.getAppointment(id);
            return this.state.selectedAppointment;
        } catch (error) {
            this.state.error = error.message;
            this.showError(error.message);
            throw error;
        }
    },

    render() {
        const container = document.getElementById('appointments-container');
        if (!container) return;

        if (this.state.loading) {
            container.innerHTML = '<div class="alert alert-info">Loading appointments...</div>';
            return;
        }

        if (this.state.appointments.length === 0) {
            container.innerHTML = '<div class="alert alert-warning">No appointments found</div>';
            return;
        }

        container.innerHTML = `
            <div class="appointments-list">
                ${this.state.appointments.map(apt => this.renderAppointmentCard(apt)).join('')}
            </div>
        `;
    },

    renderAppointmentCard(apt) {
        const statusBadge = {
            'pending': 'warning',
            'confirmed': 'success',
            'completed': 'secondary',
            'cancelled': 'danger'
        }[apt.status] || 'info';

        return `
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <h5 class="card-title">${apt.customer_name || 'Customer'}</h5>
                            <p class="card-text">
                                <strong>Service:</strong> ${apt.service_name || '-'}<br>
                                <strong>Staff:</strong> ${apt.staff_name || '-'}<br>
                                <strong>Date:</strong> ${apt.start_time || '-'}<br>
                                <strong>Status:</strong> <span class="badge bg-${statusBadge}">${apt.status || 'pending'}</span>
                            </p>
                            ${apt.notes ? `<p class="card-text"><small>${apt.notes}</small></p>` : ''}
                        </div>
                        <div class="col-md-4 text-end">
                            <button class="btn btn-sm btn-primary" onclick="AppointmentsModule.editAppointment(${apt.id})">Edit</button>
                            <button class="btn btn-sm btn-danger" onclick="AppointmentsModule.cancelAppointment(${apt.id})">Cancel</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    },

    editAppointment(id) {
        alert(`Edit functionality for appointment ${id} - to be implemented`);
    },

    setupEventListeners() {
        const refreshBtn = document.getElementById('refresh-appointments');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => this.loadAppointments());
        }

        const createBtn = document.getElementById('create-appointment');
        if (createBtn) {
            createBtn.addEventListener('click', () => this.showCreateForm());
        }
    },

    showCreateForm() {
        alert('Create appointment form - to be implemented');
    },

    showSuccess(message) {
        // Implement toast notification
        console.log('✓', message);
    },

    showError(message) {
        // Implement error notification
        console.error('✗', message);
    }
};

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => AppointmentsModule.init());
} else {
    AppointmentsModule.init();
}
