/**
 * Form Components Library
 * Reusable form elements with validation and error handling
 */

class FormInput {
    constructor(name, options = {}) {
        this.name = name;
        this.label = options.label || name;
        this.type = options.type || 'text';
        this.placeholder = options.placeholder || '';
        this.required = options.required || false;
        this.validation = options.validation || null;
        this.value = options.value || '';
        this.error = null;
    }

    validate() {
        this.error = null;

        // Required validation
        if (this.required && !this.value.trim()) {
            this.error = `${this.label} is required`;
            return false;
        }

        // Custom validation
        if (this.validation && this.value.trim()) {
            if (!this.validation.regex.test(this.value)) {
                this.error = this.validation.message;
                return false;
            }
        }

        return true;
    }

    render() {
        const errorClass = this.error ? 'is-invalid' : '';
        return `
            <div class="mb-3">
                <label class="form-label" for="${this.name}">${this.label}${this.required ? ' *' : ''}</label>
                <input
                    type="${this.type}"
                    class="form-control ${errorClass}"
                    id="${this.name}"
                    name="${this.name}"
                    placeholder="${this.placeholder}"
                    value="${this.value}"
                    ${this.required ? 'required' : ''}
                >
                ${this.error ? `<div class="invalid-feedback d-block">${this.error}</div>` : ''}
            </div>
        `;
    }
}

class FormSelect {
    constructor(name, options = {}) {
        this.name = name;
        this.label = options.label || name;
        this.required = options.required || false;
        this.options = options.options || [];
        this.value = options.value || '';
        this.error = null;
    }

    validate() {
        this.error = null;

        if (this.required && !this.value) {
            this.error = `${this.label} is required`;
            return false;
        }

        return true;
    }

    render() {
        const errorClass = this.error ? 'is-invalid' : '';
        return `
            <div class="mb-3">
                <label class="form-label" for="${this.name}">${this.label}${this.required ? ' *' : ''}</label>
                <select
                    class="form-select ${errorClass}"
                    id="${this.name}"
                    name="${this.name}"
                    ${this.required ? 'required' : ''}
                >
                    <option value="">Select ${this.label}...</option>
                    ${this.options.map(opt => `
                        <option value="${opt.value}" ${opt.value === this.value ? 'selected' : ''}>
                            ${opt.label}
                        </option>
                    `).join('')}
                </select>
                ${this.error ? `<div class="invalid-feedback d-block">${this.error}</div>` : ''}
            </div>
        `;
    }
}

class FormTextarea {
    constructor(name, options = {}) {
        this.name = name;
        this.label = options.label || name;
        this.placeholder = options.placeholder || '';
        this.required = options.required || false;
        this.rows = options.rows || 4;
        this.value = options.value || '';
        this.error = null;
    }

    validate() {
        this.error = null;

        if (this.required && !this.value.trim()) {
            this.error = `${this.label} is required`;
            return false;
        }

        return true;
    }

    render() {
        const errorClass = this.error ? 'is-invalid' : '';
        return `
            <div class="mb-3">
                <label class="form-label" for="${this.name}">${this.label}${this.required ? ' *' : ''}</label>
                <textarea
                    class="form-control ${errorClass}"
                    id="${this.name}"
                    name="${this.name}"
                    placeholder="${this.placeholder}"
                    rows="${this.rows}"
                    ${this.required ? 'required' : ''}
                >${this.value}</textarea>
                ${this.error ? `<div class="invalid-feedback d-block">${this.error}</div>` : ''}
            </div>
        `;
    }
}

class FormCheckbox {
    constructor(name, options = {}) {
        this.name = name;
        this.label = options.label || name;
        this.checked = options.checked || false;
        this.value = options.value || 'on';
    }

    render() {
        return `
            <div class="form-check mb-3">
                <input
                    class="form-check-input"
                    type="checkbox"
                    id="${this.name}"
                    name="${this.name}"
                    value="${this.value}"
                    ${this.checked ? 'checked' : ''}
                >
                <label class="form-check-label" for="${this.name}">
                    ${this.label}
                </label>
            </div>
        `;
    }
}

class FormBuilder {
    constructor(formId = 'dynamic-form') {
        this.formId = formId;
        this.fields = [];
        this.submitLabel = 'Submit';
        this.cancelLabel = 'Cancel';
        this.onSubmit = null;
        this.onCancel = null;
    }

    addField(field) {
        this.fields.push(field);
        return this;
    }

    setSubmitLabel(label) {
        this.submitLabel = label;
        return this;
    }

