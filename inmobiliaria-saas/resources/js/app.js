import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.data('crudTable', (config = {}) => ({
    modalOpen: false,
    modalTitle: '',
    modalHtml: '',
    loading: false,
    saving: false,
    error: null,
    toastVisible: false,
    toastMessage: '',
    toastType: 'success',
    toastTimer: null,
    config,

    init() {
        if (this.config.flash) {
            this.showToast(this.config.flash);
        }
    },

    async openModal(url, title) {
        this.modalOpen = true;
        this.modalTitle = title;
        this.modalHtml = '';
        this.loading = true;
        this.error = null;

        try {
            const response = await window.axios.get(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html, application/xhtml+xml',
                },
            });

            this.modalHtml = response.data;
        } catch (error) {
            this.error = this.resolveErrorMessage(error, 'No fue posible cargar el formulario.');
            this.showToast(this.error, 'error');
        } finally {
            this.loading = false;
        }
    },

    closeModal() {
        this.modalOpen = false;
        this.modalTitle = '';
        this.modalHtml = '';
        this.error = null;
    },

    async submitForm(event) {
        const form = event.target.closest('form[data-ajax-form]');

        if (! form) {
            return;
        }

        this.clearFormErrors(form);
        this.saving = true;
        this.error = null;

        try {
            const formData = new FormData(form);
            const response = await window.axios.post(form.action, formData, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            this.applyRowChange(response.data);
            this.closeModal();
            this.showToast(response.data.message ?? 'Operación realizada correctamente.');
        } catch (error) {
            if (error.response?.status === 422 && error.response.data?.errors) {
                this.applyFormErrors(form, error.response.data.errors);
            } else {
                this.error = this.resolveErrorMessage(error, 'No fue posible guardar la información.');
                this.showToast(this.error, 'error');
            }
        } finally {
            this.saving = false;
        }
    },

    async handleClick(event) {
        const button = event.target.closest('[data-action]');

        if (! button) {
            return;
        }

        const action = button.dataset.action;

        if (action === 'create' || action === 'edit') {
            await this.openModal(button.dataset.url, button.dataset.title);
            return;
        }

        if (action === 'close-modal') {
            this.closeModal();
            return;
        }

        if (action === 'delete') {
            await this.deleteRecord(button.dataset.url, button.dataset.confirmMessage);
            return;
        }

        if (action === 'status') {
            await this.changeStatus(button);
        }
    },

    async changeStatus(button) {
        const options = JSON.parse(button.dataset.statusOptions ?? '[]');
        const currentStatus = button.dataset.currentStatus;
        const currentIndex = options.indexOf(currentStatus);
        const nextStatus = options[(currentIndex + 1) % options.length];

        if (! nextStatus) {
            return;
        }

        try {
            const response = await window.axios.patch(button.dataset.url, {
                status: nextStatus,
            }, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            this.applyRowChange(response.data);
            this.showToast(response.data.message ?? 'Estado actualizado correctamente.');
        } catch (error) {
            this.showToast(this.resolveErrorMessage(error, 'No fue posible cambiar el estado.'), 'error');
        }
    },

    async deleteRecord(url, confirmMessage) {
        if (! window.confirm(confirmMessage || '¿Deseas eliminar este registro?')) {
            return;
        }

        try {
            const response = await window.axios.delete(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            this.removeRow(response.data.id);
            this.showToast(response.data.message ?? 'Registro eliminado correctamente.');
        } catch (error) {
            this.showToast(this.resolveErrorMessage(error, 'No fue posible eliminar el registro.'), 'error');
        }
    },

    applyRowChange(payload) {
        if (payload.summary_html && this.$refs.summary) {
            this.$refs.summary.innerHTML = payload.summary_html;
        }

        if (payload.structure_html && this.$refs.structure) {
            this.$refs.structure.innerHTML = payload.structure_html;
            return;
        }

        if (! payload.row_html || ! payload.id) {
            return;
        }

        const tbody = this.$refs.tbody;

        if (! tbody) {
            return;
        }

        const wrapper = document.createElement('tbody');
        wrapper.innerHTML = payload.row_html.trim();
        const newRow = wrapper.firstElementChild;

        if (! newRow) {
            return;
        }

        tbody.querySelectorAll('[data-empty-state]').forEach((element) => element.remove());

        const currentRow = tbody.querySelector(`[data-row-id="${payload.id}"]`);

        if (currentRow) {
            currentRow.replaceWith(newRow);
        } else {
            tbody.prepend(newRow);
        }
    },

    removeRow(id) {
        const row = this.$refs.tbody?.querySelector(`[data-row-id="${id}"]`);

        if (row) {
            row.remove();
        }
    },

    showToast(message, type = 'success') {
        if (! message) {
            return;
        }

        if (this.toastTimer) {
            window.clearTimeout(this.toastTimer);
        }

        this.toastMessage = message;
        this.toastType = type;
        this.toastVisible = true;

        this.toastTimer = window.setTimeout(() => {
            this.hideToast();
        }, 3200);
    },

    hideToast() {
        if (this.toastTimer) {
            window.clearTimeout(this.toastTimer);
            this.toastTimer = null;
        }

        this.toastVisible = false;
    },

    clearFormErrors(form) {
        form.querySelectorAll('[data-error-for]').forEach((element) => {
            element.textContent = '';
            element.classList.add('hidden');
        });
    },

    applyFormErrors(form, errors) {
        Object.entries(errors).forEach(([field, messages]) => {
            const errorElement = form.querySelector(`[data-error-for="${field}"]`);

            if (! errorElement) {
                return;
            }

            errorElement.textContent = messages.join(' ');
            errorElement.classList.remove('hidden');
        });
    },

    resolveErrorMessage(error, fallback) {
        return error.response?.data?.message
            || error.message
            || fallback;
    },
}));

Alpine.start();
