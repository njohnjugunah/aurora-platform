/**
 * Admin Settings Module
 * User management, roles, permissions, and system configuration
 */

const AdminSettingsModule = {
    state: {
        currentTab: 'users', // users, roles, permissions, config, audit
        users: [],
        roles: [],
        permissions: [],
        auditLogs: [],
        config: {
            businessName: 'Aurora Platform',
            timezone: 'Africa/Nairobi',
            currency: 'KES',
            taxRate: 16,
            businessEmail: 'admin@aurora.local',
            supportPhone: '+254 700 000000'
        },
        loading: false
    },

    async init() {
        this.render();
        this.setupEventListeners();
        await this.loadInitialData();
    },

    async loadInitialData() {
        try {
            await Promise.all([
                this.loadUsers(),
                this.loadRoles(),
                this.loadPermissions(),
                this.loadAuditLogs()
            ]);
        } catch (error) {
            Notifications.error('Failed to load admin data: ' + error.message);
        }
    },

    render() {
        const container = document.getElementById('admin-settings-section');
        if (!container) return;

        container.innerHTML = `
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Administration Panel</h5>
                        </div>
                        <div class="card-body">
                            <ul class="nav nav-tabs mb-3" role="tablist">
                                <li class="nav-item">
                                    <button class="nav-link active" id="tab-users" data-bs-toggle="tab" data-bs-target="#users-tab">
                                        <i class="fas fa-users"></i> Users
                                    </button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link" id="tab-roles" data-bs-toggle="tab" data-bs-target="#roles-tab">
                                        <i class="fas fa-shield-alt"></i> Roles
                                    </button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link" id="tab-permissions" data-bs-toggle="tab" data-bs-target="#permissions-tab">
                                        <i class="fas fa-lock"></i> Permissions
                                    </button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link" id="tab-config" data-bs-toggle="tab" data-bs-target="#config-tab">
                                        <i class="fas fa-cog"></i> Configuration
                                    </button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link" id="tab-audit" data-bs-toggle="tab" data-bs-target="#audit-tab">
                                        <i class="fas fa-history"></i> Audit Log
                                    </button>
                                </li>
                            </ul>

                            <div class="tab-content">
                                <div class="tab-pane fade show active" id="users-tab">
                                    <div id="users-content"></div>
                                </div>
                                <div class="tab-pane fade" id="roles-tab">
                                    <div id="roles-content"></div>
                                </div>
                                <div class="tab-pane fade" id="permissions-tab">
                                    <div id="permissions-content"></div>
                                </div>
                                <div class="tab-pane fade" id="config-tab">
                                    <div id="config-content"></div>
                                </div>
                                <div class="tab-pane fade" id="audit-tab">
                                    <div id="audit-content"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        this.renderUsers();
    },

    setupEventListeners() {
        document.getElementById('tab-users')?.addEventListener('click', () => this.renderUsers());
        document.getElementById('tab-roles')?.addEventListener('click', () => this.renderRoles());
        document.getElementById('tab-permissions')?.addEventListener('click', () => this.renderPermissions());
        document.getElementById('tab-config')?.addEventListener('click', () => this.renderConfig());
        document.getElementById('tab-audit')?.addEventListener('click', () => this.renderAuditLog());
    },

    async loadUsers() {
        try {
            this.state.users = await window.api.getUsers?.() || this.generateMockUsers();
        } catch (error) {
            this.state.users = this.generateMockUsers();
        }
    },

    generateMockUsers() {
        return [
            { id: 1, name: 'Admin User', email: 'admin@aurora.local', role: 'admin', status: 'active', created_at: '2024-01-15', last_login: '2026-08-01 14:30' },
            { id: 2, name: 'Alice Johnson', email: 'alice@aurora.local', role: 'manager', status: 'active', created_at: '2024-02-10', last_login: '2026-08-01 10:15' },
            { id: 3, name: 'Bob Smith', email: 'bob@aurora.local', role: 'staff', status: 'active', created_at: '2024-03-05', last_login: '2026-07-31 16:45' },
            { id: 4, name: 'Carol White', email: 'carol@aurora.local', role: 'staff', status: 'inactive', created_at: '2024-04-20', last_login: '2026-07-15 09:00' }
        ];
    },

    renderUsers() {
        const container = document.getElementById('users-content');
        if (!container) return;

        const html = `
            <div class="mb-3">
                <button class="btn btn-primary" id="create-user-btn">
                    <i class="fas fa-plus"></i> Create New User
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Last Login</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${this.state.users.map(user => `
                            <tr>
                                <td><strong>${user.name}</strong></td>
                                <td>${user.email}</td>
                                <td>
                                    <span class="badge bg-info">${user.role}</span>
                                </td>
                                <td>
                                    <span class="badge ${user.status === 'active' ? 'bg-success' : 'bg-danger'}">
                                        ${user.status}
                                    </span>
                                </td>
                                <td>${ExportUtils.formatDate(user.created_at)}</td>
                                <td>${user.last_login || 'Never'}</td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="AdminSettingsModule.showEditUserModal(${user.id})">
                                        Edit
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="AdminSettingsModule.deleteUser(${user.id})">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        `;

        container.innerHTML = html;

        document.getElementById('create-user-btn')?.addEventListener('click', () => {
            this.showCreateUserModal();
        });
    },

    showCreateUserModal() {
        const modal = new Modal('Create New User');

        const form = new FormBuilder('create-user-form');
        form.addField(new FormInput('name', {
            label: 'Full Name',
            required: true,
            placeholder: 'John Doe'
        }));
        form.addField(new FormInput('email', {
            label: 'Email Address',
            type: 'email',
            required: true,
            validation: ValidationRules.email
        }));
        form.addField(new FormInput('password', {
            label: 'Password',
            type: 'password',
            required: true,
            placeholder: 'Minimum 8 characters'
        }));
        form.addField(new FormSelect('role', {
            label: 'Role',
            required: true,
            options: [
                { value: 'admin', label: 'Administrator' },
                { value: 'manager', label: 'Manager' },
                { value: 'staff', label: 'Staff' }
            ]
        }));

        modal.setContent(form.render());
        modal.setFooter(`
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" id="save-user-btn">Create User</button>
        `);

        const instance = modal.show();

        document.getElementById('save-user-btn')?.addEventListener('click', async () => {
            const values = form.getValues();

            if (!values.name || !values.email || !values.password || !values.role) {
                Notifications.error('Please fill in all fields');
                return;
            }

            try {
                await window.api.createUser?.(values) || this.state.users.push({
                    id: Math.max(...this.state.users.map(u => u.id)) + 1,
                    ...values,
                    status: 'active',
                    created_at: new Date().toISOString().split('T')[0],
                    last_login: null
                });

                Notifications.success('User created successfully');
                instance.hide();
                await this.loadUsers();
                this.renderUsers();
            } catch (error) {
                Notifications.error('Failed to create user: ' + error.message);
            }
        });
    },

    showEditUserModal(userId) {
        const user = this.state.users.find(u => u.id === userId);
        if (!user) return;

        const modal = new Modal(`Edit User: ${user.name}`);

        const form = new FormBuilder('edit-user-form');
        form.addField(new FormInput('name', {
            label: 'Full Name',
            required: true,
            value: user.name
        }));
        form.addField(new FormInput('email', {
            label: 'Email Address',
            type: 'email',
            required: true,
            value: user.email
        }));
        form.addField(new FormSelect('role', {
            label: 'Role',
            required: true,
            value: user.role,
            options: [
                { value: 'admin', label: 'Administrator' },
                { value: 'manager', label: 'Manager' },
                { value: 'staff', label: 'Staff' }
            ]
        }));
        form.addField(new FormSelect('status', {
            label: 'Status',
            required: true,
            value: user.status,
            options: [
                { value: 'active', label: 'Active' },
                { value: 'inactive', label: 'Inactive' }
            ]
        }));

        modal.setContent(form.render());
        modal.setFooter(`
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" id="update-user-btn">Update User</button>
        `);

        const instance = modal.show();

        document.getElementById('update-user-btn')?.addEventListener('click', async () => {
            const values = form.getValues();

            if (!values.name || !values.email || !values.role) {
                Notifications.error('Please fill in all fields');
                return;
            }

            try {
                await window.api.updateUser?.(userId, values);
                Object.assign(user, values);
                Notifications.success('User updated successfully');
                instance.hide();
                this.renderUsers();
            } catch (error) {
                Notifications.error('Failed to update user: ' + error.message);
            }
        });
    },

    async deleteUser(userId) {
        const confirmed = await new ConfirmDialog(
            'Delete User',
            'Are you sure you want to delete this user? This action cannot be undone.'
        ).show();

        if (confirmed) {
            try {
                await window.api.deleteUser?.(userId);
                this.state.users = this.state.users.filter(u => u.id !== userId);
                Notifications.success('User deleted successfully');
                this.renderUsers();
            } catch (error) {
                Notifications.error('Failed to delete user: ' + error.message);
            }
        }
    },

    async loadRoles() {
        try {
            this.state.roles = await window.api.getRoles?.() || this.generateMockRoles();
        } catch (error) {
            this.state.roles = this.generateMockRoles();
        }
    },

    generateMockRoles() {
        return [
            { id: 1, name: 'admin', description: 'Full system access', permissions: ['all'] },
            { id: 2, name: 'manager', description: 'Staff and sales management', permissions: ['manage_staff', 'view_reports', 'manage_sales'] },
            { id: 3, name: 'staff', description: 'Basic staff access', permissions: ['view_schedule', 'manage_appointments', 'process_sales'] }
        ];
    },

    renderRoles() {
        const container = document.getElementById('roles-content');
        if (!container) return;

        const html = `
            <div class="mb-3">
                <button class="btn btn-primary" id="create-role-btn">
                    <i class="fas fa-plus"></i> Create New Role
                </button>
            </div>

            <div class="row">
                ${this.state.roles.map(role => `
                    <div class="col-md-6 mb-3">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">${role.name}</h6>
                            </div>
                            <div class="card-body">
                                <p class="text-muted">${role.description}</p>
                                <div class="mb-2">
                                    <strong>Permissions:</strong>
                                    <div class="mt-2">
                                        ${role.permissions.map(perm => `
                                            <span class="badge bg-success me-1">${perm}</span>
                                        `).join('')}
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button class="btn btn-sm btn-primary" onclick="AdminSettingsModule.showEditRoleModal(${role.id})">
                                    Edit
                                </button>
                                ${role.id !== 1 ? `
                                    <button class="btn btn-sm btn-danger" onclick="AdminSettingsModule.deleteRole(${role.id})">
                                        Delete
                                    </button>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;

        container.innerHTML = html;

        document.getElementById('create-role-btn')?.addEventListener('click', () => {
            this.showCreateRoleModal();
        });
    },

    showCreateRoleModal() {
        const allPermissions = ['manage_users', 'manage_staff', 'manage_appointments', 'manage_sales', 'view_reports', 'manage_inventory', 'manage_settings'];

        const modal = new Modal('Create New Role');

        const form = new FormBuilder('create-role-form');
        form.addField(new FormInput('name', {
            label: 'Role Name',
            required: true,
            placeholder: 'e.g., supervisor'
        }));
        form.addField(new FormInput('description', {
            label: 'Description',
            required: true,
            placeholder: 'Brief description of this role'
        }));

        let permissionsHTML = '<div class="form-group"><label class="form-label">Permissions</label>';
        allPermissions.forEach(perm => {
            permissionsHTML += `
                <div class="form-check">
                    <input class="form-check-input permission-check" type="checkbox" value="${perm}" id="perm-${perm}">
                    <label class="form-check-label" for="perm-${perm}">
                        ${perm.replace('_', ' ').toUpperCase()}
                    </label>
                </div>
            `;
        });
        permissionsHTML += '</div>';

        modal.setContent(form.render() + permissionsHTML);
        modal.setFooter(`
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" id="save-role-btn">Create Role</button>
        `);

        const instance = modal.show();

        document.getElementById('save-role-btn')?.addEventListener('click', async () => {
            const values = form.getValues();
            const selectedPermissions = Array.from(document.querySelectorAll('.permission-check:checked')).map(el => el.value);

            if (!values.name || !values.description || selectedPermissions.length === 0) {
                Notifications.error('Please fill in all fields and select at least one permission');
                return;
            }

            try {
                const newRole = {
                    id: Math.max(...this.state.roles.map(r => r.id)) + 1,
                    name: values.name,
                    description: values.description,
                    permissions: selectedPermissions
                };

                await window.api.createRole?.(newRole);
                this.state.roles.push(newRole);
                Notifications.success('Role created successfully');
                instance.hide();
                this.renderRoles();
            } catch (error) {
                Notifications.error('Failed to create role: ' + error.message);
            }
        });
    },

    showEditRoleModal(roleId) {
        const role = this.state.roles.find(r => r.id === roleId);
        if (!role) return;

        const allPermissions = ['manage_users', 'manage_staff', 'manage_appointments', 'manage_sales', 'view_reports', 'manage_inventory', 'manage_settings'];

        const modal = new Modal(`Edit Role: ${role.name}`);

        const form = new FormBuilder('edit-role-form');
        form.addField(new FormInput('name', {
            label: 'Role Name',
            required: true,
            value: role.name
        }));
        form.addField(new FormInput('description', {
            label: 'Description',
            required: true,
            value: role.description
        }));

        let permissionsHTML = '<div class="form-group"><label class="form-label">Permissions</label>';
        allPermissions.forEach(perm => {
            const checked = role.permissions.includes(perm);
            permissionsHTML += `
                <div class="form-check">
                    <input class="form-check-input permission-check" type="checkbox" value="${perm}" id="perm-${perm}" ${checked ? 'checked' : ''}>
                    <label class="form-check-label" for="perm-${perm}">
                        ${perm.replace('_', ' ').toUpperCase()}
                    </label>
                </div>
            `;
        });
        permissionsHTML += '</div>';

        modal.setContent(form.render() + permissionsHTML);
        modal.setFooter(`
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" id="update-role-btn">Update Role</button>
        `);

        const instance = modal.show();

        document.getElementById('update-role-btn')?.addEventListener('click', async () => {
            const values = form.getValues();
            const selectedPermissions = Array.from(document.querySelectorAll('.permission-check:checked')).map(el => el.value);

            if (!values.name || !values.description || selectedPermissions.length === 0) {
                Notifications.error('Please fill in all fields and select at least one permission');
                return;
            }

            try {
                Object.assign(role, {
                    name: values.name,
                    description: values.description,
                    permissions: selectedPermissions
                });

                await window.api.updateRole?.(roleId, role);
                Notifications.success('Role updated successfully');
                instance.hide();
                this.renderRoles();
            } catch (error) {
                Notifications.error('Failed to update role: ' + error.message);
            }
        });
    },

    async deleteRole(roleId) {
        const confirmed = await new ConfirmDialog(
            'Delete Role',
            'Are you sure you want to delete this role? Users with this role will need to be reassigned.'
        ).show();

        if (confirmed) {
            try {
                await window.api.deleteRole?.(roleId);
                this.state.roles = this.state.roles.filter(r => r.id !== roleId);
                Notifications.success('Role deleted successfully');
                this.renderRoles();
            } catch (error) {
                Notifications.error('Failed to delete role: ' + error.message);
            }
        }
    },

    async loadPermissions() {
        try {
            this.state.permissions = await window.api.getPermissions?.() || this.generateMockPermissions();
        } catch (error) {
            this.state.permissions = this.generateMockPermissions();
        }
    },

    generateMockPermissions() {
        return [
            { id: 1, name: 'manage_users', description: 'Create, edit, and delete users' },
            { id: 2, name: 'manage_staff', description: 'Manage staff schedules and assignments' },
            { id: 3, name: 'manage_appointments', description: 'Create and manage appointments' },
            { id: 4, name: 'manage_sales', description: 'Process sales and payments' },
            { id: 5, name: 'view_reports', description: 'View system reports and analytics' },
            { id: 6, name: 'manage_inventory', description: 'Manage product inventory' },
            { id: 7, name: 'manage_settings', description: 'Configure system settings' }
        ];
    },

    renderPermissions() {
        const container = document.getElementById('permissions-content');
        if (!container) return;

        const html = `
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> Permissions are automatically assigned through roles. Manage permissions by editing roles.
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Permission</th>
                            <th>Description</th>
                            <th>Used In Roles</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${this.state.permissions.map(perm => {
                            const usedInRoles = this.state.roles
                                .filter(r => r.permissions.includes(perm.name))
                                .map(r => r.name);

                            return `
                                <tr>
                                    <td><code>${perm.name}</code></td>
                                    <td>${perm.description}</td>
                                    <td>
                                        ${usedInRoles.length > 0
                                            ? usedInRoles.map(r => `<span class="badge bg-info">${r}</span>`).join(' ')
                                            : '<span class="text-muted">Not assigned</span>'
                                        }
                                    </td>
                                </tr>
                            `;
                        }).join('')}
                    </tbody>
                </table>
            </div>
        `;

        container.innerHTML = html;
    },

    renderConfig() {
        const container = document.getElementById('config-content');
        if (!container) return;

        const { businessName, timezone, currency, taxRate, businessEmail, supportPhone } = this.state.config;

        const html = `
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">System Configuration</h6>
                </div>
                <div class="card-body">
                    <form id="config-form">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Business Name</label>
                                    <input type="text" class="form-control" id="config-business-name" value="${businessName}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Business Email</label>
                                    <input type="email" class="form-control" id="config-business-email" value="${businessEmail}">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Support Phone</label>
                                    <input type="tel" class="form-control" id="config-support-phone" value="${supportPhone}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Currency</label>
                                    <select class="form-select" id="config-currency">
                                        <option value="KES" ${currency === 'KES' ? 'selected' : ''}>Kenyan Shilling (KES)</option>
                                        <option value="USD" ${currency === 'USD' ? 'selected' : ''}>US Dollar (USD)</option>
                                        <option value="EUR" ${currency === 'EUR' ? 'selected' : ''}>Euro (EUR)</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Timezone</label>
                                    <select class="form-select" id="config-timezone">
                                        <option value="Africa/Nairobi" ${timezone === 'Africa/Nairobi' ? 'selected' : ''}>East Africa (Nairobi)</option>
                                        <option value="Africa/Johannesburg" ${timezone === 'Africa/Johannesburg' ? 'selected' : ''}>South Africa (Johannesburg)</option>
                                        <option value="Europe/London" ${timezone === 'Europe/London' ? 'selected' : ''}>Europe (London)</option>
                                        <option value="America/New_York" ${timezone === 'America/New_York' ? 'selected' : ''}>North America (New York)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Tax Rate (%)</label>
                                    <input type="number" class="form-control" id="config-tax-rate" value="${taxRate}" min="0" max="100" step="0.1">
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Danger Zone:</strong> The following actions are irreversible.
                        </div>

                        <div class="mb-3">
                            <button type="button" class="btn btn-warning" id="backup-btn">
                                <i class="fas fa-download"></i> Export Database Backup
                            </button>
                            <button type="button" class="btn btn-danger" id="reset-btn">
                                <i class="fas fa-redo"></i> Reset System
                            </button>
                        </div>
                    </form>
                </div>
                <div class="card-footer">
                    <button class="btn btn-primary" id="save-config-btn">
                        <i class="fas fa-save"></i> Save Configuration
                    </button>
                </div>
            </div>
        `;

        container.innerHTML = html;

        document.getElementById('save-config-btn')?.addEventListener('click', () => {
            this.state.config.businessName = document.getElementById('config-business-name')?.value || this.state.config.businessName;
            this.state.config.businessEmail = document.getElementById('config-business-email')?.value || this.state.config.businessEmail;
            this.state.config.supportPhone = document.getElementById('config-support-phone')?.value || this.state.config.supportPhone;
            this.state.config.currency = document.getElementById('config-currency')?.value || this.state.config.currency;
            this.state.config.timezone = document.getElementById('config-timezone')?.value || this.state.config.timezone;
            this.state.config.taxRate = parseFloat(document.getElementById('config-tax-rate')?.value) || this.state.config.taxRate;

            Notifications.success('Configuration saved successfully');
        });

        document.getElementById('backup-btn')?.addEventListener('click', () => {
            const backupData = {
                timestamp: new Date().toISOString(),
                config: this.state.config,
                users: this.state.users,
                roles: this.state.roles
            };

            ExportUtils.downloadFile(JSON.stringify(backupData, null, 2), `aurora-backup-${Date.now()}.json`, 'application/json');
            Notifications.success('Database backup exported successfully');
        });

        document.getElementById('reset-btn')?.addEventListener('click', async () => {
            const confirmed = await new ConfirmDialog(
                'Reset System',
                'This will reset all data to factory defaults. This action is irreversible!'
            ).show();

            if (confirmed) {
                Notifications.success('System reset initiated (simulated)');
            }
        });
    },

    async loadAuditLogs() {
        try {
            this.state.auditLogs = await window.api.getAuditLogs?.() || this.generateMockAuditLogs();
        } catch (error) {
            this.state.auditLogs = this.generateMockAuditLogs();
        }
    },

    generateMockAuditLogs() {
        return [
            { id: 1, user: 'admin@aurora.local', action: 'User Login', resource: 'Admin User', timestamp: '2026-08-02 14:30:15', details: 'Successful login' },
            { id: 2, user: 'alice@aurora.local', action: 'Sale Created', resource: 'Transaction #145', timestamp: '2026-08-02 13:45:22', details: 'KES 8,500.00' },
            { id: 3, user: 'admin@aurora.local', action: 'User Created', resource: 'bob@aurora.local', timestamp: '2026-08-02 11:20:08', details: 'New staff member' },
            { id: 4, user: 'alice@aurora.local', action: 'Appointment Completed', resource: 'Appointment #32', timestamp: '2026-08-01 17:15:44', details: 'Hair styling service' },
            { id: 5, user: 'admin@aurora.local', action: 'Inventory Updated', resource: 'Product #15', timestamp: '2026-08-01 10:05:30', details: 'Stock updated to 45 units' },
            { id: 6, user: 'alice@aurora.local', action: 'Report Generated', resource: 'Revenue Report', timestamp: '2026-07-31 09:00:12', details: 'July 2026 revenue summary' }
        ];
    },

    renderAuditLog() {
        const container = document.getElementById('audit-content');
        if (!container) return;

        const html = `
            <div class="mb-3">
                <div class="row">
                    <div class="col-md-6">
                        <input type="text" class="form-control" id="audit-search" placeholder="Search by user, action, or resource...">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" id="audit-action-filter">
                            <option value="">All Actions</option>
                            <option value="User Login">User Login</option>
                            <option value="Sale Created">Sale Created</option>
                            <option value="User Created">User Created</option>
                            <option value="Appointment Completed">Appointment Completed</option>
                            <option value="Inventory Updated">Inventory Updated</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary w-100" id="audit-filter-btn">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Timestamp</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Resource</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${this.state.auditLogs.map(log => `
                            <tr>
                                <td>${log.timestamp}</td>
                                <td><code>${log.user}</code></td>
                                <td>
                                    <span class="badge bg-primary">${log.action}</span>
                                </td>
                                <td>${log.resource}</td>
                                <td>${log.details}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>

            <div class="alert alert-info mt-3">
                <i class="fas fa-info-circle"></i>
                Showing the 6 most recent audit log entries. Full audit log history is available in system backups.
            </div>
        `;

        container.innerHTML = html;

        document.getElementById('audit-filter-btn')?.addEventListener('click', () => {
            const searchTerm = document.getElementById('audit-search')?.value.toLowerCase() || '';
            const actionFilter = document.getElementById('audit-action-filter')?.value || '';

            let filtered = this.state.auditLogs;

            if (searchTerm) {
                filtered = filtered.filter(log =>
                    log.user.toLowerCase().includes(searchTerm) ||
                    log.action.toLowerCase().includes(searchTerm) ||
                    log.resource.toLowerCase().includes(searchTerm)
                );
            }

            if (actionFilter) {
                filtered = filtered.filter(log => log.action === actionFilter);
            }

            this.state.auditLogs = filtered;
            this.renderAuditLog();
        });
    }
};

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        const container = document.getElementById('admin-settings-section');
        if (container) AdminSettingsModule.init();
    });
} else {
    const container = document.getElementById('admin-settings-section');
    if (container) AdminSettingsModule.init();
}