    setCancelLabel(label) {
        this.cancelLabel = label;
        return this;
    }

    onSubmitClick(callback) {
        this.onSubmit = callback;
        return this;
    }

    onCancelClick(callback) {
        this.onCancel = callback;
        return this;
    }

    validate() {
        return this.fields.every(field => field.validate());
    }

    getValues() {
        const values = {};
        this.fields.forEach(field => {
            const input = document.getElementById(field.name);
            if (field instanceof FormCheckbox) {
                values[field.name] = input?.checked || false;
            } else {
                values[field.name] = input?.value || '';
            }
        });
        return values;
    }

    render() {
        return `
            <form id="${this.formId}" class="needs-validation">
                ${this.fields.map(field => field.render()).join('')}
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">${this.submitLabel}</button>
                    <button type="button" class="btn btn-secondary">${this.cancelLabel}</button>
                </div>
            </form>
        `;
    }

    attach(containerId) {
        const container = document.getElementById(containerId);
        if (!container) {
            console.error(`Container ${containerId} not found`);
            return;
        }

        container.innerHTML = this.render();

        // Wire up submit
        const form = document.getElementById(this.formId);
        form?.addEventListener('submit', (e) => {
            e.preventDefault();
            if (this.validate()) {
                const values = this.getValues();
                this.onSubmit?.(values);
            } else {
                Notifications.error('Please fix the errors above');
            }
        });

        // Wire up cancel
        const cancelBtn = form?.querySelector('button[type="button"]');
        cancelBtn?.addEventListener('click', () => {
            this.onCancel?.();
        });
    }
}

class Modal {
    constructor(title, options = {}) {
        this.title = title;
        this.id = options.id || `modal-${Math.random().toString(36).substr(2, 9)}`;
        this.size = options.size || 'md'; // sm, md, lg, xl
        this.content = '';
        this.footer = null;
        this.onClose = options.onClose || null;
    }

    setContent(html) {
        this.content = html;
        return this;
    }

    setFooter(html) {
        this.footer = html;
        return this;
    }

    render() {
        return `
            <div class="modal fade" id="${this.id}" tabindex="-1">
                <div class="modal-dialog modal-${this.size}">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">${this.title}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            ${this.content}
                        </div>
                        ${this.footer ? `<div class="modal-footer">${this.footer}</div>` : ''}
                    </div>
                </div>
            </div>
        `;
    }

    show() {
        const container = document.body;
        container.insertAdjacentHTML('beforeend', this.render());

        const modalEl = document.getElementById(this.id);
        const modal = new bootstrap.Modal(modalEl);
        modal.show();

        // Clean up on close
        modalEl.addEventListener('hidden.bs.modal', () => {
            modalEl.remove();
            this.onClose?.();
        });

        return modal;
    }
}

class ConfirmDialog {
    constructor(title, message) {
        this.title = title;
        this.message = message;
        this.confirmLabel = 'Confirm';
        this.cancelLabel = 'Cancel';
        this.onConfirm = null;
        this.onCancel = null;
    }

    show() {
        return new Promise((resolve) => {
            const modal = new Modal(this.title);
            modal.setContent(`<p>${this.message}</p>`);
            modal.setFooter(`
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">${this.cancelLabel}</button>
                <button type="button" class="btn btn-danger" id="confirm-btn">${this.confirmLabel}</button>
            `);

            const instance = modal.show();

            document.getElementById('confirm-btn')?.addEventListener('click', () => {
                instance.hide();
                resolve(true);
            });

            const modalEl = document.getElementById(modal.id);
            modalEl.addEventListener('hidden.bs.modal', () => {
                resolve(false);
            });
        });
    }
}

// Common validation patterns
const ValidationRules = {
    email: {
        regex: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
        message: 'Please enter a valid email address'
    },
    phone: {
        regex: /^[\d\s\-\+\(\)]{7,}$/,
        message: 'Please enter a valid phone number'
    },
    url: {
        regex: /^https?:\/\/.+/,
        message: 'Please enter a valid URL'
    },
    number: {
        regex: /^\d+$/,
        message: 'Please enter a valid number'
    },
    date: {
        regex: /^\d{4}-\d{2}-\d{2}$/,
        message: 'Please enter a valid date (YYYY-MM-DD)'
    }
};

// Export all components
window.FormComponents = {
    FormInput,
    FormSelect,
    FormTextarea,
    FormCheckbox,
    FormBuilder,
    Modal,
    ConfirmDialog,
    ValidationRules
};
