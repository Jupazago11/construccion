import './bootstrap';

import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

window.Alpine = Alpine;
window.Chart = Chart;

window.addEventListener('error', (event) => {
    console.error('Global JS error:', event.error || event.message);
});

window.addEventListener('unhandledrejection', (event) => {
    console.error('Unhandled promise rejection:', event.reason);
});

Alpine.data('crudTable', (config = {}) => ({
    modalOpen: false,
    modalTitle: '',
    modalHtml: '',
    modalUrl: '',
    nestedModalOpen: false,
    nestedModalTitle: '',
    nestedModalHtml: '',
    nestedModalUrl: '',
    nestedLoading: false,
    loading: false,
    saving: false,
    error: null,
    toastVisible: false,
    toastMessage: '',
    toastType: 'success',
    toastTimer: null,
    formDrafts: {},
    config,

    init() {
        if (this.config.flash) {
            this.showToast(this.config.flash);
        }

        window.addEventListener('crud-toast', (event) => {
            this.showToast(event.detail?.message, event.detail?.type ?? 'success');
        });

        window.addEventListener('crud-save-open-modal-draft', () => {
            if (! this.modalOpen || ! this.modalUrl) {
                return;
            }

            this.saveDraft(this.modalUrl, this.collectModalFormValues());
        });

        window.addEventListener('crud-refresh-open-modal-from-draft', () => {
            if (! this.modalOpen || ! this.modalUrl) {
                return;
            }

            const draft = this.loadDraft(this.modalUrl) ?? this.collectModalFormValues();

            this.refreshOpenModalPreservingForm(draft);
        });

        window.addEventListener('open-ajax-modal', (event) => {
            const { url, title } = event.detail ?? {};

            if (url) {
                this.openModal(url, title ?? '');
            }
        });

        window.addEventListener('close-ajax-modal', () => {
            this.closeModal();
        });
    },

    shouldReloadAfterMutation() {
        return this.config.reloadOnMutate === true;
    },

    reloadCurrentPage() {
        window.location.assign(window.location.href);
    },

    async paginateTable(url) {
        if (! url || ! this.$refs.tbody || ! this.$refs.pagination) {
            return;
        }

        const preservedWindowTop = window.scrollY || window.pageYOffset || 0;
        const pagination = this.$refs.pagination;

        pagination.classList.add('pointer-events-none', 'opacity-60');

        try {
            const response = await window.axios.get(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            });

            if (response.data?.table_html) {
                this.$refs.tbody.innerHTML = response.data.table_html;
                Alpine.initTree(this.$refs.tbody);
            }

            if (response.data?.pagination_html !== undefined) {
                this.$refs.pagination.innerHTML = response.data.pagination_html;
                Alpine.initTree(this.$refs.pagination);
            }

            window.requestAnimationFrame(() => {
                window.scrollTo({
                    top: preservedWindowTop,
                    left: 0,
                    behavior: 'auto',
                });
                window.scrollTo({
                    top: preservedWindowTop,
                    left: 0,
                    behavior: 'auto',
                });
            });
        } catch (error) {
            this.showToast(this.resolveErrorMessage(error, 'No fue posible cambiar de página.'), 'error');
        } finally {
            pagination.classList.remove('pointer-events-none', 'opacity-60');
        }
    },

    async openModal(url, title) {
        if (this.loading) {
            return;
        }

        this.modalOpen = true;
        this.modalTitle = title;
        this.modalUrl = url;
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
            this.$nextTick(() => {
                if (this.$refs.modalContent) {
                    Alpine.initTree(this.$refs.modalContent);
                    window.initializeExpenseForms?.(this.$refs.modalContent);
                    window.initializeTransactionForms?.(this.$refs.modalContent);
                    window.initializeStandaloneInvoiceForms?.(this.$refs.modalContent);
                    window.initializeCompanyLogoForms?.(this.$refs.modalContent);
                    window.initializeInvoiceAttachmentForms?.(this.$refs.modalContent);
                    window.initializeAssetForms?.(this.$refs.modalContent);

                    const draft = this.loadDraft(url);
                    if (draft) {
                        const methodInput = this.$refs.modalContent.querySelector('[name="_method"]');
                        const isEditForm = methodInput && ['PATCH', 'PUT'].includes(methodInput.value.toUpperCase());
                        if (!isEditForm) {
                            window.requestAnimationFrame(() => {
                                this.restoreModalFormValues(draft);
                            });
                        }
                    }
                }
            });
        } catch (error) {
            this.error = this.resolveErrorMessage(error, 'No fue posible cargar el formulario.');
            this.closeModal();
            this.showToast(this.error, 'error');
        } finally {
            this.loading = false;
        }
    },

    async openNestedModal(url, title, context = {}) {
        if (this.nestedLoading) {
            return;
        }

        this.nestedModalOpen = true;
        this.nestedModalTitle = title;
        this.nestedModalUrl = url;
        this.nestedLoading = true;
        this.error = null;

        try {
            const response = await window.axios.get(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html, application/xhtml+xml',
                },
            });

            this.nestedModalHtml = response.data;
            this.$nextTick(() => {
                if (! this.$refs.nestedModalContent) {
                    return;
                }

                Alpine.initTree(this.$refs.nestedModalContent);
                window.initializeExpenseForms?.(this.$refs.nestedModalContent);
                window.initializeTransactionForms?.(this.$refs.nestedModalContent);

                const form = this.$refs.nestedModalContent.querySelector('form[data-ajax-form]');
                if (form && context.invoiceId) {
                    const detailInput = document.createElement('input');
                    detailInput.type = 'hidden';
                    detailInput.name = 'invoice_detail_id';
                    detailInput.value = context.invoiceId;
                    form.append(detailInput);

                    const invoiceField = form.querySelector('[data-transaction-invoice]');
                    if (invoiceField && ! invoiceField.value) {
                        invoiceField.value = context.invoiceId;
                    }
                }
            });
        } catch (error) {
            this.error = this.resolveErrorMessage(error, 'No fue posible cargar el formulario.');
            this.closeNestedModal();
            this.showToast(this.error, 'error');
        } finally {
            this.nestedLoading = false;
        }
    },

    closeModal() {
        if (this.modalHtml) {
            this.saveDraft(this.modalUrl, this.collectModalFormValues());
        }
        this.modalOpen = false;
        this.modalTitle = '';
        this.modalHtml = '';
        this.modalUrl = '';
        this.closeNestedModal();
        this.error = null;
    },

    closeNestedModal() {
        this.nestedModalOpen = false;
        this.nestedModalTitle = '';
        this.nestedModalHtml = '';
        this.nestedModalUrl = '';
        this.nestedLoading = false;
    },

    async refreshOpenModalPreservingForm(values = null, revealWhenDone = false) {
        if (! this.modalOpen || ! this.modalUrl || this.loading) {
            if (revealWhenDone) {
                this.nestedModalOpen = false;
            }

            return;
        }

        const preservedValues = values ?? this.collectModalFormValues();
        const scrollContainer = this.$refs.modalContent?.querySelector('.overflow-y-auto');
        const preservedScrollTop = scrollContainer?.scrollTop ?? 0;

        this.loading = true;
        this.error = null;

        try {
            const response = await window.axios.get(this.modalUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html, application/xhtml+xml',
                },
            });

            this.modalHtml = response.data;
            this.$nextTick(() => {
                if (! this.$refs.modalContent) {
                    return;
                }

                Alpine.initTree(this.$refs.modalContent);
                window.initializeExpenseForms?.(this.$refs.modalContent);
                window.initializeTransactionForms?.(this.$refs.modalContent);
                window.initializeStandaloneInvoiceForms?.(this.$refs.modalContent);
                window.initializeCompanyLogoForms?.(this.$refs.modalContent);
                window.initializeInvoiceAttachmentForms?.(this.$refs.modalContent);
                window.initializeAssetForms?.(this.$refs.modalContent);

                window.requestAnimationFrame(() => {
                    this.restoreModalFormValues(preservedValues);

                    if (preservedScrollTop > 0) {
                        const nextScrollContainer = this.$refs.modalContent?.querySelector('.overflow-y-auto');
                        if (nextScrollContainer) {
                            nextScrollContainer.scrollTop = preservedScrollTop;
                        }
                    }
                });
            });
        } catch (error) {
            this.error = this.resolveErrorMessage(error, 'No fue posible actualizar el formulario.');
            this.showToast(this.error, 'error');
        } finally {
            this.loading = false;
            if (revealWhenDone) {
                this.nestedModalOpen = false;
            }
        }
    },

    collectModalFormValues() {
        const form = this.$refs.modalContent?.querySelector('form[data-ajax-form]');

        if (! form) {
            return [];
        }

        return this.collectFormValues(form);
    },

    collectFormValues(form) {
        return Array.from(form.elements)
            .filter((field) => field.name && field.type !== 'file')
            .map((field) => ({
                name: field.name,
                type: field.type,
                value: field.value,
                checked: field.checked,
            }));
    },

    draftKey(url) {
        if (! url) return null;
        return 'form-draft:' + url.split('?')[0];
    },

    saveDraft(url, values) {
        const key = this.draftKey(url);

        if (! key || ! Array.isArray(values)) {
            return;
        }

        const ignoredFields = ['_token', '_method'];

        const meaningful = values.filter((field) => {
            return ! ignoredFields.includes(field.name)
                && String(field.value ?? '').trim() !== '';
        });

        if (meaningful.length === 0) {
            return;
        }

        const currentDraft = this.formDrafts[key] ?? [];

        const currentMeaningful = currentDraft.filter((field) => {
            return ! ignoredFields.includes(field.name)
                && String(field.value ?? '').trim() !== '';
        });

        if (currentMeaningful.length > meaningful.length) {
            return;
        }

        this.formDrafts[key] = values;
    },

    loadDraft(url) {
        const key = this.draftKey(url);

        if (! key) {
            return null;
        }

        return this.formDrafts[key] ?? null;
    },

    clearDraft(url) {
        const key = this.draftKey(url);

        if (! key) {
            return;
        }

        delete this.formDrafts[key];
    },


    restoreModalFormValues(values) {
        const form = this.$refs.modalContent?.querySelector('form[data-ajax-form]');

        if (! form || values.length === 0) {
            return;
        }

        values.forEach((savedField) => {
            const fields = Array.from(form.elements).filter((field) => field.name === savedField.name);

            fields.forEach((field) => {
                if (field.type === 'file') {
                    return;
                }

                if (field.tagName === 'SELECT') {
                    const hasOption = Array.from(field.options).some((option) => option.value === savedField.value && ! option.disabled);

                    if (! hasOption) {
                        return;
                    }
                }

                if (field.type === 'checkbox' || field.type === 'radio') {
                    field.checked = savedField.checked;
                } else {
                    field.value = savedField.value;
                }

                if (field.matches('[data-currency-input]')) {
                    syncFormattedMoneyInput(field);
                }

                field.dispatchEvent(new Event('input', { bubbles: true }));
                field.dispatchEvent(new Event('change', { bubbles: true }));
            });
        });
    },

    async submitForm(event) {
        const form = event.target.closest('form[data-ajax-form]');

        if (! form) {
            return;
        }

        if (this.saving || form.dataset.ajaxSubmitting === 'true') {
            return;
        }

        const openStructureModal = form.querySelector('[data-expense-structure-modal]:not(.hidden)');

        if (openStructureModal && openStructureModal.contains(document.activeElement)) {
            return;
        }

        this.clearFormErrors(form);
        this.saving = true;
        form.dataset.ajaxSubmitting = 'true';
        const submitter = event.submitter?.matches?.('[type="submit"]') ? event.submitter : form.querySelector('[type="submit"]');
        const submitterText = submitter?.textContent;
        if (submitter) {
            submitter.disabled = true;
            submitter.classList.add('pointer-events-none', 'opacity-60');
        }
        this.error = null;

        try {
            const formData = event.submitter
                ? new FormData(form, event.submitter)
                : new FormData(form);
            const response = await window.axios.post(form.action, formData, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (this.shouldReloadAfterMutation()) {
                this.clearDraft(this.modalUrl);
                this.reloadCurrentPage();
                return;
            }

            this.applyRowChange(response.data);
            this.clearDraft(this.modalUrl);

            form.reset();
            if (form.closest('[data-nested-modal-content]')) {
                this.closeNestedModal();
            } else {
                this.closeModal();
            }
            this.showToast(response.data.message ?? 'Operación realizada correctamente.');
        } catch (error) {
            if (error.response?.status === 422 && error.response.data?.errors) {
                this.applyFormErrors(form, error.response.data.errors);
                this.showToast(
                    Object.values(error.response.data.errors).flat()[0] ?? 'Revisa la información enviada.',
                    'error',
                );
            } else {
                this.error = this.resolveErrorMessage(error, 'No fue posible guardar la información.');
                this.showToast(this.error, 'error');
            }
        } finally {
            this.saving = false;
            delete form.dataset.ajaxSubmitting;
            if (submitter) {
                submitter.disabled = false;
                submitter.classList.remove('pointer-events-none', 'opacity-60');
                if (submitterText !== undefined) {
                    submitter.textContent = submitterText;
                }
            }
        }
    },

    async handleClick(event) {
        const paginationLink = event.target.closest('[data-ajax-pagination] a[href]');

        if (paginationLink) {
            event.preventDefault();
            event.stopPropagation();
            await this.paginateTable(paginationLink.href);
            return;
        }

        const rowTrigger = event.target.closest('[data-row-open]');

        if (
            rowTrigger
            && ! event.target.closest('a, button, input, select, textarea, label, [data-action]')
        ) {
            event.preventDefault();
            event.stopPropagation();

            if (rowTrigger.dataset.href) {
                window.location.assign(rowTrigger.dataset.href);
                return;
            }

            await this.openModal(rowTrigger.dataset.url, rowTrigger.dataset.title ?? '');
            return;
        }

        const button = event.target.closest('[data-action]');

        if (! button) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        const action = button.dataset.action;

        if (action === 'create' || action === 'edit') {
            const invoiceDetailRoot = button.closest('[data-invoice-detail-root]');
            if (action === 'edit' && invoiceDetailRoot) {
                await this.openNestedModal(button.dataset.url, button.dataset.title, {
                    invoiceId: invoiceDetailRoot.dataset.invoiceId,
                });
                return;
            }

            await this.openModal(button.dataset.url, button.dataset.title);
            return;
        }

        if (action === 'close-modal') {
            if (button.closest('[data-nested-modal-content]')) {
                this.closeNestedModal();
                return;
            }

            this.closeModal();
            return;
        }

        if (action === 'delete') {
            await this.deleteRecord(button.dataset.url, button.dataset.confirmMessage, button);
            return;
        }

        if (action === 'invoice-attachment-delete') {
            await this.deleteInvoiceAttachment(button.dataset.url);
            return;
        }

        if (action === 'invoice-delete') {
            await this.deleteInvoice(button.dataset.url);
            return;
        }

        if (action === 'status') {
            await this.changeStatus(button);
            return;
        }

        if (action === 'invoice-detail') {
            await this.openModal(button.dataset.url, button.dataset.title || 'Detalle de factura');
            return;
        }

        if (action === 'invoice-status') {
            await this.changeInvoiceStatus(button);
            return;
        }

        if (action === 'status-modal') {
            this.openStatusModal(button);
            return;
        }

        if (action === 'pick-status') {
            this.submitPickedStatus(button);
        }
    },

    openStatusModal(button) {
        const options = JSON.parse(button.dataset.statusOptions ?? '[]');
        const currentStatus = button.dataset.currentStatus ?? '';
        const url = button.dataset.url;
        const entityLabel = button.dataset.entityLabel ?? 'registro';
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

        if (! url || options.length === 0) {
            return;
        }

        const statusThemes = {
            planning: {
                current: 'border-sky-300 bg-sky-100 text-sky-950 ring-2 ring-sky-100 shadow-sm',
                idle: 'border-sky-200 bg-sky-50/70 text-sky-800 hover:border-sky-300 hover:bg-sky-100',
                dot: 'bg-sky-400',
            },
            active: {
                current: 'border-emerald-300 bg-emerald-100 text-emerald-950 ring-2 ring-emerald-100 shadow-sm',
                idle: 'border-emerald-200 bg-emerald-50/70 text-emerald-800 hover:border-emerald-300 hover:bg-emerald-100',
                dot: 'bg-emerald-400',
            },
            paused: {
                current: 'border-orange-300 bg-orange-100 text-orange-950 ring-2 ring-orange-100 shadow-sm',
                idle: 'border-orange-200 bg-orange-50/70 text-orange-800 hover:border-orange-300 hover:bg-orange-100',
                dot: 'bg-orange-400',
            },
            completed: {
                current: 'border-teal-300 bg-teal-100 text-teal-950 ring-2 ring-teal-100 shadow-sm',
                idle: 'border-teal-200 bg-teal-50/70 text-teal-800 hover:border-teal-300 hover:bg-teal-100',
                dot: 'bg-teal-400',
            },
            cancelled: {
                current: 'border-stone-300 bg-stone-100 text-stone-950 ring-2 ring-stone-100 shadow-sm',
                idle: 'border-stone-200 bg-stone-50/80 text-stone-800 hover:border-stone-300 hover:bg-stone-100',
                dot: 'bg-stone-400',
            },
            inactive: {
                current: 'border-amber-300 bg-amber-100 text-amber-950 ring-2 ring-amber-100 shadow-sm',
                idle: 'border-amber-200 bg-amber-50/70 text-amber-800 hover:border-amber-300 hover:bg-amber-100',
                dot: 'bg-amber-400',
            },
            deleted: {
                current: 'border-rose-300 bg-rose-100 text-rose-950 ring-2 ring-rose-100 shadow-sm',
                idle: 'border-rose-200 bg-rose-50/70 text-rose-800 hover:border-rose-300 hover:bg-rose-100',
                dot: 'bg-rose-400',
            },
        };

        const optionButtons = options.map((status) => {
            const isCurrent = status === currentStatus;
            const theme = statusThemes[status] ?? {
                current: 'border-stone-300 bg-stone-100 text-stone-950 ring-2 ring-stone-100 shadow-sm',
                idle: 'border-stone-200 bg-white text-stone-700 hover:border-stone-300 hover:bg-stone-50',
                dot: 'bg-stone-400',
            };

            return `
                <button
                    type="button"
                    data-action="pick-status"
                    data-status-value="${status}"
                    value="${status}"
                    class="rounded-2xl border px-4 py-3 text-left text-sm transition ${isCurrent ? theme.current : theme.idle}"
                >
                    <span class="flex items-center gap-2 font-medium">
                        <span class="h-2.5 w-2.5 rounded-full ${theme.dot}"></span>
                        <span>${this.humanizeStatus(status)}</span>
                    </span>
                    ${isCurrent ? '<span class="mt-1 block text-xs opacity-70">Estado actual</span>' : ''}
                </button>
            `;
        }).join('');

        this.modalTitle = 'Cambiar estado';
        this.modalOpen = true;
        this.loading = false;
        this.error = null;
        this.modalHtml = `
            <form method="POST" action="${url}" data-ajax-form class="flex h-full min-h-0 flex-col gap-4 overflow-hidden sm:gap-6">
                <input type="hidden" name="_token" value="${csrf}">
                <input type="hidden" name="_method" value="PATCH">
                <input type="hidden" name="status" value="${currentStatus}">
                <div class="min-h-0 flex-1 overflow-y-auto pr-1">
                    <div class="space-y-4">
                        <div class="rounded-2xl border border-stone-200 bg-stone-50 px-4 py-3 text-sm text-stone-700">
                            Elige el estado que deseas dejar para este ${entityLabel}.
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            ${optionButtons}
                        </div>
                    </div>
                </div>
                <div class="sticky bottom-0 z-10 mt-auto shrink-0 border-t border-stone-200 bg-white px-1 pb-[calc(env(safe-area-inset-bottom)+0.5rem)] pt-4 shadow-[0_-8px_18px_rgba(255,255,255,0.92)]">
                    <div class="flex items-center justify-end gap-3">
                        <button type="button" data-action="close-modal" class="rounded-2xl border border-stone-300 px-4 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-50">
                            Cancelar
                        </button>
                    </div>
                </div>
            </form>
        `;

        this.$nextTick(() => {
            if (this.$refs.modalContent) {
                Alpine.initTree(this.$refs.modalContent);
            }
        });
    },

    submitPickedStatus(button) {
        const form = button.closest('form[data-ajax-form]');

        if (! form) {
            return;
        }

        const statusInput = form.querySelector('input[name="status"]');

        if (! statusInput) {
            return;
        }

        statusInput.value = button.value || button.dataset.statusValue || '';
        form.requestSubmit();
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
            const invoiceDetailRoot = button.closest('[data-invoice-detail-root]');
            const response = await window.axios.patch(button.dataset.url, {
                status: nextStatus,
                invoice_detail_id: invoiceDetailRoot?.dataset.invoiceId || undefined,
            }, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (this.shouldReloadAfterMutation()) {
                this.reloadCurrentPage();
                return;
            }

            this.applyRowChange(response.data);
            this.showToast(response.data.message ?? 'Estado actualizado correctamente.');
        } catch (error) {
            this.showToast(this.resolveErrorMessage(error, 'No fue posible cambiar el estado.'), 'error');
        }
    },

    async deleteRecord(url, confirmMessage, button = null) {
        if (! window.confirm(confirmMessage || '¿Deseas eliminar este registro?')) {
            return;
        }

        try {
            const invoiceDetailRoot = button?.closest?.('[data-invoice-detail-root]');
            const response = await window.axios.delete(url, {
                data: {
                    invoice_detail_id: invoiceDetailRoot?.dataset.invoiceId || undefined,
                },
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (this.shouldReloadAfterMutation()) {
                this.reloadCurrentPage();
                return;
            }

            this.applyRowChange(response.data);

            if (! response.data.row_html && ! response.data.table_html) {
                this.removeRow(response.data.id);
            }

            this.showToast(response.data.message ?? 'Registro eliminado correctamente.');
        } catch (error) {
            this.showToast(this.resolveErrorMessage(error, 'No fue posible eliminar el registro.'), 'error');
        }
    },

    async changeInvoiceStatus(button) {
        const currentStatus = button.dataset.currentStatus ?? 'open';
        const nextStatus = currentStatus === 'open' ? 'closed' : 'open';
        const confirmMessage = nextStatus === 'closed'
            ? '¿Deseas cerrar esta factura?'
            : '¿Deseas abrir esta factura?';

        if (! window.confirm(confirmMessage)) {
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

            button.dataset.currentStatus = nextStatus;
            const badge = button.querySelector('span');
            if (badge) {
                badge.textContent = nextStatus === 'closed' ? 'Cerrada' : 'Abierta';
                badge.className = badge.className
                    .replace(/bg-\S+|text-\S+|ring-\S+/g, '')
                    + (nextStatus === 'closed'
                        ? ' bg-teal-100 text-teal-700 ring-teal-600/20'
                        : ' bg-sky-100 text-sky-700 ring-sky-600/20');
            }

            this.showToast(response.data.message ?? 'Estado de factura actualizado correctamente.');

            if (this.shouldReloadAfterMutation()) {
                this.reloadCurrentPage();
            }
        } catch (error) {
            this.showToast(this.resolveErrorMessage(error, 'No fue posible cambiar el estado de la factura.'), 'error');
        }
    },

    async deleteInvoiceAttachment(url) {
        if (! window.confirm('¿Deseas archivar este archivo?')) {
            return;
        }

        try {
            const response = await window.axios.delete(url, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });

            this.applyRowChange(response.data);
            this.showToast(response.data.message ?? 'Archivo archivado correctamente.');
        } catch (error) {
            this.showToast(this.resolveErrorMessage(error, 'No fue posible archivar el archivo.'), 'error');
        }
    },

    async deleteInvoice(url) {
        if (! window.confirm('¿Deseas archivar esta factura? Los gastos o compras asociados quedarán como movimientos independientes.')) {
            return;
        }

        try {
            const response = await window.axios.delete(url, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });

            if (response.data.close_modal) {
                this.closeModal();
            }

            this.applyRowChange(response.data);
            this.showToast(response.data.message ?? 'Factura archivada correctamente.');
        } catch (error) {
            this.showToast(this.resolveErrorMessage(error, 'No fue posible archivar la factura.'), 'error');
        }
    },

    applyRowChange(payload) {
        if (payload.summary_html && this.$refs.summary) {
            this.$refs.summary.innerHTML = payload.summary_html;
        }

        if (payload.attachments_html && this.$refs.attachments) {
            this.$refs.attachments.innerHTML = payload.attachments_html;
            Alpine.initTree(this.$refs.attachments);
        }

        if (payload.attachments_html && this.$refs.modalContent) {
            const attachmentsRoot = this.$refs.modalContent.querySelector('[data-invoice-attachments-root]');
            if (attachmentsRoot) {
                attachmentsRoot.innerHTML = payload.attachments_html;
                Alpine.initTree(attachmentsRoot);
            }
        }

        if (payload.invoice_detail_html && this.$refs.modalContent) {
            const invoiceDetailRoot = this.$refs.modalContent.querySelector('[data-invoice-detail-root]');
            if (invoiceDetailRoot) {
                const itemsScroll = invoiceDetailRoot.querySelector('[data-invoice-items-scroll]');
                const itemsXScroll = invoiceDetailRoot.querySelector('[data-invoice-items-x-scroll]');
                const attachmentsScroll = invoiceDetailRoot.querySelector('[data-invoice-attachments-scroll]');
                const preservedScroll = {
                    itemsTop: itemsScroll?.scrollTop ?? 0,
                    itemsLeft: itemsXScroll?.scrollLeft ?? 0,
                    attachmentsTop: attachmentsScroll?.scrollTop ?? 0,
                };

                invoiceDetailRoot.outerHTML = payload.invoice_detail_html;
                this.$nextTick(() => {
                    if (this.$refs.modalContent) {
                        Alpine.initTree(this.$refs.modalContent);
                        window.initializeInvoiceAttachmentForms?.(this.$refs.modalContent);
                        window.requestAnimationFrame(() => {
                            const nextRoot = this.$refs.modalContent.querySelector('[data-invoice-detail-root]');
                            const nextItemsScroll = nextRoot?.querySelector('[data-invoice-items-scroll]');
                            const nextItemsXScroll = nextRoot?.querySelector('[data-invoice-items-x-scroll]');
                            const nextAttachmentsScroll = nextRoot?.querySelector('[data-invoice-attachments-scroll]');

                            if (nextItemsScroll) {
                                nextItemsScroll.scrollTop = Math.min(preservedScroll.itemsTop, nextItemsScroll.scrollHeight);
                            }

                            if (nextItemsXScroll) {
                                nextItemsXScroll.scrollLeft = preservedScroll.itemsLeft;
                            }

                            if (nextAttachmentsScroll) {
                                nextAttachmentsScroll.scrollTop = Math.min(preservedScroll.attachmentsTop, nextAttachmentsScroll.scrollHeight);
                            }
                        });
                    }
                });
            }
        }

        if (payload.modal_html) {
            this.modalHtml = payload.modal_html;
            this.$nextTick(() => {
                if (this.$refs.modalContent) {
                    Alpine.initTree(this.$refs.modalContent);
                    window.initializeExpenseForms?.(this.$refs.modalContent);
                    window.initializeTransactionForms?.(this.$refs.modalContent);
                    window.initializeStandaloneInvoiceForms?.(this.$refs.modalContent);
                    window.initializeCompanyLogoForms?.(this.$refs.modalContent);
                    window.initializeInvoiceAttachmentForms?.(this.$refs.modalContent);
                    window.initializeAssetForms?.(this.$refs.modalContent);
                }
            });
        }

        if (payload.structure_html && this.$refs.structure) {
            const currentStructure = this.$refs.structure.querySelector('[data-project-structure-root]');
            const selectedCategoryId = currentStructure?.dataset.selectedCategoryId
                || window.projectStructureSelection?.selectedCategoryId
                || '';
            const selectedSubcategoryId = currentStructure?.dataset.selectedSubcategoryId
                || window.projectStructureSelection?.selectedSubcategoryId
                || '';
            const preservedScroll = collectPreservedScroll(this.$refs.structure);
            const scrollY = window.scrollY;
            const template = document.createElement('template');

            template.innerHTML = payload.structure_html.trim();

            const nextStructure = template.content.firstElementChild;

            if (! nextStructure) {
                return;
            }

            nextStructure.dataset.initialSelectedCategoryId = selectedCategoryId;
            nextStructure.dataset.initialSelectedSubcategoryId = selectedSubcategoryId;
            this.$refs.structure.replaceChildren(nextStructure);
            this.$nextTick(() => {
                Alpine.initTree(nextStructure);
                window.initializeProjectStructureSorting?.(nextStructure);
                window.requestAnimationFrame(() => {
                    restorePreservedScroll(this.$refs.structure, preservedScroll);
                    window.scrollTo({ top: scrollY });
                });
            });
            return;
        }

        if (! payload.row_html || ! payload.id) {
            if (payload.table_html && this.$refs.tbody) {
                this.$refs.tbody.innerHTML = payload.table_html;
                Alpine.initTree(this.$refs.tbody);
            }
            return;
        }

        if (payload.table_html && this.$refs.tbody) {
            this.$refs.tbody.innerHTML = payload.table_html;
            Alpine.initTree(this.$refs.tbody);
            return;
        }

        const tbody = this.$refs.tbody;

        if (! tbody) {
            return;
        }

        const template = document.createElement('template');
        template.innerHTML = payload.row_html.trim();
        const newRow = template.content.firstElementChild;

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

        Alpine.initTree(newRow);
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
        if (error.response?.status === 403) {
            return 'No tienes autorización para realizar esta acción.';
        }

        return error.response?.data?.message
            || error.message
            || fallback;
    },

    humanizeStatus(status) {
        const labels = {
            planning: 'En gestión',
            active: 'Activo',
            paused: 'Pausado',
            completed: 'Completado',
            cancelled: 'Cancelado',
            deleted: 'Archivado',
            inactive: 'Inactivo',
        };

        return labels[status] ?? status;
    },

}));

Alpine.data('productCatalog', (config = {}) => ({
    modalOpen: false,
    tab: 'group',
    editingId: null,
    saving: false,
    error: '',
    errors: {},
    toastVisible: false,
    toastMessage: '',
    toastType: 'success',
    toastTimer: null,
    isSuperAdmin: config.isSuperAdmin === true,
    visibilityStorageKey: config.visibilityStorageKey ?? '',
    tableVisibilityDefaults: {
        groups: false,
        subgroups: false,
        products: true,
        ...(config.tableVisibilityDefaults ?? {}),
    },
    tableVisibility: {
        groups: false,
        subgroups: false,
        products: true,
    },
    filtersOpen: window.innerWidth >= 768,
    tableLoading: false,
    groups: config.groups ?? [],
    subgroups: config.subgroups ?? [],
    filterGroupId: config.filterGroupId ?? '',
    filterSubgroupId: config.filterSubgroupId ?? '',
    groupSearch: '',
    subgroupSearch: '',
    groupMenuOpen: false,
    subgroupMenuOpen: false,
    form: {
        company_id: config.companyId ? String(config.companyId) : '',
        product_group_id: '',
        product_subgroup_id: '',
        name: '',
    },
    drafts: {
        group: null,
        subgroup: null,
        product: null,
    },

    init() {
        this.initTableVisibility();

        try {
            const stored = sessionStorage.getItem('_catalog_toast');
            if (stored) {
                sessionStorage.removeItem('_catalog_toast');
                const { message, type } = JSON.parse(stored);
                this.showToast(message, type ?? 'success');
            }
        } catch {}

        window.addEventListener('crud-toast', (event) => {
            this.showToast(event.detail?.message, event.detail?.type ?? 'success');
        });
    },

    showToast(message, type = 'success') {
        if (! message) {
            return;
        }

        window.clearTimeout(this.toastTimer);
        this.toastMessage = message;
        this.toastType = type;
        this.toastVisible = true;
        this.toastTimer = window.setTimeout(() => this.hideToast(), 3500);
    },

    hideToast() {
        this.toastVisible = false;
    },

    get filteredGroups() {
        const companyId = this.form.company_id ? String(this.form.company_id) : '';

        return this.groups.filter((group) => ! companyId || String(group.company_id) === companyId);
    },

    get visibleGroups() {
        const term = this.groupSearch.trim().toLocaleLowerCase();

        return this.filteredGroups.filter((group) => ! term || group.name.toLocaleLowerCase().includes(term));
    },

    get filteredSubgroups() {
        const companyId = this.form.company_id ? String(this.form.company_id) : '';
        const groupId = this.form.product_group_id ? String(this.form.product_group_id) : '';

        return this.subgroups.filter((subgroup) => (
            (! companyId || String(subgroup.company_id) === companyId)
            && (! groupId || String(subgroup.product_group_id) === groupId)
        ));
    },

    get visibleSubgroups() {
        const term = this.subgroupSearch.trim().toLocaleLowerCase();

        return this.filteredSubgroups.filter((subgroup) => ! term || subgroup.name.toLocaleLowerCase().includes(term));
    },

    get filterableGroups() {
        return this.groups;
    },

    get catalogFilterSubgroups() {
        if (! this.filterGroupId) {
            return this.subgroups;
        }

        return this.subgroups.filter((subgroup) => String(subgroup.product_group_id) === String(this.filterGroupId));
    },

    initTableVisibility() {
        this.tableVisibility = { ...this.tableVisibilityDefaults };

        if (! this.isSuperAdmin || ! this.visibilityStorageKey) {
            return;
        }

        try {
            const stored = window.localStorage.getItem(this.visibilityStorageKey);
            if (! stored) {
                return;
            }

            const parsed = JSON.parse(stored);
            this.tableVisibility = {
                ...this.tableVisibilityDefaults,
                ...parsed,
            };
        } catch {}
    },

    persistTableVisibility() {
        if (! this.isSuperAdmin || ! this.visibilityStorageKey) {
            return;
        }

        try {
            window.localStorage.setItem(this.visibilityStorageKey, JSON.stringify(this.tableVisibility));
        } catch {}
    },

    saveCurrentDraft() {
        if (this.editingId) {
            return;
        }

        this.drafts[this.tab] = {
            form: { ...this.form },
            groupSearch: this.groupSearch,
            subgroupSearch: this.subgroupSearch,
        };
    },

    restoreDraft(tab) {
        if (this.editingId) {
            return;
        }

        const draft = this.drafts[tab];

        if (! draft) {
            this.form = {
                company_id: config.companyId ? String(config.companyId) : '',
                product_group_id: '',
                product_subgroup_id: '',
                name: '',
            };
            this.groupSearch = '';
            this.subgroupSearch = '';
            return;
        }

        this.form = { ...draft.form };
        this.groupSearch = draft.groupSearch ?? '';
        this.subgroupSearch = draft.subgroupSearch ?? '';
    },

    clearDraft(tab) {
        this.drafts[tab] = null;
    },

    syncFilterSubgroupSelection() {
        if (! this.filterSubgroupId) {
            return;
        }

        const hasSelectedSubgroup = this.catalogFilterSubgroups.some((subgroup) => String(subgroup.id) === String(this.filterSubgroupId));

        if (! hasSelectedSubgroup) {
            this.filterSubgroupId = '';
        }
    },

    syncCollectionsState() {
        const hasSelectedGroup = ! this.form.product_group_id
            || this.filteredGroups.some((group) => String(group.id) === String(this.form.product_group_id));

        if (! hasSelectedGroup) {
            this.form.product_group_id = '';
            this.groupSearch = '';
            this.form.product_subgroup_id = '';
            this.subgroupSearch = '';
        } else if (this.form.product_group_id) {
            this.groupSearch = this.labelForGroup(this.form.product_group_id);
        }

        const hasSelectedSubgroup = ! this.form.product_subgroup_id
            || this.filteredSubgroups.some((subgroup) => String(subgroup.id) === String(this.form.product_subgroup_id));

        if (! hasSelectedSubgroup) {
            this.form.product_subgroup_id = '';
            this.subgroupSearch = '';
        } else if (this.form.product_subgroup_id) {
            this.subgroupSearch = this.labelForSubgroup(this.form.product_subgroup_id);
        }

        const hasFilterGroup = ! this.filterGroupId
            || this.filterableGroups.some((group) => String(group.id) === String(this.filterGroupId));

        if (! hasFilterGroup) {
            this.filterGroupId = '';
        }

        this.syncFilterSubgroupSelection();
    },

    setTab(tab) {
        if (this.tab === tab) {
            return;
        }

        this.saveCurrentDraft();

        this.tab = tab;
        this.editingId = null;
        this.clearMessages();
        this.groupMenuOpen = false;
        this.subgroupMenuOpen = false;

        this.restoreDraft(tab);
    },

    openModal(tab = 'group') {
        this.modalOpen = true;
        this.tab = tab;
        this.editingId = null;
        this.clearMessages();
        this.groupMenuOpen = false;
        this.subgroupMenuOpen = false;
        this.restoreDraft(tab);
    },

    closeModal() {
        this.saveCurrentDraft();
        this.modalOpen = false;
        this.editingId = null;
        this.clearMessages();
    },

    editRecord(type, record) {
        this.modalOpen = true;
        this.tab = type;
        this.editingId = record.id;
        this.clearMessages();
        this.form = {
            company_id: record.company_id ? String(record.company_id) : '',
            product_group_id: record.product_group_id ? String(record.product_group_id) : '',
            product_subgroup_id: record.product_subgroup_id ? String(record.product_subgroup_id) : '',
            name: record.name ?? '',
        };
        this.groupSearch = this.labelForGroup(this.form.product_group_id);
        this.subgroupSearch = this.labelForSubgroup(this.form.product_subgroup_id);
    },

    clearMessages() {
        this.error = '';
        this.errors = {};
    },

    friendlyError(message) {
        const translations = {
            'The company id field is required.': 'Selecciona una empresa.',
            'The product group id field is required.': 'Selecciona un grupo.',
            'The product subgroup id field is required.': 'Selecciona un subgrupo.',
            'The name field is required.': 'Escribe un nombre.',
            'The name has already been taken.': 'Ese nombre ya existe.',
            'The selected company id is invalid.': 'La empresa seleccionada no es válida.',
            'The selected product group id is invalid.': 'El grupo seleccionado no es válido.',
            'The selected product subgroup id is invalid.': 'El subgrupo seleccionado no es válido.',
        };

        return translations[message] ?? message ?? 'Revisa la información enviada.';
    },

    showErrorToast(message) {
        window.dispatchEvent(new CustomEvent('crud-toast', {
            detail: { message: this.friendlyError(message), type: 'error' },
        }));
    },

    labelForGroup(id) {
        return this.groups.find((group) => String(group.id) === String(id))?.name ?? '';
    },

    labelForSubgroup(id) {
        return this.subgroups.find((subgroup) => String(subgroup.id) === String(id))?.name ?? '';
    },

    syncGroupFromSearch() {
        const typed = this.groupSearch.trim().toLocaleLowerCase();
        const match = this.filteredGroups.find((group) => group.name.toLocaleLowerCase() === typed);
        const nextGroupId = match ? String(match.id) : '';

        if (this.form.product_group_id !== nextGroupId) {
            this.form.product_subgroup_id = '';
            this.subgroupSearch = '';
        }

        this.form.product_group_id = nextGroupId;
    },

    normalizeGroupSearch() {
        this.groupSearch = this.labelForGroup(this.form.product_group_id);
        window.setTimeout(() => {
            this.groupMenuOpen = false;
        }, 100);
    },

    openGroupMenu() {
        this.groupMenuOpen = true;
        this.subgroupMenuOpen = false;
    },

    selectGroup(group) {
        const nextGroupId = String(group.id);

        if (this.form.product_group_id !== nextGroupId) {
            this.form.product_subgroup_id = '';
            this.subgroupSearch = '';
        }

        this.form.product_group_id = nextGroupId;
        this.groupSearch = group.name;
        this.groupMenuOpen = false;
    },

    syncSubgroupFromSearch() {
        const typed = this.subgroupSearch.trim().toLocaleLowerCase();
        const match = this.filteredSubgroups.find((subgroup) => subgroup.name.toLocaleLowerCase() === typed);
        this.form.product_subgroup_id = match ? String(match.id) : '';
    },

    normalizeSubgroupSearch() {
        this.subgroupSearch = this.labelForSubgroup(this.form.product_subgroup_id);
        window.setTimeout(() => {
            this.subgroupMenuOpen = false;
        }, 100);
    },

    openSubgroupMenu() {
        this.subgroupMenuOpen = true;
        this.groupMenuOpen = false;
    },

    selectSubgroup(subgroup) {
        this.form.product_subgroup_id = String(subgroup.id);
        this.subgroupSearch = subgroup.name;
        this.subgroupMenuOpen = false;
    },

    urlFor(action, type, id = null) {
        const key = `${type}${action}`;
        const url = config.urls?.[key] ?? '';

        return id ? url.replace('__ID__', encodeURIComponent(id)) : url;
    },

    payload() {
        const data = new FormData();

        if (this.form.company_id) {
            data.append('company_id', this.form.company_id);
        }

        if (this.tab === 'subgroup' || this.tab === 'product') {
            data.append('product_group_id', this.form.product_group_id);
        }

        if (this.tab === 'product') {
            data.append('product_subgroup_id', this.form.product_subgroup_id);
        }

        data.append('name', this.form.name);

        return data;
    },

    applyResponse(data) {
        const message = data.message ?? 'Operación realizada correctamente.';
        try { sessionStorage.setItem('_catalog_toast', JSON.stringify({ message, type: 'success' })); } catch {}
        window.location.reload();
    },

    async saveRecord() {
        if (this.saving) {
            return;
        }

        this.saving = true;
        this.clearMessages();

        try {
            const action = this.editingId ? 'Update' : 'Store';
            const url = this.urlFor(action, this.tab, this.editingId);
            const formData = this.payload();

            if (this.editingId) {
                formData.append('_method', 'PATCH');
            }

            const response = await window.axios.post(url, formData, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });

            this.clearDraft(this.tab);
            await this.goToPage(window.location.href, false);
            this.showToast(response.data?.message ?? 'Estado actualizado correctamente.', 'success');
        } catch (error) {
            if (error.response?.status === 422 && error.response.data?.errors) {
                const firstMessage = Object.values(error.response.data.errors).flat()[0];
                this.showErrorToast(firstMessage);
            } else {
                this.showErrorToast(error.response?.data?.message || error.message || 'No fue posible guardar el registro.');
            }
        } finally {
            this.saving = false;
        }
    },

    async toggleStatus(type, id, status) {
        try {
            const scrollTop = window.scrollY || window.pageYOffset || 0;
            const formData = new FormData();
            formData.append('_method', 'PATCH');
            formData.append('status', status);

            const response = await window.axios.post(this.urlFor('Status', type, id), formData, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });

            await this.goToPage(window.location.href, false);
            window.requestAnimationFrame(() => {
                window.scrollTo({ top: scrollTop, behavior: 'auto' });
            });
            this.showToast(response.data?.message ?? 'Estado actualizado correctamente.', 'success');
        } catch (error) {
            window.dispatchEvent(new CustomEvent('crud-toast', {
                detail: { message: error.response?.data?.message || 'No fue posible cambiar el estado.', type: 'error' },
            }));
        }
    },

    async archiveRecord(type, id) {
        if (! window.confirm('¿Deseas archivar este registro?')) {
            return;
        }

        try {
            const response = await window.axios.delete(this.urlFor('Destroy', type, id), {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });

            await this.goToPage(window.location.href, false);
            this.showToast(response.data?.message ?? 'Estado actualizado correctamente.', 'success');
        } catch (error) {
            window.dispatchEvent(new CustomEvent('crud-toast', {
                detail: { message: error.response?.data?.message || 'No fue posible archivar el registro.', type: 'error' },
            }));
        }
    },

    async goToPage(url, pushState = true) {
        if (! url || this.tableLoading) {
            return;
        }

        this.tableLoading = true;

        try {
            const targetUrl = new URL(url, window.location.origin);
            const requestUrl = new URL(targetUrl);
            requestUrl.searchParams.set('table_only', '1');

            const response = await window.axios.get(requestUrl.toString(), {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });

            if (this.$refs.productsTable) {
                this.$refs.productsTable.innerHTML = response.data?.products_table_html ?? response.data?.table_html ?? '';
                Alpine.initTree(this.$refs.productsTable);
            }

            if (this.$refs.groupsTable && response.data?.groups_table_html !== undefined) {
                this.$refs.groupsTable.innerHTML = response.data.groups_table_html;
                Alpine.initTree(this.$refs.groupsTable);
            }

            if (this.$refs.subgroupsTable && response.data?.subgroups_table_html !== undefined) {
                this.$refs.subgroupsTable.innerHTML = response.data.subgroups_table_html;
                Alpine.initTree(this.$refs.subgroupsTable);
            }

            if (Array.isArray(response.data?.groups)) {
                this.groups = response.data.groups;
            }

            if (Array.isArray(response.data?.subgroups)) {
                this.subgroups = response.data.subgroups;
            }

            this.syncCollectionsState();

            if (pushState) {
                window.history.pushState({ catalogTable: true }, '', `${targetUrl.pathname}${targetUrl.search}`);
            }
        } catch (error) {
            window.location.assign(url);
        } finally {
            this.tableLoading = false;
        }
    },
}));

Alpine.data('activityCatalog', (config = {}) => ({
    modalOpen: false,
    tab: 'group',
    editingId: null,
    saving: false,
    error: '',
    errors: {},
    toastVisible: false,
    toastMessage: '',
    toastType: 'success',
    toastTimer: null,
    isSuperAdmin: config.isSuperAdmin === true,
    visibilityStorageKey: config.visibilityStorageKey ?? '',
    tableVisibilityDefaults: {
        groups: true,
        subgroups: true,
        activities: true,
        ...(config.tableVisibilityDefaults ?? {}),
    },
    tableVisibility: {
        groups: true,
        subgroups: true,
        activities: true,
    },
    filtersOpen: window.innerWidth >= 768,
    tableLoading: false,
    groups: config.groups ?? [],
    subgroups: config.subgroups ?? [],
    filterGroupId: config.filterGroupId ?? '',
    filterSubgroupId: config.filterSubgroupId ?? '',
    groupSearch: '',
    subgroupSearch: '',
    groupMenuOpen: false,
    subgroupMenuOpen: false,
    form: {
        company_id: config.companyId ? String(config.companyId) : '',
        activity_group_id: '',
        activity_subgroup_id: '',
        name: '',
    },
    drafts: {
        group: null,
        subgroup: null,
        activity: null,
    },

    init() {
        this.initTableVisibility();

        try {
            const stored = sessionStorage.getItem('_catalog_toast');
            if (stored) {
                sessionStorage.removeItem('_catalog_toast');
                const { message, type } = JSON.parse(stored);
                this.showToast(message, type ?? 'success');
            }
        } catch {}

        window.addEventListener('crud-toast', (event) => {
            this.showToast(event.detail?.message, event.detail?.type ?? 'success');
        });
    },

    showToast(message, type = 'success') {
        if (! message) {
            return;
        }

        window.clearTimeout(this.toastTimer);
        this.toastMessage = message;
        this.toastType = type;
        this.toastVisible = true;
        this.toastTimer = window.setTimeout(() => this.hideToast(), 3500);
    },

    hideToast() {
        this.toastVisible = false;
    },

    get filteredGroups() {
        const companyId = this.form.company_id ? String(this.form.company_id) : '';

        return this.groups.filter((group) => ! companyId || String(group.company_id) === companyId);
    },

    get visibleGroups() {
        const term = this.groupSearch.trim().toLocaleLowerCase();

        return this.filteredGroups.filter((group) => ! term || group.name.toLocaleLowerCase().includes(term));
    },

    get filteredSubgroups() {
        const companyId = this.form.company_id ? String(this.form.company_id) : '';
        const groupId = this.form.activity_group_id ? String(this.form.activity_group_id) : '';

        return this.subgroups.filter((subgroup) => (
            (! companyId || String(subgroup.company_id) === companyId)
            && (! groupId || String(subgroup.activity_group_id) === groupId)
        ));
    },

    get visibleSubgroups() {
        const term = this.subgroupSearch.trim().toLocaleLowerCase();

        return this.filteredSubgroups.filter((subgroup) => ! term || subgroup.name.toLocaleLowerCase().includes(term));
    },

    get filterableGroups() {
        return this.groups;
    },

    get catalogFilterSubgroups() {
        if (! this.filterGroupId) {
            return this.subgroups;
        }

        return this.subgroups.filter((subgroup) => String(subgroup.activity_group_id) === String(this.filterGroupId));
    },

    initTableVisibility() {
        this.tableVisibility = { ...this.tableVisibilityDefaults };

        if (! this.isSuperAdmin || ! this.visibilityStorageKey) {
            return;
        }

        try {
            const stored = window.localStorage.getItem(this.visibilityStorageKey);
            if (! stored) {
                return;
            }

            const parsed = JSON.parse(stored);
            this.tableVisibility = {
                ...this.tableVisibilityDefaults,
                ...parsed,
            };
        } catch {}
    },

    persistTableVisibility() {
        if (! this.isSuperAdmin || ! this.visibilityStorageKey) {
            return;
        }

        try {
            window.localStorage.setItem(this.visibilityStorageKey, JSON.stringify(this.tableVisibility));
        } catch {}
    },

    saveCurrentDraft() {
        if (this.editingId) {
            return;
        }

        this.drafts[this.tab] = {
            form: { ...this.form },
            groupSearch: this.groupSearch,
            subgroupSearch: this.subgroupSearch,
        };
    },

    restoreDraft(tab) {
        if (this.editingId) {
            return;
        }

        const draft = this.drafts[tab];

        if (! draft) {
            this.form = {
                company_id: config.companyId ? String(config.companyId) : '',
                activity_group_id: '',
                activity_subgroup_id: '',
                name: '',
            };
            this.groupSearch = '';
            this.subgroupSearch = '';
            return;
        }

        this.form = { ...draft.form };
        this.groupSearch = draft.groupSearch ?? '';
        this.subgroupSearch = draft.subgroupSearch ?? '';
    },

    clearDraft(tab) {
        this.drafts[tab] = null;
    },

    syncFilterSubgroupSelection() {
        if (! this.filterSubgroupId) {
            return;
        }

        const hasSelectedSubgroup = this.catalogFilterSubgroups.some((subgroup) => String(subgroup.id) === String(this.filterSubgroupId));

        if (! hasSelectedSubgroup) {
            this.filterSubgroupId = '';
        }
    },

    syncCollectionsState() {
        const hasSelectedGroup = ! this.form.activity_group_id
            || this.filteredGroups.some((group) => String(group.id) === String(this.form.activity_group_id));

        if (! hasSelectedGroup) {
            this.form.activity_group_id = '';
            this.groupSearch = '';
            this.form.activity_subgroup_id = '';
            this.subgroupSearch = '';
        } else if (this.form.activity_group_id) {
            this.groupSearch = this.labelForGroup(this.form.activity_group_id);
        }

        const hasSelectedSubgroup = ! this.form.activity_subgroup_id
            || this.filteredSubgroups.some((subgroup) => String(subgroup.id) === String(this.form.activity_subgroup_id));

        if (! hasSelectedSubgroup) {
            this.form.activity_subgroup_id = '';
            this.subgroupSearch = '';
        } else if (this.form.activity_subgroup_id) {
            this.subgroupSearch = this.labelForSubgroup(this.form.activity_subgroup_id);
        }

        const hasFilterGroup = ! this.filterGroupId
            || this.filterableGroups.some((group) => String(group.id) === String(this.filterGroupId));

        if (! hasFilterGroup) {
            this.filterGroupId = '';
        }

        this.syncFilterSubgroupSelection();
    },

    setTab(tab) {
        if (this.tab === tab) {
            return;
        }

        this.saveCurrentDraft();

        this.tab = tab;
        this.editingId = null;
        this.clearMessages();
        this.groupMenuOpen = false;
        this.subgroupMenuOpen = false;

        this.restoreDraft(tab);
    },

    openModal(tab = 'group') {
        this.modalOpen = true;
        this.tab = tab;
        this.editingId = null;
        this.clearMessages();
        this.groupMenuOpen = false;
        this.subgroupMenuOpen = false;
        this.restoreDraft(tab);
    },

    closeModal() {
        this.saveCurrentDraft();
        this.modalOpen = false;
        this.editingId = null;
        this.clearMessages();
    },

    editRecord(type, record) {
        this.modalOpen = true;
        this.tab = type;
        this.editingId = record.id;
        this.clearMessages();
        this.form = {
            company_id: record.company_id ? String(record.company_id) : '',
            activity_group_id: record.activity_group_id ? String(record.activity_group_id) : '',
            activity_subgroup_id: record.activity_subgroup_id ? String(record.activity_subgroup_id) : '',
            name: record.name ?? '',
        };
        this.groupSearch = this.labelForGroup(this.form.activity_group_id);
        this.subgroupSearch = this.labelForSubgroup(this.form.activity_subgroup_id);
    },

    clearMessages() {
        this.error = '';
        this.errors = {};
    },

    friendlyError(message) {
        const translations = {
            'The company id field is required.': 'Selecciona una empresa.',
            'The activity group id field is required.': 'Selecciona un grupo.',
            'The activity subgroup id field is required.': 'Selecciona un subgrupo.',
            'The name field is required.': 'Escribe un nombre.',
            'The name has already been taken.': 'Ese nombre ya existe.',
            'The selected company id is invalid.': 'La empresa seleccionada no es valida.',
            'The selected activity group id is invalid.': 'El grupo seleccionado no es valido.',
            'The selected activity subgroup id is invalid.': 'El subgrupo seleccionado no es valido.',
        };

        return translations[message] ?? message ?? 'Revisa la informacion enviada.';
    },

    showErrorToast(message) {
        window.dispatchEvent(new CustomEvent('crud-toast', {
            detail: { message: this.friendlyError(message), type: 'error' },
        }));
    },

    labelForGroup(id) {
        return this.groups.find((group) => String(group.id) === String(id))?.name ?? '';
    },

    labelForSubgroup(id) {
        return this.subgroups.find((subgroup) => String(subgroup.id) === String(id))?.name ?? '';
    },

    syncGroupFromSearch() {
        const typed = this.groupSearch.trim().toLocaleLowerCase();
        const match = this.filteredGroups.find((group) => group.name.toLocaleLowerCase() === typed);
        const nextGroupId = match ? String(match.id) : '';

        if (this.form.activity_group_id !== nextGroupId) {
            this.form.activity_subgroup_id = '';
            this.subgroupSearch = '';
        }

        this.form.activity_group_id = nextGroupId;
    },

    normalizeGroupSearch() {
        this.groupSearch = this.labelForGroup(this.form.activity_group_id);
        window.setTimeout(() => {
            this.groupMenuOpen = false;
        }, 100);
    },

    openGroupMenu() {
        this.groupMenuOpen = true;
        this.subgroupMenuOpen = false;
    },

    selectGroup(group) {
        const nextGroupId = String(group.id);

        if (this.form.activity_group_id !== nextGroupId) {
            this.form.activity_subgroup_id = '';
            this.subgroupSearch = '';
        }

        this.form.activity_group_id = nextGroupId;
        this.groupSearch = group.name;
        this.groupMenuOpen = false;
    },

    syncSubgroupFromSearch() {
        const typed = this.subgroupSearch.trim().toLocaleLowerCase();
        const match = this.filteredSubgroups.find((subgroup) => subgroup.name.toLocaleLowerCase() === typed);
        this.form.activity_subgroup_id = match ? String(match.id) : '';
    },

    normalizeSubgroupSearch() {
        this.subgroupSearch = this.labelForSubgroup(this.form.activity_subgroup_id);
        window.setTimeout(() => {
            this.subgroupMenuOpen = false;
        }, 100);
    },

    openSubgroupMenu() {
        this.subgroupMenuOpen = true;
        this.groupMenuOpen = false;
    },

    selectSubgroup(subgroup) {
        this.form.activity_subgroup_id = String(subgroup.id);
        this.subgroupSearch = subgroup.name;
        this.subgroupMenuOpen = false;
    },

    urlFor(action, type, id = null) {
        const key = `${type}${action}`;
        const url = config.urls?.[key] ?? '';

        return id ? url.replace('__ID__', encodeURIComponent(id)) : url;
    },

    payload() {
        const data = new FormData();

        if (this.form.company_id) {
            data.append('company_id', this.form.company_id);
        }

        if (this.tab === 'subgroup' || this.tab === 'activity') {
            data.append('activity_group_id', this.form.activity_group_id);
        }

        if (this.tab === 'activity') {
            data.append('activity_subgroup_id', this.form.activity_subgroup_id);
        }

        data.append('name', this.form.name);

        return data;
    },

    applyResponse(data) {
        const message = data.message ?? 'Operacion realizada correctamente.';
        try { sessionStorage.setItem('_catalog_toast', JSON.stringify({ message, type: 'success' })); } catch {}
        window.location.reload();
    },

    async saveRecord() {
        if (this.saving) {
            return;
        }

        this.saving = true;
        this.clearMessages();

        try {
            const action = this.editingId ? 'Update' : 'Store';
            const url = this.urlFor(action, this.tab, this.editingId);
            const formData = this.payload();

            if (this.editingId) {
                formData.append('_method', 'PATCH');
            }

            const response = await window.axios.post(url, formData, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });

            this.clearDraft(this.tab);
            await this.goToPage(window.location.href, false);
            this.showToast(response.data?.message ?? 'Estado actualizado correctamente.', 'success');
        } catch (error) {
            if (error.response?.status === 422 && error.response.data?.errors) {
                const firstMessage = Object.values(error.response.data.errors).flat()[0];
                this.showErrorToast(firstMessage);
            } else {
                this.showErrorToast(error.response?.data?.message || error.message || 'No fue posible guardar el registro.');
            }
        } finally {
            this.saving = false;
        }
    },

    async toggleStatus(type, id, status) {
        try {
            const scrollTop = window.scrollY || window.pageYOffset || 0;
            const formData = new FormData();
            formData.append('_method', 'PATCH');
            formData.append('status', status);

            const response = await window.axios.post(this.urlFor('Status', type, id), formData, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });

            await this.goToPage(window.location.href, false);
            window.requestAnimationFrame(() => {
                window.scrollTo({ top: scrollTop, behavior: 'auto' });
            });
            this.showToast(response.data?.message ?? 'Estado actualizado correctamente.', 'success');
        } catch (error) {
            window.dispatchEvent(new CustomEvent('crud-toast', {
                detail: { message: error.response?.data?.message || 'No fue posible cambiar el estado.', type: 'error' },
            }));
        }
    },

    async archiveRecord(type, id) {
        if (! window.confirm('Deseas archivar este registro?')) {
            return;
        }

        try {
            const response = await window.axios.delete(this.urlFor('Destroy', type, id), {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });

            await this.goToPage(window.location.href, false);
            this.showToast(response.data?.message ?? 'Estado actualizado correctamente.', 'success');
        } catch (error) {
            window.dispatchEvent(new CustomEvent('crud-toast', {
                detail: { message: error.response?.data?.message || 'No fue posible archivar el registro.', type: 'error' },
            }));
        }
    },

    async goToPage(url, pushState = true) {
        if (! url || this.tableLoading) {
            return;
        }

        this.tableLoading = true;

        try {
            const targetUrl = new URL(url, window.location.origin);
            const requestUrl = new URL(targetUrl);
            requestUrl.searchParams.set('table_only', '1');

            const response = await window.axios.get(requestUrl.toString(), {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });

            if (this.$refs.activitiesTable) {
                this.$refs.activitiesTable.innerHTML = response.data?.activities_table_html ?? response.data?.table_html ?? '';
                Alpine.initTree(this.$refs.activitiesTable);
            }

            if (this.$refs.groupsTable && response.data?.groups_table_html !== undefined) {
                this.$refs.groupsTable.innerHTML = response.data.groups_table_html;
                Alpine.initTree(this.$refs.groupsTable);
            }

            if (this.$refs.subgroupsTable && response.data?.subgroups_table_html !== undefined) {
                this.$refs.subgroupsTable.innerHTML = response.data.subgroups_table_html;
                Alpine.initTree(this.$refs.subgroupsTable);
            }

            if (Array.isArray(response.data?.groups)) {
                this.groups = response.data.groups;
            }

            if (Array.isArray(response.data?.subgroups)) {
                this.subgroups = response.data.subgroups;
            }

            this.syncCollectionsState();

            if (pushState) {
                window.history.pushState({ catalogTable: true }, '', `${targetUrl.pathname}${targetUrl.search}`);
            }
        } catch (error) {
            window.location.assign(url);
        } finally {
            this.tableLoading = false;
        }
    },
}));

Alpine.data('reportsPage', () => ({
    historyLoading: false,

    async handleHistoryClick(event) {
        const link = event.target.closest('[x-ref="historyBlock"] a');

        if (! link) {
            return;
        }

        event.preventDefault();

        if (this.historyLoading) {
            return;
        }

        this.historyLoading = true;

        try {
            const url = new URL(link.href, window.location.origin);
            url.searchParams.set('history_only', '1');

            const response = await window.axios.get(url.toString(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'text/html, application/xhtml+xml',
                },
            });

            if (this.$refs.historyBlock) {
                this.$refs.historyBlock.innerHTML = response.data;
            }

            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('page', url.searchParams.get('page') || '1');
            window.history.replaceState({}, '', currentUrl);
            window.scrollTo({ top: this.$refs.historyBlock.offsetTop - 96, behavior: 'smooth' });
        } finally {
            this.historyLoading = false;
        }
    },
}));

Alpine.data('projectStructureState', () => ({
    selectedCategoryId: null,
    selectedSubcategoryId: null,

    restoreSelection(element) {
        this.selectedCategoryId = Number(element.dataset.initialSelectedCategoryId || element.dataset.selectedCategoryId) || null;
        this.selectedSubcategoryId = Number(element.dataset.initialSelectedSubcategoryId || element.dataset.selectedSubcategoryId) || null;
        this.persistSelection(element);
    },

    persistSelection(element) {
        element.dataset.selectedCategoryId = this.selectedCategoryId ?? '';
        element.dataset.selectedSubcategoryId = this.selectedSubcategoryId ?? '';
        window.projectStructureSelection = {
            selectedCategoryId: element.dataset.selectedCategoryId,
            selectedSubcategoryId: element.dataset.selectedSubcategoryId,
        };
    },
}));

Alpine.data('assetTypeManager', (config = {}) => {
const selectEls = [];
let _selectedTypeId = String(config.selectedTypeId ?? '');
return {
    types: config.types ?? [],
    selectedTypeId: String(config.selectedTypeId ?? ''),
    previousTypeId: String(config.selectedTypeId ?? ''),
    companyId: String(config.initialCompanyId ?? ''),
    entityName: config.entityName ?? 'activo',
    quantity: String(config.quantity ?? '1'),
    purchaseValue: String(config.purchaseValue ?? ''),
    allowEmptySelection: config.allowEmptySelection === true,
    storeUrl: config.storeUrl,
    indexUrl: config.indexUrl,
    managerOpen: false,
    managerSaving: false,
    managerLoading: false,
    managerError: '',
    _formSnapshot: null,
    draft: {
        id: null,
        name: '',
        adds_value: true,
        status: 'active',
        update_url: '',
        delete_url: '',
    },

    init() {
        this.normalizeTypeSelection();
    },

    // Called via x-effect on each select element — registers the element and
    // builds initial options. Subsequent updates are driven imperatively from
    // normalizeTypeSelection() since Alpine reactivity doesn't cross the
    // Alpine.initTree / x-teleport boundary in this app's modal architecture.
    syncTypeSelect(el) {
        if (!selectEls.includes(el)) {
            selectEls.push(el);
        }
        this._buildSelectOptions(el);
    },

    _buildSelectOptions(el) {
        const activeTypes = this.activeTypes;
        const selectedId = _selectedTypeId; // closure var — not tracked by x-effect

        while (el.options.length > 0) {
            el.remove(0);
        }

        el.add(new Option(
            this.allowEmptySelection
                ? 'Sin tipo'
                : (activeTypes.length === 0 ? 'No hay tipos activos' : 'Selecciona un tipo'),
            ''
        ));

        for (const type of activeTypes) {
            el.add(new Option(type.name, String(type.id)));
        }

        el.value = selectedId;
        el.disabled = !this.allowEmptySelection && activeTypes.length === 0;
    },

    get activeTypes() {
        return this.types.filter((type) => type.status === 'active');
    },

    get selectedType() {
        return this.types.find((type) => String(type.id) === String(this.selectedTypeId)) ?? null;
    },

    get selectedTypeAddsValue() {
        return this.selectedType?.adds_value !== false;
    },

    parseCurrency(value) {
        const normalized = String(value ?? '').replace(/\D+/g, '');
        return normalized === '' ? 0 : Number.parseInt(normalized, 10);
    },

    totalPurchaseValue() {
        const quantity = Math.max(1, Number.parseInt(String(this.quantity ?? '1').replace(/\D+/g, ''), 10) || 1);
        return this.parseCurrency(this.purchaseValue) * quantity;
    },

    formatCurrency(value) {
        return Number(value || 0).toLocaleString('es-CO');
    },

    handleTypeChange(event) {
        event.preventDefault();
        event.stopPropagation();

        const value = event.target.value;

        if (value === '__manage__') {
            event.target.value = this.selectedTypeId;
            this.openManager();
            return;
        }

        this.previousTypeId = this.selectedTypeId;
        this.selectedTypeId = value;
        _selectedTypeId = value;

        selectEls.forEach((select) => {
            if (select !== event.target) {
                select.value = value;
            }
        });
    },

    openManager() {
        window.dispatchEvent(new CustomEvent('crud-save-open-modal-draft'));

        this.managerOpen = true;
        this.managerError = '';
        this.resetDraft();

        this.$nextTick(() => {
            this.loadTypes(true);
        });
    },

    closeManager() {
        window.dispatchEvent(new CustomEvent('crud-save-open-modal-draft'));

        this.managerOpen = false;
        this.resetDraft();

        this.$nextTick(() => {
            window.dispatchEvent(new CustomEvent('crud-refresh-open-modal-from-draft'));
        });
    },

    resetDraft() {
        this.managerError = '';
        this.draft = {
            id: null,
            name: '',
            adds_value: true,
            status: 'active',
            update_url: '',
            delete_url: '',
        };
    },

    editType(type) {
        this.managerError = '';
        this.draft = {
            id: type.id,
            name: type.name,
            adds_value: Boolean(type.adds_value),
            status: type.status,
            update_url: type.update_url,
            delete_url: type.delete_url,
        };
    },

    async loadTypes(showLoading = false) {
        if (! this.companyId || ! this.indexUrl) {
            this.types = [];
            this.selectedTypeId = '';
            _selectedTypeId = '';
            this.previousTypeId = '';
            return;
        }

        if (showLoading) {
            this.managerLoading = true;
        }

        const startedAt = Date.now();
        try {
            const response = await window.axios.get(this.indexUrl, {
                params: { company_id: this.companyId },
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            this.applyTypes(response.data.types ?? []);
        } catch (error) {
            this.managerError = error.response?.data?.message || error.message || 'No fue posible cargar los tipos.';
        } finally {
            if (! showLoading) {
                this.managerLoading = false;
                return;
            }

            const elapsed = Date.now() - startedAt;
            const remaining = Math.max(0, 1000 - elapsed);

            window.setTimeout(() => {
                this.managerLoading = false;
            }, remaining);
        }
    },

    async saveType() {
        if (this.managerSaving) {
            return;
        }

        if (! this.draft.name.trim()) {
            this.managerError = 'Escribe el nombre del tipo.';
            return;
        }

        if (! this.companyId) {
            this.managerError = 'Selecciona una empresa antes de crear tipos.';
            return;
        }

        this.managerSaving = true;
        this.managerError = '';

        try {
            const payload = {
                company_id: this.companyId,
                name: this.draft.name.trim(),
                adds_value: this.draft.adds_value ? 1 : 0,
                status: this.draft.status,
            };

            const response = this.draft.id
                ? await window.axios.patch(this.draft.update_url, payload, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                })
                : await window.axios.post(this.storeUrl, payload, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

            const wasCreating = ! this.draft.id;
            this.applyTypes(response.data.types ?? []);

            if (wasCreating) {
                const createdId = response.data.selected_type_id ? String(response.data.selected_type_id) : '';
                const created = createdId
                    ? this.types.find((type) => String(type.id) === createdId)
                    : this.types.find((type) => type.name.toLowerCase() === payload.name.toLowerCase());
                if (created?.status === 'active') {
                    this.selectedTypeId = String(created.id);
                    this.previousTypeId = this.selectedTypeId;
                    _selectedTypeId = this.selectedTypeId;
                    selectEls.forEach((el) => { el.value = _selectedTypeId; });
                }
            }

            this.resetDraft();

            window.dispatchEvent(new CustomEvent('crud-save-open-modal-draft'));

            window.dispatchEvent(new CustomEvent('crud-toast', {
                detail: { message: response.data.message ?? 'Tipo actualizado correctamente.' },
            }));
        } catch (error) {
            this.managerError = error.response?.data?.message
                || Object.values(error.response?.data?.errors ?? {}).flat()[0]
                || error.message
                || 'No fue posible guardar el tipo.';
        } finally {
            this.managerSaving = false;
        }
    },

    async quickUpdateType(type, changes, event = null) {
        event?.preventDefault?.();
        event?.stopPropagation?.();

        if (this.managerSaving || ! type?.update_url) {
            return;
        }

        const previousType = { ...type };
        const nextType = {
            ...type,
            ...(Object.prototype.hasOwnProperty.call(changes, 'adds_value')
                ? { adds_value: Boolean(changes.adds_value) }
                : {}),
            ...(Object.prototype.hasOwnProperty.call(changes, 'status')
                ? { status: changes.status }
                : {}),
        };

        this.updateTypeInPlace(nextType);
        this.managerOpen = true;
        this.managerSaving = true;
        this.managerError = '';

        try {
            const response = await window.axios.patch(nextType.update_url, {
                company_id: this.companyId,
                name: nextType.name,
                adds_value: nextType.adds_value ? 1 : 0,
                status: nextType.status,
            }, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const savedType = (response.data.types ?? [])
                .find((currentType) => String(currentType.id) === String(nextType.id));

            if (savedType) {
                this.updateTypeInPlace(savedType);
            }

            this.managerOpen = true;

            window.dispatchEvent(new CustomEvent('crud-save-open-modal-draft'));

            window.dispatchEvent(new CustomEvent('crud-toast', {
                detail: { message: response.data.message ?? 'Tipo actualizado correctamente.' },
            }));
        } catch (error) {
            this.updateTypeInPlace(previousType);
            this.managerOpen = true;
            this.managerError = error.response?.data?.message
                || Object.values(error.response?.data?.errors ?? {}).flat()[0]
                || error.message
                || 'No fue posible actualizar el tipo.';
        } finally {
            this.managerSaving = false;
        }
    },

    updateTypeInPlace(nextType) {
        const index = this.types.findIndex((currentType) => String(currentType.id) === String(nextType.id));

        if (index === -1) {
            return;
        }

        this.types[index] = { ...this.types[index], ...nextType };
        this.types = [...this.types];

        this.normalizeTypeSelection();
    },

    normalizeTypeSelection() {
        if (! this.selectedType || this.selectedType.status !== 'active') {
            this.selectedTypeId = this.allowEmptySelection
                ? ''
                : (this.activeTypes.length > 0 ? String(this.activeTypes[0].id) : '');
        }

        this.previousTypeId = this.selectedTypeId;
        _selectedTypeId = this.selectedTypeId;

        selectEls.forEach((select) => {
            this._buildSelectOptions(select);
        });
    },

    async deleteType(type) {
        if (this.managerSaving) {
            return;
        }

        if (! type.can_delete) {
            this.managerError = `No puedes eliminar un tipo que ya tiene ${this.entityName === 'novedad' ? 'novedades' : 'activos'} asociados.`;
            return;
        }

        if (! window.confirm(`¿Deseas eliminar este tipo de ${this.entityName}?`)) {
            return;
        }

        this.managerSaving = true;
        this.managerError = '';

        try {
            const response = await window.axios.delete(type.delete_url, {
                params: { company_id: this.companyId },
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            this.applyTypes(response.data.types ?? []);
            this.resetDraft();

            window.dispatchEvent(new CustomEvent('crud-save-open-modal-draft'));

            window.dispatchEvent(new CustomEvent('crud-toast', {
                detail: { message: response.data.message ?? 'Tipo eliminado correctamente.' },
            }));
        } catch (error) {
            this.managerError = error.response?.data?.message || error.message || 'No fue posible eliminar el tipo.';
        } finally {
            this.managerSaving = false;
        }
    },

    applyTypes(types) {
        this.types = types;
        this.normalizeTypeSelection();
    },


}; });

Alpine.data('provider2TypeManager', (config = {}) => {
const selectEls = [];
let _selectedTypeId = String(config.selectedTypeId ?? '');
return {
    types: config.types ?? [],
    selectedTypeId: String(config.selectedTypeId ?? ''),
    previousTypeId: String(config.selectedTypeId ?? ''),
    companyId: String(config.initialCompanyId ?? ''),
    entityName: config.entityName ?? 'proveedor',
    allowEmptySelection: config.allowEmptySelection === true,
    storeUrl: config.storeUrl,
    indexUrl: config.indexUrl,
    managerOpen: false,
    managerSaving: false,
    managerLoading: false,
    managerError: '',
    _formSnapshot: null,
    draft: {
        id: null,
        name: '',
        status: 'active',
        update_url: '',
        delete_url: '',
    },

    init() {
        this.normalizeTypeSelection();
    },

    // Called via x-effect on each select element — registers the element and
    // builds initial options. Subsequent updates are driven imperatively from
    // normalizeTypeSelection() since Alpine reactivity doesn't cross the
    // Alpine.initTree / x-teleport boundary in this app's modal architecture.
    syncTypeSelect(el) {
        if (!selectEls.includes(el)) {
            selectEls.push(el);
        }
        this._buildSelectOptions(el);
    },

    _buildSelectOptions(el) {
        const activeTypes = this.activeTypes;
        const selectedId = _selectedTypeId; // closure var — not tracked by x-effect

        while (el.options.length > 0) {
            el.remove(0);
        }

        el.add(new Option(
            this.allowEmptySelection
                ? 'Sin tipo'
                : (activeTypes.length === 0 ? 'No hay tipos activos' : 'Selecciona un tipo'),
            ''
        ));

        for (const type of activeTypes) {
            el.add(new Option(type.name, String(type.id)));
        }

        el.value = selectedId;
        el.disabled = !this.allowEmptySelection && activeTypes.length === 0;
    },

    get activeTypes() {
        return this.types.filter((type) => type.status === 'active');
    },

    get selectedType() {
        return this.types.find((type) => String(type.id) === String(this.selectedTypeId)) ?? null;
    },

    get selectedTypeAddsValue() {
        return this.selectedType?.adds_value !== false;
    },

    handleTypeChange(event) {
        const value = event.target.value;

        this.previousTypeId = this.selectedTypeId;
        this.selectedTypeId = value;
        _selectedTypeId = value;

        selectEls.forEach((select) => {
            if (select !== event.target) {
                select.value = value;
            }
        });
    },

    openManager() {
        window.dispatchEvent(new CustomEvent('crud-save-open-modal-draft'));

        this.managerOpen = true;
        this.managerError = '';
        this.resetDraft();

        this.$nextTick(() => {
            this.loadTypes(true);
        });
    },

    closeManager() {
        window.dispatchEvent(new CustomEvent('crud-save-open-modal-draft'));

        this.managerOpen = false;
        this.resetDraft();

        selectEls.forEach((el) => this._buildSelectOptions(el));

        this.$nextTick(() => {
            window.dispatchEvent(new CustomEvent('crud-refresh-open-modal-from-draft'));
        });
    },

    resetDraft() {
        this.managerError = '';
        this.draft = {
            id: null,
            name: '',
            status: 'active',
            update_url: '',
            delete_url: '',
        };
    },

    editType(type) {
        this.managerError = '';
        this.draft = {
            id: type.id,
            name: type.name,
            adds_value: Boolean(type.adds_value),
            status: type.status,
            update_url: type.update_url,
            delete_url: type.delete_url,
        };
    },

    async loadTypes(showLoading = false) {
        if (! this.companyId || ! this.indexUrl) {
            this.types = [];
            this.selectedTypeId = '';
            _selectedTypeId = '';
            this.previousTypeId = '';
            return;
        }

        if (showLoading) {
            this.managerLoading = true;
        }

        const startedAt = Date.now();
        try {
            const response = await window.axios.get(this.indexUrl, {
                params: { company_id: this.companyId },
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            this.applyTypes(response.data.types ?? []);
        } catch (error) {
            this.managerError = error.response?.data?.message || error.message || 'No fue posible cargar los tipos.';
        } finally {
            if (! showLoading) {
                this.managerLoading = false;
                return;
            }

            const elapsed = Date.now() - startedAt;
            const remaining = Math.max(0, 1000 - elapsed);

            window.setTimeout(() => {
                this.managerLoading = false;
            }, remaining);
        }
    },

    async saveType() {
        if (this.managerSaving) {
            return;
        }

        if (! this.draft.name.trim()) {
            this.managerError = 'Escribe el nombre del tipo.';
            return;
        }

        if (! this.companyId) {
            this.managerError = 'Selecciona una empresa antes de crear tipos.';
            return;
        }

        this.managerSaving = true;
        this.managerError = '';

        try {
            const payload = {
                company_id: this.companyId,
                name: this.draft.name.trim(),
                status: this.draft.status,
            };

            const response = this.draft.id
                ? await window.axios.patch(this.draft.update_url, payload, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                })
                : await window.axios.post(this.storeUrl, payload, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

            const wasCreating = ! this.draft.id;
            this.applyTypes(response.data.types ?? []);

            if (wasCreating) {
                const createdId = response.data.selected_type_id ? String(response.data.selected_type_id) : '';
                const created = createdId
                    ? this.types.find((type) => String(type.id) === createdId)
                    : this.types.find((type) => type.name.toLowerCase() === payload.name.toLowerCase());
                if (created?.status === 'active') {
                    this.selectedTypeId = String(created.id);
                    this.previousTypeId = this.selectedTypeId;
                    _selectedTypeId = this.selectedTypeId;
                    selectEls.forEach((el) => { el.value = _selectedTypeId; });
                }
            }

            this.resetDraft();

            window.dispatchEvent(new CustomEvent('crud-save-open-modal-draft'));

            window.dispatchEvent(new CustomEvent('crud-toast', {
                detail: { message: response.data.message ?? 'Tipo actualizado correctamente.' },
            }));
        } catch (error) {
            this.managerError = error.response?.data?.message
                || Object.values(error.response?.data?.errors ?? {}).flat()[0]
                || error.message
                || 'No fue posible guardar el tipo.';
        } finally {
            this.managerSaving = false;
        }
    },

    async quickUpdateType(type, changes, event = null) {
        event?.preventDefault?.();
        event?.stopPropagation?.();

        if (this.managerSaving || ! type?.update_url) {
            return;
        }

        const previousType = { ...type };
        const nextType = {
            ...type,
            ...(Object.prototype.hasOwnProperty.call(changes, 'adds_value')
                ? { adds_value: Boolean(changes.adds_value) }
                : {}),
            ...(Object.prototype.hasOwnProperty.call(changes, 'status')
                ? { status: changes.status }
                : {}),
        };

        this.updateTypeInPlace(nextType);
        this.managerOpen = true;
        this.managerSaving = true;
        this.managerError = '';

        try {
            const response = await window.axios.patch(nextType.update_url, {
                company_id: this.companyId,
                name: nextType.name,
                status: nextType.status,
            }, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const savedType = (response.data.types ?? [])
                .find((currentType) => String(currentType.id) === String(nextType.id));

            if (savedType) {
                this.updateTypeInPlace(savedType);
            }

            this.managerOpen = true;

            window.dispatchEvent(new CustomEvent('crud-save-open-modal-draft'));

            window.dispatchEvent(new CustomEvent('crud-toast', {
                detail: { message: response.data.message ?? 'Tipo actualizado correctamente.' },
            }));
        } catch (error) {
            this.updateTypeInPlace(previousType);
            this.managerOpen = true;
            this.managerError = error.response?.data?.message
                || Object.values(error.response?.data?.errors ?? {}).flat()[0]
                || error.message
                || 'No fue posible actualizar el tipo.';
        } finally {
            this.managerSaving = false;
        }
    },

    updateTypeInPlace(nextType) {
        const index = this.types.findIndex((currentType) => String(currentType.id) === String(nextType.id));

        if (index === -1) {
            return;
        }

        this.types[index] = { ...this.types[index], ...nextType };
        this.types = [...this.types];

        this.normalizeTypeSelection();
    },

    normalizeTypeSelection() {
        if (! this.selectedType || this.selectedType.status !== 'active') {
            this.selectedTypeId = this.allowEmptySelection
                ? ''
                : (this.activeTypes.length > 0 ? String(this.activeTypes[0].id) : '');
        }

        this.previousTypeId = this.selectedTypeId;
        _selectedTypeId = this.selectedTypeId;

        selectEls.forEach((select) => {
            this._buildSelectOptions(select);
        });
    },

    async deleteType(type) {
        if (this.managerSaving) {
            return;
        }

        if (! type.can_delete) {
            this.managerError = `No puedes eliminar un tipo que ya tiene proveedores asociados.`;
            return;
        }

        if (! window.confirm(`¿Deseas eliminar este tipo de ${this.entityName}?`)) {
            return;
        }

        this.managerSaving = true;
        this.managerError = '';

        try {
            const response = await window.axios.delete(type.delete_url, {
                params: { company_id: this.companyId },
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            this.applyTypes(response.data.types ?? []);
            this.resetDraft();

            window.dispatchEvent(new CustomEvent('crud-save-open-modal-draft'));

            window.dispatchEvent(new CustomEvent('crud-toast', {
                detail: { message: response.data.message ?? 'Tipo eliminado correctamente.' },
            }));
        } catch (error) {
            this.managerError = error.response?.data?.message || error.message || 'No fue posible eliminar el tipo.';
        } finally {
            this.managerSaving = false;
        }
    },

    applyTypes(types) {
        this.types = types;
        this.normalizeTypeSelection();
    },


}; });

const normalizeIntegerAmount = (value) => {
    const raw = String(value ?? '').trim();

    if (raw === '') {
        return '';
    }

    if (/^\d+([.,]00)?$/.test(raw)) {
        return raw.replace(/[.,]00$/, '');
    }

    return raw.replace(/\D/g, '');
};

const formatIntegerAmount = (value) => {
    const normalized = normalizeIntegerAmount(value);

    if (normalized === '') {
        return '';
    }

    const amount = Number(normalized);

    if (Number.isNaN(amount)) {
        return '';
    }

    return new Intl.NumberFormat('es-CO', {
        maximumFractionDigits: 0,
    }).format(amount);
};

const countAmountDigits = (value) => String(value ?? '').replace(/\D/g, '').length;

const caretPositionFromDigits = (formattedValue, digitCount) => {
    if (digitCount <= 0) {
        return 0;
    }

    let digitsSeen = 0;

    for (let index = 0; index < formattedValue.length; index += 1) {
        if (/\d/.test(formattedValue[index])) {
            digitsSeen += 1;

            if (digitsSeen === digitCount) {
                return index + 1;
            }
        }
    }

    return formattedValue.length;
};

const syncFormattedMoneyInput = (input, preserveCaret = false) => {
    if (! input) {
        return;
    }

    const rawValue = input.value;
    const selectionStart = input.selectionStart ?? rawValue.length;
    const digitsBeforeCaret = countAmountDigits(rawValue.slice(0, selectionStart));
    const formattedValue = formatIntegerAmount(rawValue);

    input.value = formattedValue;

    if (preserveCaret) {
        const nextCaret = caretPositionFromDigits(formattedValue, digitsBeforeCaret);
        input.setSelectionRange(nextCaret, nextCaret);
    }
};

window.initializeAssetForms = (root = document) => {
    root.querySelectorAll('form[data-asset-form]').forEach((form) => {
        if (form.dataset.assetInitialized === 'true') {
            return;
        }

        form.dataset.assetInitialized = 'true';

        const moneyInputs = [...form.querySelectorAll('[data-currency-input]')];

        moneyInputs.forEach((input) => {
            syncFormattedMoneyInput(input);

            input.addEventListener('input', () => {
                syncFormattedMoneyInput(input, true);
            });

            input.addEventListener('blur', () => {
                syncFormattedMoneyInput(input);
            });
        });

        form.addEventListener('submit', () => {
            moneyInputs.forEach((input) => {
                input.value = normalizeIntegerAmount(input.value);
            });
        });
    });
};

window.initializeTransactionForms = (root = document) => {
    root.querySelectorAll('form[data-transaction-form]').forEach((form) => {
        if (form.dataset.transactionInitialized === 'true') {
            return;
        }

        form.dataset.transactionInitialized = 'true';

        const payloadNode = form.querySelector('[data-expense-payload]');
        const selectedNode = form.querySelector('[data-expense-selected]');

        if (! payloadNode || ! selectedNode) {
            return;
        }

        const payload = JSON.parse(payloadNode.textContent || '{}');
        const selected = JSON.parse(selectedNode.textContent || '{}');
        const projects = (payload.projects ?? []).map((item) => ({ ...item, id: String(item.id), company_id: String(item.company_id) }));
        const providers = (payload.providers ?? []).map((item) => ({ ...item, id: String(item.id), company_id: String(item.company_id) }));
        const products = (payload.products ?? []).map((item) => ({ ...item, id: String(item.id), company_id: String(item.company_id) }));
        let invoices = (payload.invoices ?? []).map((item) => ({
            ...item,
            id: String(item.id),
            company_id: String(item.company_id),
            project_id: String(item.project_id),
            provider_id: String(item.provider_id),
        }));

        const projectField = form.querySelector('[data-expense-project]') ?? form.querySelector('input[name="project_id"]');
        const providerField = form.querySelector('[data-transaction-provider]');
        const providerSearch = form.querySelector('[data-transaction-provider-search]');
        const providerMenu = form.querySelector('[data-transaction-provider-menu]');
        const invoiceField = form.querySelector('[data-transaction-invoice]');
        const invoiceSearch = form.querySelector('[data-transaction-invoice-search]');
        const invoiceMenu = form.querySelector('[data-transaction-invoice-menu]');
        const productField = form.querySelector('[data-transaction-product]');
        const productSearch = form.querySelector('[data-transaction-product-search]');
        const productMenu = form.querySelector('[data-transaction-product-menu]');
        const unitPriceField = form.querySelector('[data-transaction-unit-price]');
        const quantityField = form.querySelector('[data-transaction-quantity]');
        const totalPreviewEl = form.querySelector('[data-transaction-total-preview]');
        const providerClear = form.querySelector('[data-transaction-provider-clear]');
        const productClear = form.querySelector('[data-transaction-product-clear]');
        const invoiceClear = form.querySelector('[data-transaction-invoice-clear]');

        const state = {
            projectId: selected.project_id ? String(selected.project_id) : '',
            providerId: selected.provider_id ? String(selected.provider_id) : '',
            invoiceId: selected.invoice_id ? String(selected.invoice_id) : '',
            productId: selected.product_id ? String(selected.product_id) : '',
        };

        if (! state.projectId && projectField?.value) {
            state.projectId = String(projectField.value);
        }

        const currentProject = () => projects.find((project) => project.id === state.projectId) ?? null;
        const optionLabel = (item) => item.name;
        const availableForProject = (items) => {
            const project = currentProject();

            if (project) {
                return items.filter((item) => item.company_id === project.company_id);
            }

            const availableCompanyIds = new Set(projects.map((item) => item.company_id));

            return items.filter((item) => availableCompanyIds.has(item.company_id));
        };
        const availableInvoices = () => invoices.filter((invoice) => (
            invoice.project_id === state.projectId
            && invoice.type === form.dataset.transactionType
            && (! invoice.provider_id || invoice.provider_id === 'null' || invoice.provider_id === state.providerId)
        ));

        const closeMenu = (menu) => menu?.classList.add('hidden');
        const openMenu = (menu) => menu?.classList.remove('hidden');

        const renderSimpleMenu = ({ menu, items, emptyText, onSelect }) => {
            if (! menu) {
                return;
            }

            menu.innerHTML = '';

            if (items.length === 0) {
                const empty = document.createElement('div');
                empty.className = 'px-4 py-3 text-sm text-stone-500';
                empty.textContent = emptyText;
                menu.appendChild(empty);
                return;
            }

            items.forEach((item) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'block w-full rounded-xl px-4 py-3 text-left transition hover:bg-stone-100 focus:bg-stone-100 focus:outline-none';
                const name = document.createElement('span');
                name.className = 'block whitespace-nowrap text-sm font-medium text-stone-900';
                name.textContent = item.name;
                button.appendChild(name);
                button.addEventListener('mousedown', (event) => {
                    event.preventDefault();
                    onSelect(item);
                });
                menu.appendChild(button);
            });
        };

        const closeProductMenu = () => {
            productMenu?.classList.add('hidden');
        };

        const openProductMenu = () => {
            productMenu?.classList.remove('hidden');
        };

        const renderProductMenu = (items) => {
            if (! productMenu) {
                return;
            }

            productMenu.innerHTML = '';

            if (items.length === 0) {
                const empty = document.createElement('div');
                empty.className = 'px-4 py-3 text-sm text-stone-500';
                empty.textContent = 'Sin productos disponibles';
                productMenu.appendChild(empty);
                return;
            }

            items.forEach((item) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'block w-full rounded-xl px-4 py-3 text-left transition hover:bg-stone-100 focus:bg-stone-100 focus:outline-none';
                const name = document.createElement('span');
                name.className = 'block whitespace-nowrap text-sm font-medium text-stone-900';
                name.textContent = item.name;
                button.appendChild(name);

                if (item.subgroup_name) {
                    const subgroup = document.createElement('span');
                    subgroup.className = 'mt-0.5 block whitespace-nowrap text-xs text-stone-500';
                    subgroup.textContent = item.subgroup_name;
                    button.appendChild(subgroup);
                }
                button.addEventListener('mousedown', (event) => {
                    event.preventDefault();
                    state.productId = item.id;

                    if (productField) {
                        productField.value = item.id;
                    }

                    if (productSearch) {
                        productSearch.value = item.name;
                    }

                    updateClearButtons();
                    closeProductMenu();
                });
                productMenu.appendChild(button);
            });
        };

        const invoiceLabel = (invoice) => invoice?.label ?? 'Factura sin número';

        const syncSearch = (items, id, search, hidden) => {
            const selectedItem = items.find((item) => item.id === id);

            if (search) {
                search.value = selectedItem ? optionLabel(selectedItem) : '';
            }

            if (hidden) {
                hidden.value = selectedItem ? selectedItem.id : '';
            }
        };

        const resolveTypedValue = (items, search, hidden, key) => {
            const value = (search?.value ?? '').trim().toLocaleLowerCase();
            const match = items.find((item) => optionLabel(item).toLocaleLowerCase() === value);
            state[key] = match ? match.id : '';

            if (hidden) {
                hidden.value = state[key];
            }
        };

        const updateClearButtons = () => {
            providerClear?.classList.toggle('hidden', ! state.providerId);
            productClear?.classList.toggle('hidden', ! state.productId);
            invoiceClear?.classList.toggle('hidden', ! state.invoiceId);
        };

        const syncLists = () => {
            const availableProviders = availableForProject(providers);
            const availableProducts = availableForProject(products);

            if (! availableProviders.some((provider) => provider.id === state.providerId)) {
                state.providerId = '';
            }

            if (! availableProducts.some((product) => product.id === state.productId)) {
                state.productId = '';
            }

            if (! availableInvoices().some((invoice) => invoice.id === state.invoiceId)) {
                state.invoiceId = '';
            }

            syncSearch(availableProviders, state.providerId, providerSearch, providerField);
            syncSearch(availableInvoices().map((invoice) => ({ ...invoice, name: invoiceLabel(invoice) })), state.invoiceId, invoiceSearch, invoiceField);
            syncSearch(availableProducts, state.productId, productSearch, productField);
            renderSimpleMenu({
                menu: providerMenu,
                items: availableProviders,
                emptyText: 'Sin proveedores disponibles',
                onSelect: (item) => {
                    state.providerId = item.id;
                    if (providerField) {
                        providerField.value = item.id;
                    }
                    if (providerSearch) {
                        providerSearch.value = item.name;
                    }
                    closeMenu(providerMenu);
                },
            });
            renderProductMenu(availableProducts);
            updateClearButtons();
        };

        const renderInvoiceMenu = (items) => {
            const normalized = items.map((invoice) => ({ ...invoice, name: invoiceLabel(invoice) }));
            renderSimpleMenu({
                menu: invoiceMenu,
                items: [
                    { id: '', name: 'Sin factura' },
                    ...normalized,
                ],
                emptyText: 'Sin facturas disponibles',
                onSelect: (item) => {
                    state.invoiceId = item.id;
                    if (invoiceField) {
                        invoiceField.value = item.id;
                    }
                    if (invoiceSearch) {
                        invoiceSearch.value = item.name === 'Sin factura' ? '' : item.name;
                    }
                    updateClearButtons();
                    closeMenu(invoiceMenu);
                },
            });
        };

        const syncUnitPrice = (preserveCaret = false) => {
            if (! unitPriceField) {
                return;
            }

            const rawValue = unitPriceField.value;
            const selectionStart = unitPriceField.selectionStart ?? rawValue.length;
            const digitsBeforeCaret = countAmountDigits(rawValue.slice(0, selectionStart));
            const formattedValue = formatIntegerAmount(rawValue);

            unitPriceField.value = formattedValue;

            if (preserveCaret) {
                const nextCaret = caretPositionFromDigits(formattedValue, digitsBeforeCaret);
                unitPriceField.setSelectionRange(nextCaret, nextCaret);
            }
        };

        const updateTotalPreview = () => {
            if (! totalPreviewEl) {
                return;
            }

            const rawPrice = normalizeIntegerAmount(unitPriceField?.value ?? '');
            const price = parseFloat(rawPrice) || 0;
            const qty = parseFloat(quantityField?.value ?? '') || 0;

            if (price > 0 && qty > 0 && qty !== 1) {
                const total = Math.round(price * qty);
                const formattedQty = Number.isInteger(qty)
                    ? String(qty)
                    : qty.toLocaleString('es-CO', { maximumFractionDigits: 2 });
                const formattedPrice = `$ ${formatIntegerAmount(String(Math.round(price)))}`;
                const formattedTotal = `$ ${formatIntegerAmount(String(total))}`;
                totalPreviewEl.innerHTML = `<span style="display:block;font-size:0.7rem;line-height:1rem;color:#a8a29e;margin-bottom:1px">${formattedQty} &times; ${formattedPrice}</span><span style="display:block;font-size:1.25rem;line-height:1.5rem;font-weight:700;color:#1c1917;letter-spacing:-0.01em">${formattedTotal}</span>`;
                totalPreviewEl.classList.remove('hidden');
            } else {
                totalPreviewEl.classList.add('hidden');
            }
        };

        if (! state.projectId && projects.length === 1) {
            state.projectId = projects[0].id;
        }

        if (projectField) {
            projectField.value = state.projectId;
            projectField.addEventListener('change', (event) => {
                state.projectId = String(event.target.value || '');
                state.providerId = '';
                state.invoiceId = '';
                state.productId = '';
                syncLists();
            });
        }

        providerSearch?.addEventListener('focus', () => {
            const availableProviders = availableForProject(providers);
            const term = providerSearch.value.trim().toLocaleLowerCase();
            renderSimpleMenu({
                menu: providerMenu,
                items: availableProviders.filter((item) => item.name.toLocaleLowerCase().includes(term)),
                emptyText: 'Sin proveedores disponibles',
                onSelect: (item) => {
                    state.providerId = item.id;
                    state.invoiceId = '';
                    if (providerField) {
                        providerField.value = item.id;
                    }
                    if (providerSearch) {
                        providerSearch.value = item.name;
                    }
                    updateClearButtons();
                    closeMenu(providerMenu);
                },
            });
            openMenu(providerMenu);
        });
        providerSearch?.addEventListener('input', () => {
            const availableProviders = availableForProject(providers);
            const term = providerSearch.value.trim().toLocaleLowerCase();
            resolveTypedValue(availableProviders, providerSearch, providerField, 'providerId');
            renderSimpleMenu({
                menu: providerMenu,
                items: availableProviders.filter((item) => item.name.toLocaleLowerCase().includes(term)),
                emptyText: 'Sin proveedores disponibles',
                onSelect: (item) => {
                    state.providerId = item.id;
                    state.invoiceId = '';
                    if (providerField) {
                        providerField.value = item.id;
                    }
                    if (providerSearch) {
                        providerSearch.value = item.name;
                    }
                    updateClearButtons();
                    closeMenu(providerMenu);
                },
            });
            openMenu(providerMenu);
        });
        providerSearch?.addEventListener('blur', () => {
            window.setTimeout(() => {
                syncSearch(availableForProject(providers), state.providerId, providerSearch, providerField);
                closeMenu(providerMenu);
            }, 120);
        });
        invoiceSearch?.addEventListener('focus', () => {
            const term = invoiceSearch.value.trim().toLocaleLowerCase();
            renderInvoiceMenu(availableInvoices().filter((item) => invoiceLabel(item).toLocaleLowerCase().includes(term)));
            openMenu(invoiceMenu);
        });
        invoiceSearch?.addEventListener('input', () => {
            const term = invoiceSearch.value.trim().toLocaleLowerCase();
            const normalized = availableInvoices().map((invoice) => ({ ...invoice, name: invoiceLabel(invoice) }));
            resolveTypedValue(normalized, invoiceSearch, invoiceField, 'invoiceId');
            renderInvoiceMenu(availableInvoices().filter((item) => invoiceLabel(item).toLocaleLowerCase().includes(term)));
            openMenu(invoiceMenu);
        });
        invoiceSearch?.addEventListener('blur', () => {
            window.setTimeout(() => {
                syncSearch(availableInvoices().map((invoice) => ({ ...invoice, name: invoiceLabel(invoice) })), state.invoiceId, invoiceSearch, invoiceField);
                closeMenu(invoiceMenu);
            }, 120);
        });
        productSearch?.addEventListener('focus', () => {
            const availableProducts = availableForProject(products);
            const term = productSearch.value.trim().toLocaleLowerCase();
            renderProductMenu(availableProducts.filter((item) => (
                item.name.toLocaleLowerCase().includes(term)
                || (item.subgroup_name ?? '').toLocaleLowerCase().includes(term)
            )));
            openProductMenu();
        });
        productSearch?.addEventListener('input', () => {
            const availableProducts = availableForProject(products);
            const term = productSearch.value.trim().toLocaleLowerCase();
            const filteredProducts = availableProducts.filter((item) => (
                item.name.toLocaleLowerCase().includes(term)
                || (item.subgroup_name ?? '').toLocaleLowerCase().includes(term)
            ));

            resolveTypedValue(availableProducts, productSearch, productField, 'productId');
            renderProductMenu(filteredProducts);
            openProductMenu();
        });
        productSearch?.addEventListener('blur', () => {
            window.setTimeout(() => {
                syncSearch(availableForProject(products), state.productId, productSearch, productField);
                closeProductMenu();
            }, 120);
        });

        providerClear?.addEventListener('click', () => {
            state.providerId = '';
            state.invoiceId = '';
            if (providerField) providerField.value = '';
            if (providerSearch) providerSearch.value = '';
            if (invoiceField) invoiceField.value = '';
            if (invoiceSearch) invoiceSearch.value = '';
            syncLists();
        });

        productClear?.addEventListener('click', () => {
            state.productId = '';
            if (productField) productField.value = '';
            if (productSearch) productSearch.value = '';
            syncLists();
        });

        invoiceClear?.addEventListener('click', () => {
            state.invoiceId = '';
            if (invoiceField) invoiceField.value = '';
            if (invoiceSearch) invoiceSearch.value = '';
            syncLists();
        });

        if (unitPriceField) {
            unitPriceField.addEventListener('input', () => syncUnitPrice(true));
            unitPriceField.addEventListener('blur', () => syncUnitPrice());
            unitPriceField.addEventListener('input', updateTotalPreview);
        }

        if (quantityField) {
            quantityField.addEventListener('input', updateTotalPreview);
            quantityField.addEventListener('blur', updateTotalPreview);
        }

        form.addEventListener('submit', () => {
            if (unitPriceField) {
                unitPriceField.value = normalizeIntegerAmount(unitPriceField.value);
            }
        });

        form.addEventListener('invoice-added', (event) => {
            const invoice = event.detail ?? {};
            if (! invoice.id) return;
            const normalized = {
                ...invoice,
                id: String(invoice.id),
                company_id: String(invoice.company_id ?? ''),
                project_id: String(invoice.project_id ?? ''),
                provider_id: String(invoice.provider_id ?? ''),
            };
            invoices = invoices.filter((inv) => inv.id !== normalized.id);
            invoices.push(normalized);
            state.invoiceId = normalized.id;
            if (invoiceField) invoiceField.value = normalized.id;
            if (invoiceSearch) invoiceSearch.value = invoiceLabel(normalized);
            syncLists();
        });

        syncLists();
        syncUnitPrice();
        updateTotalPreview();
    });
};

window.initializeStandaloneInvoiceForms = (root = document) => {
    root.querySelectorAll('form[data-standalone-invoice-form]').forEach((form) => {
        if (form.dataset.standaloneInvoiceInitialized === 'true') return;
        form.dataset.standaloneInvoiceInitialized = 'true';

        const invoiceType = form.dataset.invoiceType ?? 'expense';
        const storeUrl = form.dataset.invoiceStoreUrl ?? '';

        const projectsNode = form.querySelector('[data-invoice-projects]');
        const providersNode = form.querySelector('[data-invoice-providers]');
        const allProjects = projectsNode ? JSON.parse(projectsNode.textContent || '[]').map((p) => ({ ...p, id: String(p.id), company_id: String(p.company_id) })) : [];
        const allProviders = providersNode ? JSON.parse(providersNode.textContent || '[]').map((p) => ({ ...p, id: String(p.id), company_id: String(p.company_id) })) : [];

        const projectField = form.querySelector('[data-invoice-project]');
        const providerSearchEl = form.querySelector('[data-invoice-provider-search]');
        const providerField = form.querySelector('[data-invoice-provider]');
        const providerMenu = form.querySelector('[data-invoice-provider-menu]');
        const numberField = form.querySelector('[data-invoice-number]');
        const dateField = form.querySelector('[data-invoice-date]');
        const descriptionField = form.querySelector('[data-invoice-description]');
        const saveButton = form.querySelector('[data-invoice-save]');

        const state = { projectId: '', providerId: '' };

        const showError = (field, message) => {
            const el = form.querySelector(`[data-invoice-error-for="${field}"]`);
            if (el) { el.textContent = message; el.classList.remove('hidden'); }
        };
        const clearErrors = () => {
            form.querySelectorAll('[data-invoice-error-for]').forEach((el) => { el.textContent = ''; el.classList.add('hidden'); });
        };

        const availableProviders = () => {
            if (state.projectId) {
                const project = allProjects.find((p) => p.id === state.projectId);
                if (project) return allProviders.filter((p) => p.company_id === project.company_id);
            }
            const companyIds = new Set(allProjects.map((p) => p.company_id));
            return allProviders.filter((p) => companyIds.has(p.company_id));
        };

        const renderProviderMenu = (items) => {
            if (! providerMenu) return;
            providerMenu.innerHTML = '';
            if (items.length === 0) {
                const empty = document.createElement('div');
                empty.className = 'px-4 py-3 text-sm text-stone-500';
                empty.textContent = 'Sin proveedores disponibles';
                providerMenu.appendChild(empty);
                return;
            }
            items.forEach((item) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'block w-full rounded-xl px-4 py-3 text-left transition hover:bg-stone-100 focus:bg-stone-100 focus:outline-none';
                const name = document.createElement('span');
                name.className = 'block whitespace-nowrap text-sm font-medium text-stone-900';
                name.textContent = item.name;
                button.appendChild(name);
                button.addEventListener('mousedown', (event) => {
                    event.preventDefault();
                    state.providerId = item.id;
                    if (providerField) providerField.value = item.id;
                    if (providerSearchEl) providerSearchEl.value = item.name;
                    providerMenu.classList.add('hidden');
                });
                providerMenu.appendChild(button);
            });
        };

        providerSearchEl?.addEventListener('focus', () => {
            const term = (providerSearchEl.value ?? '').trim().toLocaleLowerCase();
            renderProviderMenu(availableProviders().filter((p) => p.name.toLocaleLowerCase().includes(term)));
            providerMenu?.classList.remove('hidden');
        });
        providerSearchEl?.addEventListener('input', () => {
            const term = (providerSearchEl.value ?? '').trim().toLocaleLowerCase();
            const match = availableProviders().find((p) => p.name.toLocaleLowerCase() === term);
            state.providerId = match ? match.id : '';
            if (providerField) providerField.value = state.providerId;
            renderProviderMenu(availableProviders().filter((p) => p.name.toLocaleLowerCase().includes(term)));
            providerMenu?.classList.remove('hidden');
        });
        providerSearchEl?.addEventListener('blur', () => {
            window.setTimeout(() => {
                const match = availableProviders().find((p) => p.id === state.providerId);
                if (providerSearchEl) providerSearchEl.value = match ? match.name : '';
                if (providerField) providerField.value = state.providerId;
                providerMenu?.classList.add('hidden');
            }, 120);
        });

        projectField?.addEventListener('change', (event) => {
            state.projectId = String(event.target.value || '');
            state.providerId = '';
            if (providerField) providerField.value = '';
            if (providerSearchEl) providerSearchEl.value = '';
        });

        saveButton?.addEventListener('click', async () => {
            clearErrors();
            saveButton.disabled = true;

            try {
                const data = {
                    type: invoiceType,
                    project_id: projectField?.value || '',
                    provider_id: providerField?.value || '',
                    invoice_number: numberField?.value || '',
                    invoice_date: dateField?.value || '',
                    description: descriptionField?.value || '',
                };

                const response = await window.axios.post(storeUrl, data, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });

                if (form.dataset.invoiceRedirect === 'true' && response.data.redirect_url) {
                    window.location.assign(response.data.redirect_url);
                    return;
                }

                window.dispatchEvent(new CustomEvent('crud-toast', {
                    detail: { message: response.data.message ?? 'Factura creada correctamente.' },
                }));
                window.dispatchEvent(new CustomEvent('close-ajax-modal'));
            } catch (error) {
                const errors = error.response?.data?.errors ?? {};
                const fallback = error.response?.data?.message || 'No fue posible crear la factura.';
                if (Object.keys(errors).length > 0) {
                    Object.entries(errors).forEach(([field, messages]) => {
                        showError(field, Array.isArray(messages) ? messages[0] : String(messages));
                    });
                } else {
                    showError('project_id', fallback);
                }
            } finally {
                saveButton.disabled = false;
            }
        });
    });
};

window.initializeInvoiceShowForms = (root = document) => {
    // Auto-save simple fields (invoice_number, invoice_date, project_id)
    root.querySelectorAll('[data-invoice-show-field]').forEach((input) => {
        if (input.dataset.invoiceShowFieldInitialized === 'true') return;
        input.dataset.invoiceShowFieldInitialized = 'true';

        const field = input.dataset.invoiceShowField;
        const url = input.dataset.invoiceSaveUrl;
        if (!url) return;

        let lastValue = input.value;

        const save = async () => {
            const current = input.value;
            if (current === lastValue) return;
            lastValue = current;
            try {
                await window.axios.patch(url, { [field]: current || null }, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                window.dispatchEvent(new CustomEvent('crud-toast', { detail: { message: 'Guardado correctamente.' } }));
            } catch {
                window.dispatchEvent(new CustomEvent('crud-toast', { detail: { message: 'No fue posible guardar.', type: 'error' } }));
            }
        };

        if (input.tagName === 'SELECT') {
            input.addEventListener('change', save);
        } else {
            input.addEventListener('blur', save);
        }
    });

    // Provider combobox
    const provWidget = root.querySelector('[data-invoice-show-providers-root]')
        ?? (root === document ? document.querySelector('[data-invoice-show-providers-root]') : null);

    if (!provWidget || provWidget.dataset.invoiceShowProviderInitialized === 'true') return;
    provWidget.dataset.invoiceShowProviderInitialized = 'true';

    const providersNode = provWidget.querySelector('[data-invoice-show-providers]');
    const allProviders = providersNode
        ? JSON.parse(providersNode.textContent || '[]').map((p) => ({ ...p, id: String(p.id) }))
        : [];

    const providerSearchEl = provWidget.querySelector('[data-invoice-show-provider-search]');
    const providerField = provWidget.querySelector('[data-invoice-show-provider]');
    const providerMenu = provWidget.querySelector('[data-invoice-show-provider-menu]');
    const providerClear = provWidget.querySelector('[data-invoice-show-provider-clear]');
    const saveUrl = provWidget.dataset.invoiceSaveUrl;

    if (!providerSearchEl || !providerField || !saveUrl) return;

    let providerId = String(providerField.value ?? '');
    let lastProviderId = providerId;

    const patchProvider = async (value) => {
        if (value === lastProviderId) return;
        lastProviderId = value;
        try {
            await window.axios.patch(saveUrl, { provider_id: value || null }, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            window.dispatchEvent(new CustomEvent('crud-toast', { detail: { message: 'Proveedor actualizado.' } }));
        } catch {
            window.dispatchEvent(new CustomEvent('crud-toast', { detail: { message: 'No fue posible guardar el proveedor.', type: 'error' } }));
        }
    };

    const renderProviderMenu = (items) => {
        if (!providerMenu) return;
        providerMenu.innerHTML = '';
        if (items.length === 0) {
            const empty = document.createElement('div');
            empty.className = 'px-4 py-3 text-sm text-stone-400';
            empty.textContent = 'Sin proveedores disponibles';
            providerMenu.appendChild(empty);
            return;
        }
        items.forEach((item) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'block w-full rounded-xl px-4 py-2.5 text-left text-sm font-medium text-stone-900 transition hover:bg-stone-100 focus:bg-stone-100 focus:outline-none';
            btn.textContent = item.name;
            btn.addEventListener('mousedown', (e) => {
                e.preventDefault();
                providerId = item.id;
                if (providerField) providerField.value = item.id;
                if (providerSearchEl) providerSearchEl.value = item.name;
                if (providerMenu) providerMenu.classList.add('hidden');
                if (providerClear) providerClear.classList.remove('hidden');
                patchProvider(item.id);
            });
            providerMenu.appendChild(btn);
        });
    };

    providerSearchEl.addEventListener('focus', () => {
        const term = providerSearchEl.value.trim().toLocaleLowerCase();
        renderProviderMenu(allProviders.filter((p) => p.name.toLocaleLowerCase().includes(term)));
        providerMenu?.classList.remove('hidden');
    });
    providerSearchEl.addEventListener('input', () => {
        const term = providerSearchEl.value.trim().toLocaleLowerCase();
        const match = allProviders.find((p) => p.name.toLocaleLowerCase() === term);
        providerId = match ? match.id : '';
        if (providerField) providerField.value = providerId;
        renderProviderMenu(allProviders.filter((p) => p.name.toLocaleLowerCase().includes(term)));
        providerMenu?.classList.remove('hidden');
    });
    providerSearchEl.addEventListener('blur', () => {
        window.setTimeout(() => {
            const match = allProviders.find((p) => p.id === providerId);
            providerSearchEl.value = match ? match.name : '';
            if (providerField) providerField.value = providerId;
            if (providerMenu) providerMenu.classList.add('hidden');
            patchProvider(providerId);
        }, 120);
    });
    providerClear?.addEventListener('click', () => {
        providerId = '';
        if (providerField) providerField.value = '';
        if (providerSearchEl) providerSearchEl.value = '';
        if (providerClear) providerClear.classList.add('hidden');
        patchProvider('');
    });
};

window.initializeInvoiceInlineRows = () => {
    const addBtn = document.querySelector('[data-invoice-add-row]');
    const saveAllBtn = document.querySelector('[data-invoice-save-all]');
    const tbody = document.querySelector('[data-invoice-tbody]');
    const productsNode = document.querySelector('[data-invoice-products]');

    if (!tbody || !tbody.hasAttribute('data-invoice-editable')) return;

    const storeUrl = addBtn?.dataset.storeUrl;
    const transactionType = addBtn?.dataset.transactionType ?? 'expense';
    const projectId = addBtn?.dataset.projectId;
    const providerId = addBtn?.dataset.providerId;
    const invoiceId = addBtn?.dataset.invoiceId;

    const products = productsNode
        ? JSON.parse(productsNode.textContent || '[]').map((p) => ({ ...p, id: String(p.id) }))
        : [];

    const parsePrice = (str) => parseInt(String(str ?? '').trim().replace(/\./g, '').replace(/[^0-9]/g, ''), 10) || 0;
    const parseQty = (str) => parseInt(String(str ?? '').trim().replace(/\./g, '').replace(/[^0-9]/g, ''), 10) || 0;
    const formatPrice = (num) => new Intl.NumberFormat('es-CO', { maximumFractionDigits: 0 }).format(num || 0);

    const fieldLabels = { invoice_id: 'factura', product_id: 'producto', provider_id: 'proveedor', project_id: 'proyecto', unit_price: 'valor unitario', quantity: 'cantidad', expense_date: 'fecha', purchase_date: 'fecha' };
    const humanizeError = (msg) => {
        if (!msg) return 'No fue posible guardar.';
        return msg
            .replace(/The selected ([\w_]+) is invalid\./i, (_, f) => `El campo "${fieldLabels[f] ?? f.replace(/_/g, ' ')}" no es válido o no está disponible.`)
            .replace(/The ([\w_]+) field is required\./i, (_, f) => `El campo "${fieldLabels[f] ?? f.replace(/_/g, ' ')}" es obligatorio.`)
            .replace(/The ([\w_]+) must be a number\./i, (_, f) => `El campo "${fieldLabels[f] ?? f.replace(/_/g, ' ')}" debe ser un número.`)
            .replace(/The ([\w_]+) must be at least ([\d.]+)\./i, (_, f, v) => `El campo "${fieldLabels[f] ?? f.replace(/_/g, ' ')}" debe ser al menos ${v}.`);
    };

    const updateInvoiceTotal = () => {
        const totalEl = document.querySelector('[data-invoice-total-amount]');
        if (!totalEl) return;
        let sum = 0;
        tbody.querySelectorAll('tr[data-item-id], tr[data-inline-row]').forEach((tr) => {
            const qty = parseQty(tr.querySelector('[data-item-quantity]')?.value) || 1;
            const price = parsePrice(tr.querySelector('[data-item-unit-price]')?.value);
            sum += qty * price;
        });
        totalEl.textContent = '$ ' + formatPrice(sum);
    };

    const trashSvg = `<svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.257 3.099c.366-.446.911-.699 1.486-.699h.514c.575 0 1.12.253 1.486.699L12.85 4H16a1 1 0 110 2h-1l-.867 10.142A2 2 0 0112.14 18H7.86a2 2 0 01-1.993-1.858L5 6H4a1 1 0 010-2h3.15l1.107-.901zM8 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm4-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>`;

    const getUsedProductIds = (excludeTr = null) => {
        const ids = new Set();
        tbody.querySelectorAll('tr[data-item-id], tr[data-inline-row]').forEach((tr) => {
            if (tr === excludeTr) return;
            const val = tr.querySelector('[data-item-product]')?.value;
            if (val) ids.add(String(val));
        });
        return ids;
    };

    const filterProducts = (term, excludeTr = null) => {
        const used = getUsedProductIds(excludeTr);
        return products.filter((p) =>
            !used.has(p.id) &&
            (p.name.toLocaleLowerCase().includes(term) ||
            (p.subgroup_name ?? '').toLocaleLowerCase().includes(term))
        );
    };

    const buildProductMenu = (productMenu, items, onSelect) => {
        productMenu.innerHTML = '';
        if (items.length === 0) {
            const el = document.createElement('div');
            el.className = 'px-4 py-3 text-sm text-stone-400';
            el.textContent = 'Sin productos disponibles';
            productMenu.appendChild(el);
            productMenu.classList.remove('hidden');
            return;
        }
        items.forEach((item) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'block w-full rounded-xl px-4 py-2.5 text-left transition hover:bg-stone-100 focus:outline-none';
            btn.innerHTML = `<div class="text-sm font-medium text-stone-900">${item.name}</div>${item.subgroup_name ? `<div class="text-xs text-stone-400">${item.subgroup_name}</div>` : ''}`;
            btn.addEventListener('mousedown', (e) => {
                e.preventDefault();
                onSelect(item);
            });
            productMenu.appendChild(btn);
        });
        productMenu.classList.remove('hidden');
    };

    const initProductCombobox = (tr, onChange) => {
        const productSearch = tr.querySelector('[data-item-product-search]');
        const productField = tr.querySelector('[data-item-product]');
        const productMenu = tr.querySelector('[data-item-product-menu]');
        const subgroupEl = tr.querySelector('[data-item-subgroup]');

        if (!productSearch || !productField || !productMenu) return null;

        // Move menu to body so it escapes any overflow:hidden/auto ancestor
        document.body.appendChild(productMenu);
        productMenu.style.position = 'fixed';
        productMenu.style.zIndex = '9999';

        const positionMenu = () => {
            const rect = productSearch.getBoundingClientRect();
            productMenu.style.top = (rect.bottom + 2) + 'px';
            productMenu.style.left = rect.left + 'px';
            productMenu.style.minWidth = Math.max(rect.width, 288) + 'px';
        };

        let selectedProductId = String(productField.value || '');

        const applySelection = (item) => {
            selectedProductId = item.id;
            productField.value = item.id;
            productSearch.value = item.name;
            if (subgroupEl) {
                subgroupEl.textContent = item.subgroup_name || '';
                subgroupEl.classList.toggle('hidden', !item.subgroup_name);
            }
            productMenu.classList.add('hidden');
            onChange?.();
        };

        productSearch.addEventListener('focus', () => {
            positionMenu();
            buildProductMenu(productMenu, filterProducts(productSearch.value.trim().toLocaleLowerCase(), tr), applySelection);
        });
        productSearch.addEventListener('input', () => {
            const term = productSearch.value.trim().toLocaleLowerCase();
            const match = products.find((p) => p.name.toLocaleLowerCase() === term && !getUsedProductIds(tr).has(p.id));
            selectedProductId = match ? match.id : '';
            productField.value = selectedProductId;
            positionMenu();
            buildProductMenu(productMenu, filterProducts(term, tr), applySelection);
            onChange?.();
        });
        productSearch.addEventListener('blur', () => {
            window.setTimeout(() => {
                const match = products.find((p) => p.id === selectedProductId);
                productSearch.value = match ? match.name : '';
                if (subgroupEl && !match) {
                    subgroupEl.textContent = '';
                    subgroupEl.classList.add('hidden');
                }
                productField.value = selectedProductId;
                productMenu.classList.add('hidden');
                onChange?.();
            }, 120);
        });

        return { getSelectedId: () => selectedProductId };
    };

    // Initialize existing rows
    tbody.querySelectorAll('tr[data-item-id]').forEach((tr) => {
        if (tr.dataset.rowInitialized === 'true') return;
        tr.dataset.rowInitialized = 'true';

        const deleteUrl = tr.dataset.itemDeleteUrl;
        const updateUrl = tr.dataset.itemUpdateUrl;
        const itemDate = tr.dataset.itemDate;

        let dirty = false;
        const markDirty = () => { dirty = true; };

        const combobox = initProductCombobox(tr, markDirty);
        const quantityInput = tr.querySelector('[data-item-quantity]');
        const unitPriceInput = tr.querySelector('[data-item-unit-price]');
        const totalEl = tr.querySelector('[data-item-total]');
        const deleteBtn = tr.querySelector('[data-item-delete]');

        // Normalize initial values from DB (may come as raw decimals)
        if (quantityInput?.value) { const v = parseQty(quantityInput.value); quantityInput.value = v > 0 ? formatPrice(v) : ''; }
        if (unitPriceInput?.value) { const v = parsePrice(unitPriceInput.value); unitPriceInput.value = v > 0 ? formatPrice(v) : ''; }

        const updateTotal = () => {
            const qty = parseQty(quantityInput?.value) || 1;
            const price = parsePrice(unitPriceInput?.value);
            if (totalEl) totalEl.textContent = price > 0 ? '$ ' + formatPrice(qty * price) : '—';
            updateInvoiceTotal();
        };

        quantityInput?.addEventListener('input', () => {
            const v = parseQty(quantityInput.value);
            quantityInput.value = v > 0 ? formatPrice(v) : '';
            updateTotal(); markDirty();
        });
        unitPriceInput?.addEventListener('input', () => {
            const v = parsePrice(unitPriceInput.value);
            unitPriceInput.value = v > 0 ? formatPrice(v) : '';
            updateTotal(); markDirty();
        });

        deleteBtn?.addEventListener('click', async () => {
            if (!confirm('¿Deseas archivar este ítem? Esta acción no se puede deshacer.')) return;
            deleteBtn.disabled = true;
            try {
                await window.axios.delete(deleteUrl, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                window.location.assign(window.location.href);
            } catch {
                deleteBtn.disabled = false;
                window.dispatchEvent(new CustomEvent('crud-toast', {
                    detail: { message: 'No fue posible archivar el ítem.', type: 'error' },
                }));
            }
        });

        tr._invoiceRow = {
            isNew: false,
            isDirty: () => dirty,
            getProductId: () => combobox?.getSelectedId() ?? '',
            getQuantity: () => { const v = parseQty(quantityInput?.value); return v > 0 ? v : null; },
            getUnitPrice: () => parsePrice(unitPriceInput?.value),
            getUpdateUrl: () => updateUrl,
            getDate: () => itemDate,
        };
    });

    // Build a new inline row
    const buildNewRow = () => {
        const tr = document.createElement('tr');
        tr.dataset.inlineRow = 'true';
        tr.className = 'border-b border-sky-100 bg-sky-50/40';
        tr.innerHTML = `
            <td class="px-6 py-2.5 sm:px-8">
                <div class="relative">
                    <input type="text" data-item-product-search placeholder="Buscar producto..." autocomplete="off"
                        class="block w-full min-w-[160px] rounded-xl border-stone-300 text-sm shadow-sm focus:border-stone-900 focus:ring-stone-900">
                    <input type="hidden" data-item-product value="">
                    <div data-item-product-menu class="hidden max-h-52 overflow-y-auto rounded-xl border border-stone-200 bg-white p-1 shadow-xl"></div>
                </div>
                <div class="mt-0.5 hidden text-xs text-stone-400" data-item-subgroup></div>
            </td>
            <td class="px-4 py-2.5">
                <input type="text" inputmode="numeric" data-item-quantity placeholder="1"
                    class="block w-20 rounded-xl border-stone-300 text-right text-sm shadow-sm focus:border-stone-900 focus:ring-stone-900">
            </td>
            <td class="px-4 py-2.5">
                <input type="text" data-item-unit-price inputmode="decimal" placeholder="0"
                    class="block w-28 rounded-xl border-stone-300 text-right text-sm shadow-sm focus:border-stone-900 focus:ring-stone-900">
            </td>
            <td class="whitespace-nowrap px-4 py-2.5 text-right text-sm font-semibold text-stone-700" data-item-total>—</td>
            <td class="py-2.5 pl-8 pr-4 sm:pr-6">
                <button type="button" data-item-delete
                    class="rounded-xl border border-rose-200 p-1.5 text-rose-600 transition hover:bg-rose-50"
                    title="Eliminar fila">${trashSvg}</button>
            </td>`;

        tbody.appendChild(tr);

        const quantityInput = tr.querySelector('[data-item-quantity]');
        const unitPriceInput = tr.querySelector('[data-item-unit-price]');
        const totalEl = tr.querySelector('[data-item-total]');
        const deleteBtn = tr.querySelector('[data-item-delete]');

        const updateTotal = () => {
            const qty = parseQty(quantityInput.value) || 1;
            const price = parsePrice(unitPriceInput.value);
            totalEl.textContent = price > 0 ? '$ ' + formatPrice(qty * price) : '—';
            updateInvoiceTotal();
        };

        const combobox = initProductCombobox(tr, null);

        quantityInput.addEventListener('input', () => {
            const v = parseQty(quantityInput.value);
            quantityInput.value = v > 0 ? formatPrice(v) : '';
            updateTotal();
        });
        unitPriceInput.addEventListener('input', () => {
            const v = parsePrice(unitPriceInput.value);
            unitPriceInput.value = v > 0 ? formatPrice(v) : '';
            updateTotal();
        });
        deleteBtn.addEventListener('click', () => { tr.remove(); updateInvoiceTotal(); });

        tr._invoiceRow = {
            isNew: true,
            isDirty: () => true,
            getProductId: () => combobox?.getSelectedId() ?? '',
            getQuantity: () => { const v = parseQty(quantityInput.value); return v > 0 ? v : null; },
            getUnitPrice: () => parsePrice(unitPriceInput.value),
        };

        tr.querySelector('[data-item-product-search]')?.focus();
    };

    if (addBtn && addBtn.dataset.inlineInitialized !== 'true') {
        addBtn.dataset.inlineInitialized = 'true';
        addBtn.addEventListener('click', buildNewRow);
    }

    if (saveAllBtn && saveAllBtn.dataset.inlineInitialized !== 'true') {
        saveAllBtn.dataset.inlineInitialized = 'true';

        saveAllBtn.addEventListener('click', async () => {
            const rows = [...tbody.querySelectorAll('tr[data-item-id], tr[data-inline-row]')]
                .filter((tr) => tr._invoiceRow);

            const partialNewRows = rows.filter((tr) => {
                const r = tr._invoiceRow;
                if (!r.isNew) return false;
                const hasProduct = Boolean(r.getProductId());
                const hasPrice = r.getUnitPrice() > 0;
                const hasQty = r.getQuantity() !== null;
                const hasAny = hasProduct || hasPrice || hasQty;
                return hasAny && !(hasProduct && hasPrice);
            });

            if (partialNewRows.length > 0) {
                window.dispatchEvent(new CustomEvent('crud-toast', {
                    detail: { message: 'Hay filas incompletas: selecciona producto y valor unitario.', type: 'error' },
                }));
                return;
            }

            saveAllBtn.disabled = true;

            try {
                const dateKey = transactionType === 'purchase' ? 'purchase_date' : 'expense_date';
                const today = new Date().toISOString().split('T')[0];
                const promises = [];

                for (const tr of rows) {
                    const r = tr._invoiceRow;
                    if (r.isNew) {
                        const productId = r.getProductId();
                        const unitPrice = r.getUnitPrice();
                        if (!productId || unitPrice <= 0 || !storeUrl) continue;
                        promises.push(
                            window.axios.post(storeUrl, {
                                project_id: projectId,
                                provider_id: providerId || null,
                                invoice_id: invoiceId,
                                product_id: productId,
                                unit_price: unitPrice,
                                quantity: r.getQuantity(),
                                [dateKey]: today,
                            }, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                        );
                    } else if (r.isDirty()) {
                        promises.push(
                            window.axios.patch(r.getUpdateUrl(), {
                                project_id: projectId,
                                provider_id: providerId || null,
                                invoice_id: invoiceId,
                                product_id: r.getProductId() || null,
                                unit_price: r.getUnitPrice(),
                                quantity: r.getQuantity(),
                                [dateKey]: r.getDate() || today,
                            }, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                        );
                    }
                }

                if (promises.length === 0) {
                    window.dispatchEvent(new CustomEvent('crud-toast', {
                        detail: { message: 'No hay cambios para guardar.' },
                    }));
                    saveAllBtn.disabled = false;
                    return;
                }

                await Promise.all(promises);
                window.dispatchEvent(new CustomEvent('crud-toast', {
                    detail: { message: 'Factura guardada correctamente.' },
                }));
                window.location.assign(window.location.href);
            } catch (error) {
                saveAllBtn.disabled = false;
                const raw = Object.values(error.response?.data?.errors ?? {}).flat()[0]
                    || error.response?.data?.message;
                window.dispatchEvent(new CustomEvent('crud-toast', { detail: { message: humanizeError(raw), type: 'error' } }));
            }
        });
    }
};

window.initializeCompanyLogoForms = (root = document) => {
    root.querySelectorAll('[data-company-logo-upload]').forEach((widget) => {
        if (widget.dataset.companyLogoInitialized === 'true') return;
        widget.dataset.companyLogoInitialized = 'true';

        const input = widget.querySelector('[data-company-logo-input]');
        const preview = widget.querySelector('[data-company-logo-preview]');
        const errorEl = widget.querySelector('[data-company-logo-error]');
        const uploadUrl = input?.dataset.uploadUrl;
        const label = input ? widget.querySelector(`label[for="${input.id}"]`) : null;

        if (! input || ! uploadUrl) return;

        input.addEventListener('change', async () => {
            const file = input.files?.[0];
            if (! file) return;

            const formData = new FormData();
            formData.append('logo', file);

            if (errorEl) { errorEl.textContent = ''; errorEl.classList.add('hidden'); }
            if (label) label.classList.add('pointer-events-none', 'opacity-60');

            try {
                const response = await window.axios.post(uploadUrl, formData, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'multipart/form-data',
                    },
                });

                if (preview) {
                    const img = document.createElement('img');
                    img.src = response.data.logo_url + '?t=' + Date.now();
                    img.alt = 'Logo';
                    img.className = 'h-full w-full object-contain';
                    preview.innerHTML = '';
                    preview.appendChild(img);
                }

                window.dispatchEvent(new CustomEvent('crud-toast', {
                    detail: { message: response.data.message ?? 'Logo actualizado correctamente.' },
                }));
            } catch (error) {
                const message = Object.values(error.response?.data?.errors ?? {}).flat()[0]
                    || error.response?.data?.message
                    || 'No fue posible cargar el logo.';
                if (errorEl) { errorEl.textContent = message; errorEl.classList.remove('hidden'); }
                window.dispatchEvent(new CustomEvent('crud-toast', { detail: { message, type: 'error' } }));
            } finally {
                if (label) label.classList.remove('pointer-events-none', 'opacity-60');
                input.value = '';
            }
        });
    });
};

window.initializeInvoiceAttachmentForms = (root = document) => {
    root.querySelectorAll('form[data-invoice-attachment-form]').forEach((form) => {
        if (form.dataset.invoiceAttachmentInitialized === 'true') {
            return;
        }

        form.dataset.invoiceAttachmentInitialized = 'true';

        form.querySelector('[data-invoice-file-input]')?.addEventListener('change', () => {
            if (form.querySelector('[data-invoice-file-input]')?.files?.length) {
                form.requestSubmit();
            }
        });

        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            const button = form.querySelector('button[type="submit"]');
            const uploadTrigger = form.querySelector('label[for]');
            const originalText = button?.textContent;
            const data = new FormData(form);

            if (button) {
                button.disabled = true;
                button.textContent = 'Subiendo...';
            }
            uploadTrigger?.classList.add('pointer-events-none', 'opacity-60');

            try {
                const response = await window.axios.post(form.action, data, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'multipart/form-data',
                    },
                });
                const attachmentsRoot = form.closest('[data-invoice-detail-root]')?.querySelector('[data-invoice-attachments-root]')
                    ?? document.querySelector('[data-invoice-attachments-root]');

                if (attachmentsRoot && response.data.attachments_html) {
                    attachmentsRoot.innerHTML = response.data.attachments_html;
                    Alpine.initTree(attachmentsRoot);
                }

                form.reset();
                window.dispatchEvent(new CustomEvent('crud-toast', {
                    detail: { message: response.data.message ?? 'Archivos cargados correctamente.' },
                }));
            } catch (error) {
                const message = Object.values(error.response?.data?.errors ?? {}).flat()[0]
                    || error.response?.data?.message
                    || 'No fue posible cargar los archivos.';
                window.dispatchEvent(new CustomEvent('crud-toast', {
                    detail: { message, type: 'error' },
                }));
            } finally {
                if (button) {
                    button.disabled = false;
                    button.textContent = originalText;
                }
                uploadTrigger?.classList.remove('pointer-events-none', 'opacity-60');
            }
        });
    });
};

window.initializeAssetAttachmentForms = (root = document) => {
    root.querySelectorAll('form[data-asset-attachment-form]').forEach((form) => {
        if (form.dataset.assetAttachmentInitialized === 'true') {
            return;
        }

        form.dataset.assetAttachmentInitialized = 'true';

        form.querySelector('[data-asset-file-input]')?.addEventListener('change', () => {
            if (form.querySelector('[data-asset-file-input]')?.files?.length) {
                form.requestSubmit();
            }
        });

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            event.stopPropagation();

            const uploadTrigger = form.querySelector('label[for]');
            const data = new FormData(form);

            uploadTrigger?.classList.add('pointer-events-none', 'opacity-60');

            try {
                const response = await window.axios.post(form.action, data, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'multipart/form-data',
                    },
                });
                const attachmentsRoot = document.querySelector('[x-ref="attachments"]');

                if (attachmentsRoot && response.data.attachments_html) {
                    attachmentsRoot.innerHTML = response.data.attachments_html;
                    Alpine.initTree(attachmentsRoot);
                }

                form.reset();
                window.dispatchEvent(new CustomEvent('crud-toast', {
                    detail: { message: response.data.message ?? 'Archivos cargados correctamente.' },
                }));
            } catch (error) {
                const message = Object.values(error.response?.data?.errors ?? {}).flat()[0]
                    || error.response?.data?.message
                    || 'No fue posible cargar los archivos.';
                window.dispatchEvent(new CustomEvent('crud-toast', {
                    detail: { message, type: 'error' },
                }));
            } finally {
                uploadTrigger?.classList.remove('pointer-events-none', 'opacity-60');
            }
        });
    });
};

window.initializeExpenseForms = (root = document) => {
    root.querySelectorAll('form[data-expense-form]').forEach((form) => {
        if (form.dataset.expenseInitialized === 'true') {
            return;
        }

        form.dataset.expenseInitialized = 'true';

        const payloadNode = form.querySelector('[data-expense-payload]');
        const selectedNode = form.querySelector('[data-expense-selected]');

        if (! payloadNode || ! selectedNode) {
            return;
        }

        const payload = JSON.parse(payloadNode.textContent || '{}');
        const selected = JSON.parse(selectedNode.textContent || '{}');

        const projects = (payload.projects ?? []).map((item) => ({ ...item, id: String(item.id), company_id: String(item.company_id) }));
        const normalizeCategories = (items = []) => items.map((item) => ({ ...item, id: String(item.id), project_id: String(item.project_id) }));
        const normalizeSubcategories = (items = []) => items.map((item) => ({
            ...item,
            id: String(item.id),
            project_id: String(item.project_id),
            category_id: String(item.category_id),
        }));
        const normalizeAuxiliaries = (items = []) => items.map((item) => ({
            ...item,
            id: String(item.id),
            project_id: String(item.project_id),
            category_id: String(item.category_id),
            subcategory_id: String(item.subcategory_id),
        }));

        let categories = normalizeCategories(payload.categories ?? []);
        let subcategories = normalizeSubcategories(payload.subcategories ?? []);
        let auxiliaries = normalizeAuxiliaries(payload.auxiliaries ?? []);
        const providers = (payload.providers ?? []).map((item) => ({ ...item, id: String(item.id), company_id: String(item.company_id) }));

        const projectField = form.querySelector('[data-expense-project]');
        const categoryWrapper = form.querySelector('[data-expense-category-wrapper]');
        const categoryField = form.querySelector('[data-expense-category]');
        const categoryCards = form.querySelector('[data-expense-category-cards]');
        const subcategoryWrapper = form.querySelector('[data-expense-subcategory-wrapper]');
        const subcategoryField = form.querySelector('[data-expense-subcategory]');
        const subcategoryCards = form.querySelector('[data-expense-subcategory-cards]');
        const subcategoryEmpty = form.querySelector('[data-expense-subcategory-empty]');
        const auxiliaryWrapper = form.querySelector('[data-expense-auxiliary-wrapper]');
        const auxiliaryField = form.querySelector('[data-expense-auxiliary]');
        const auxiliaryCards = form.querySelector('[data-expense-auxiliary-cards]');
        const providerField = form.querySelector('[data-expense-provider]');
        const subtotalField = form.querySelector('#subtotal_amount');
        const createStructureButtons = [...form.querySelectorAll('[data-expense-create-structure]')];
        const structureModal = form.querySelector('[data-expense-structure-modal]');
        const structureTitle = form.querySelector('[data-structure-modal-title]');
        const structureContext = form.querySelector('[data-structure-modal-context]');
        const structureAlert = form.querySelector('[data-structure-modal-alert]');
        const structureNameField = form.querySelector('[data-structure-name]');
        const structureDescriptionField = form.querySelector('[data-structure-description]');
        const structureSaveButton = form.querySelector('[data-structure-save]');
        const structureCloseButtons = [...form.querySelectorAll('[data-structure-modal-close]')];

        const state = {
            projectId: selected.project_id ? String(selected.project_id) : '',
            categoryId: selected.category_id ? String(selected.category_id) : '',
            subcategoryId: selected.subcategory_id ? String(selected.subcategory_id) : '',
            auxiliaryId: selected.auxiliary_id ? String(selected.auxiliary_id) : '',
            providerId: selected.provider_id ? String(selected.provider_id) : '',
        };

        const quickCreate = {
            type: null,
            saving: false,
        };

        const debug = () => {};

        const selectedCategory = () => categories.find((item) => item.id === state.categoryId) ?? null;
        const selectedSubcategory = () => subcategories.find((item) => item.id === state.subcategoryId) ?? null;

        const urlFromTemplate = (template, projectId) => {
            if (! template || ! projectId) {
                return null;
            }

            return template.replace('__PROJECT__', encodeURIComponent(projectId));
        };

        const clearStructureErrors = () => {
            if (structureAlert) {
                structureAlert.textContent = '';
                structureAlert.classList.add('hidden');
            }

            form.querySelectorAll('[data-structure-error-for]').forEach((element) => {
                element.textContent = '';
                element.classList.add('hidden');
            });
        };

        const showStructureErrors = (errors = {}, fallback = 'No fue posible crear el registro.') => {
            clearStructureErrors();

            const entries = Object.entries(errors);

            entries.forEach(([field, messages]) => {
                const element = form.querySelector(`[data-structure-error-for="${field}"]`);

                if (! element) {
                    return;
                }

                element.textContent = Array.isArray(messages) ? messages[0] : String(messages);
                element.classList.remove('hidden');
            });

            if (structureAlert && entries.length === 0) {
                structureAlert.textContent = fallback;
                structureAlert.classList.remove('hidden');
            }
        };

        const setStructureSaving = (saving) => {
            quickCreate.saving = saving;

            if (structureSaveButton) {
                structureSaveButton.disabled = saving;
                structureSaveButton.textContent = saving ? 'Creando...' : 'Crear';
            }
        };

        const syncTotalPreview = (preserveCaret = false) => {
            if (! subtotalField) {
                return;
            }

            const rawValue = subtotalField.value;
            const selectionStart = subtotalField.selectionStart ?? rawValue.length;
            const digitsBeforeCaret = countAmountDigits(rawValue.slice(0, selectionStart));
            const formattedValue = formatIntegerAmount(rawValue);

            subtotalField.value = formattedValue;

            if (preserveCaret) {
                const nextCaret = caretPositionFromDigits(formattedValue, digitsBeforeCaret);
                subtotalField.setSelectionRange(nextCaret, nextCaret);
            }
        };

        const replaceOptions = (select, items, placeholder, includeEmpty = true) => {
            if (! select) {
                return;
            }

            const current = select.value;
            select.innerHTML = '';

            if (includeEmpty) {
                const empty = document.createElement('option');
                empty.value = '';
                empty.textContent = placeholder;
                select.appendChild(empty);
            }

            items.forEach((item) => {
                const option = document.createElement('option');
                option.value = item.id;
                option.textContent = item.name;
                select.appendChild(option);
            });

            if ([...select.options].some((option) => option.value === current)) {
                select.value = current;
            }
        };

        const renderCards = ({
            container,
            items,
            selectedValue,
            onSelect,
            emptyLabel = null,
            emptyDescription = null,
            emptyValue = '',
            tone = 'stone',
            meta = null,
        }) => {
            if (! container) {
                return;
            }

            const tones = {
                stone: {
                    active: 'border-amber-300 bg-amber-100 text-amber-950 shadow-sm ring-2 ring-amber-200',
                    idle: 'border-stone-200 bg-stone-50 text-stone-700 hover:border-stone-300 hover:bg-stone-100',
                },
                sky: {
                    active: 'border-sky-300 bg-sky-100 text-sky-950 shadow-sm ring-2 ring-sky-200',
                    idle: 'border-sky-100 bg-sky-50 text-stone-700 hover:border-sky-200 hover:bg-sky-100',
                },
                emerald: {
                    active: 'border-emerald-300 bg-emerald-100 text-emerald-950 shadow-sm ring-2 ring-emerald-200',
                    idle: 'border-emerald-100 bg-emerald-50 text-stone-700 hover:border-emerald-200 hover:bg-emerald-100',
                },
            };

            const palette = tones[tone] ?? tones.stone;
            container.innerHTML = '';

            const appendCard = (value, label, description = '') => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = `rounded-2xl border px-4 py-3 text-left text-sm transition ${String(value) === String(selectedValue) ? palette.active : palette.idle}`;
                button.innerHTML = `
                    <span class="flex items-start justify-between gap-3">
                        <span class="block font-medium">${label}</span>
                        ${String(value) === String(selectedValue)
                            ? '<span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-white/75 text-current"><svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 5.29a1 1 0 010 1.42l-7.2 7.2a1 1 0 01-1.415 0l-3-3a1 1 0 011.415-1.41l2.292 2.29 6.493-6.49a1 1 0 011.415 0z" clip-rule="evenodd" /></svg></span>'
                            : '<span class="mt-0.5 inline-block h-5 w-5 shrink-0 rounded-full border border-current/20 bg-white/60"></span>'}
                    </span>
                    ${description ? `<span class="mt-1 block text-xs opacity-70">${description}</span>` : ''}
                `;
                button.addEventListener('click', () => onSelect(String(value)));
                container.appendChild(button);
            };

            if (emptyLabel !== null) {
                appendCard(emptyValue, emptyLabel, emptyDescription ?? '');
            }

            items.forEach((item) => {
                appendCard(item.id, item.name, meta ? meta(item) : '');
            });
        };

        const getSelectedProject = () => projects.find((item) => item.id === state.projectId) ?? null;

        const refreshCreateButtons = () => {
            createStructureButtons.forEach((button) => {
                const type = button.dataset.expenseCreateStructure;
                const disabled = (type === 'category' && ! state.projectId)
                    || (type === 'subcategory' && ! state.categoryId)
                    || (type === 'auxiliary' && ! state.subcategoryId);

                button.disabled = disabled;
            });
        };

        const openStructureModal = (type) => {
            if (! structureModal || ! state.projectId) {
                return;
            }

            if (type === 'subcategory' && ! state.categoryId) {
                return;
            }

            if (type === 'auxiliary' && ! state.subcategoryId) {
                return;
            }

            const category = selectedCategory();
            const subcategory = selectedSubcategory();
            const copy = {
                category: {
                    title: 'Nueva categoría',
                    context: getSelectedProject() ? `Proyecto: ${getSelectedProject().name}` : '',
                },
                subcategory: {
                    title: 'Nueva subcategoría',
                    context: category ? `Categoría: ${category.name}` : '',
                },
                auxiliary: {
                    title: 'Nuevo auxiliar',
                    context: subcategory ? `Subcategoría: ${subcategory.name}` : '',
                },
            };

            quickCreate.type = type;
            clearStructureErrors();

            if (structureTitle) {
                structureTitle.textContent = copy[type]?.title ?? 'Nuevo registro';
            }

            if (structureContext) {
                structureContext.textContent = copy[type]?.context ?? '';
            }

            if (structureNameField) {
                structureNameField.value = '';
            }

            if (structureDescriptionField) {
                structureDescriptionField.value = '';
            }

            setStructureSaving(false);
            structureModal.classList.remove('hidden');
            structureModal.classList.add('flex');
            window.setTimeout(() => structureNameField?.focus(), 0);
        };

        const closeStructureModal = () => {
            if (! structureModal) {
                return;
            }

            quickCreate.type = null;
            clearStructureErrors();
            structureModal.classList.add('hidden');
            structureModal.classList.remove('flex');
        };

        const mergeExpenseStructure = (structure = {}) => {
            if (structure.categories) {
                categories = normalizeCategories(structure.categories);
            }

            if (structure.subcategories) {
                subcategories = normalizeSubcategories(structure.subcategories);
            }

            if (structure.auxiliaries) {
                auxiliaries = normalizeAuxiliaries(structure.auxiliaries);
            }
        };

        const applyCreatedStructure = (payload = {}) => {
            mergeExpenseStructure(payload.expense_structure ?? {});

            const created = payload.created ?? {};
            const createdId = created.id ? String(created.id) : '';

            if (created.type === 'category' && createdId) {
                state.categoryId = createdId;
                state.subcategoryId = '';
                state.auxiliaryId = '';
            }

            if (created.type === 'subcategory' && createdId) {
                state.subcategoryId = createdId;
                state.auxiliaryId = '';
            }

            if (created.type === 'auxiliary' && createdId) {
                state.auxiliaryId = createdId;
            }

            syncCategories();
        };

        const storeStructureRecord = async () => {
            if (! quickCreate.type || quickCreate.saving) {
                return;
            }

            const name = structureNameField?.value.trim() ?? '';
            const description = structureDescriptionField?.value.trim() ?? '';
            const formData = new FormData();
            const urlTemplates = {
                category: form.dataset.categoryStoreUrlTemplate,
                subcategory: form.dataset.subcategoryStoreUrlTemplate,
                auxiliary: form.dataset.auxiliaryStoreUrlTemplate,
            };
            const url = urlFromTemplate(urlTemplates[quickCreate.type], state.projectId);

            if (! url) {
                showStructureErrors({}, 'Selecciona un proyecto antes de crear el registro.');
                return;
            }

            formData.append('name', name);
            formData.append('description', description);

            if (quickCreate.type === 'subcategory') {
                formData.append('category_id', state.categoryId);
            }

            if (quickCreate.type === 'auxiliary') {
                formData.append('subcategory_id', state.subcategoryId);
            }

            setStructureSaving(true);

            try {
                const response = await window.axios.post(url, formData, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                applyCreatedStructure(response.data);
                closeStructureModal();
            } catch (error) {
                if (error.response?.status === 422 && error.response.data?.errors) {
                    showStructureErrors(error.response.data.errors);
                } else {
                    showStructureErrors({}, error.response?.data?.message || error.message || 'No fue posible crear el registro.');
                }
            } finally {
                setStructureSaving(false);
            }
        };

        const syncProviders = () => {
            const project = getSelectedProject();
            const availableProviders = project
                ? providers.filter((provider) => provider.company_id === project.company_id)
                : [];

            replaceOptions(providerField, availableProviders, 'Sin proveedor');

            if (! availableProviders.some((provider) => provider.id === state.providerId)) {
                state.providerId = '';
            }

            if (providerField) {
                providerField.value = state.providerId;
            }

            debug('syncProviders', { availableProviders });
        };

        const syncAuxiliaries = () => {
            const availableAuxiliaries = auxiliaries.filter((item) => (
                item.project_id === state.projectId
                && item.category_id === state.categoryId
                && item.subcategory_id === state.subcategoryId
            ));

            replaceOptions(auxiliaryField, availableAuxiliaries, 'Sin auxiliar');
            renderCards({
                container: auxiliaryCards,
                items: availableAuxiliaries,
                selectedValue: state.auxiliaryId,
                onSelect: (value) => {
                    state.auxiliaryId = value;
                    if (auxiliaryField) {
                        auxiliaryField.value = value;
                    }
                    syncAuxiliaries();
                },
                emptyLabel: 'Sin auxiliar',
                emptyDescription: 'El gasto no aplica a un auxiliar puntual',
                tone: 'emerald',
            });

            if (! availableAuxiliaries.some((item) => item.id === state.auxiliaryId)) {
                state.auxiliaryId = '';
            }

            if (auxiliaryField) {
                auxiliaryField.value = state.auxiliaryId;
            }

            if (auxiliaryWrapper) {
                auxiliaryWrapper.style.display = state.subcategoryId ? '' : 'none';
            }

            refreshCreateButtons();
            debug('syncAuxiliaries', { availableAuxiliaries });
        };

        const syncSubcategories = () => {
            const availableSubcategories = subcategories.filter((item) => (
                item.project_id === state.projectId
                && item.category_id === state.categoryId
            ));

            replaceOptions(subcategoryField, availableSubcategories, 'Solo categoría');
            renderCards({
                container: subcategoryCards,
                items: availableSubcategories,
                selectedValue: state.subcategoryId,
                onSelect: (value) => {
                    state.subcategoryId = value;
                    if (subcategoryField) {
                        subcategoryField.value = value;
                    }
                    state.auxiliaryId = '';
                    syncSubcategories();
                },
                tone: 'sky',
                emptyLabel: 'Solo categoría',
                emptyDescription: 'El gasto queda clasificado en la categoría',
                meta: (item) => {
                    const count = auxiliaries.filter((auxiliary) => auxiliary.subcategory_id === item.id).length;
                    return count > 0 ? `${count} auxiliares` : 'Sin auxiliares';
                },
            });

            if (! availableSubcategories.some((item) => item.id === state.subcategoryId)) {
                state.subcategoryId = '';
            }

            if (subcategoryField) {
                subcategoryField.value = state.subcategoryId;
            }

            if (subcategoryWrapper) {
                subcategoryWrapper.style.display = state.categoryId ? '' : 'none';
            }

            if (subcategoryEmpty) {
                subcategoryEmpty.classList.toggle('hidden', ! state.categoryId || availableSubcategories.length > 0);
            }

            syncAuxiliaries();
            refreshCreateButtons();
            debug('syncSubcategories', { availableSubcategories });
        };

        const syncCategories = () => {
            const availableCategories = categories.filter((item) => item.project_id === state.projectId);

            replaceOptions(categoryField, availableCategories, 'Selecciona una categoría');
            renderCards({
                container: categoryCards,
                items: availableCategories,
                selectedValue: state.categoryId,
                onSelect: (value) => {
                    state.categoryId = value;
                    if (categoryField) {
                        categoryField.value = value;
                    }
                    state.subcategoryId = '';
                    state.auxiliaryId = '';
                    syncCategories();
                },
                tone: 'stone',
                meta: (item) => {
                    const count = subcategories.filter((subcategory) => subcategory.category_id === item.id).length;
                    return `${count} subcategorías`;
                },
            });

            if (! availableCategories.some((item) => item.id === state.categoryId)) {
                state.categoryId = '';
            }

            if (categoryField) {
                categoryField.value = state.categoryId;
            }

            if (categoryWrapper) {
                categoryWrapper.style.display = state.projectId ? '' : 'none';
            }

            syncSubcategories();
            refreshCreateButtons();
            debug('syncCategories', { availableCategories });
        };

        const syncProject = () => {
            syncProviders();
            syncCategories();
            debug('syncProject');
        };

        if (! state.projectId && projects.length === 1) {
            state.projectId = projects[0].id;
        }

        if (projectField) {
            projectField.value = state.projectId;
            projectField.addEventListener('change', (event) => {
                state.projectId = String(event.target.value || '');
                state.categoryId = '';
                state.subcategoryId = '';
                state.auxiliaryId = '';
                state.providerId = '';
                syncProject();
            });
        }

        if (categoryField) {
            categoryField.addEventListener('change', (event) => {
                state.categoryId = String(event.target.value || '');
                state.subcategoryId = '';
                state.auxiliaryId = '';
                syncSubcategories();
            });
        }

        if (subcategoryField) {
            subcategoryField.addEventListener('change', (event) => {
                state.subcategoryId = String(event.target.value || '');
                state.auxiliaryId = '';
                syncAuxiliaries();
            });
        }

        if (auxiliaryField) {
            auxiliaryField.addEventListener('change', (event) => {
                state.auxiliaryId = String(event.target.value || '');
                debug('auxiliary:change');
            });
        }

        if (providerField) {
            providerField.addEventListener('change', (event) => {
                state.providerId = String(event.target.value || '');
                debug('provider:change');
            });
        }

        createStructureButtons.forEach((button) => {
            button.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                openStructureModal(button.dataset.expenseCreateStructure);
            });
        });

        structureCloseButtons.forEach((button) => {
            button.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                closeStructureModal();
            });
        });

        structureModal?.addEventListener('click', (event) => {
            event.stopPropagation();

            if (event.target === structureModal) {
                closeStructureModal();
            }
        });

        structureSaveButton?.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            storeStructureRecord();
        });

        structureModal?.addEventListener('keydown', (event) => {
            if (event.target instanceof HTMLTextAreaElement) {
                return;
            }

            if (event.key === 'Enter') {
                event.preventDefault();
                event.stopPropagation();
                storeStructureRecord();
            }
        });

        if (subtotalField) {
            subtotalField.addEventListener('input', () => {
                syncTotalPreview(true);
                debug('subtotal:input', { subtotal: subtotalField.value });
            });

            subtotalField.addEventListener('blur', () => {
                syncTotalPreview();
            });

            form.addEventListener('submit', () => {
                subtotalField.value = normalizeIntegerAmount(subtotalField.value);
            });
        }

        syncProject();
        syncTotalPreview();
        debug('init:complete');
    });
};

window.initializeProjectStructureSorting = (root = document) => {
    root.querySelectorAll('[data-sortable-list]').forEach((list) => {
        if (list.dataset.sortableInitialized === 'true') {
            return;
        }

        list.dataset.sortableInitialized = 'true';
        list.querySelectorAll('[data-sortable-item]').forEach((item) => {
            item.draggable = false;
        });
    });
};

let draggedSortableItem = null;
let draggedSortableList = null;
let pointerSortableItem = null;
let pointerSortableList = null;
let pointerStartY = 0;
let pointerMoved = false;

const sortableItems = (list) => [...list.querySelectorAll(':scope > [data-sortable-item]')];

const sortablePayload = (list) => {
    const payload = {
        order: sortableItems(list).map((item) => Number(item.dataset.sortableId)),
    };

    if (list.dataset.sortableParentName && list.dataset.sortableParentId) {
        payload[list.dataset.sortableParentName] = Number(list.dataset.sortableParentId);
    }

    return payload;
};

const sortableAfterElement = (list, clientY) => {
    return sortableItems(list)
        .filter((item) => item !== draggedSortableItem)
        .reduce((closest, item) => {
            const box = item.getBoundingClientRect();
            const offset = clientY - box.top - (box.height / 2);

            if (offset < 0 && offset > closest.offset) {
                return { offset, element: item };
            }

            return closest;
        }, { offset: Number.NEGATIVE_INFINITY, element: null }).element;
};

const dispatchCrudToast = (message, type = 'success') => {
    window.dispatchEvent(new CustomEvent('crud-toast', {
        detail: { message, type },
    }));
};

const collectPreservedScroll = (root = document) => {
    const positions = {};

    root.querySelectorAll('[data-preserve-scroll-key]').forEach((element) => {
        positions[element.dataset.preserveScrollKey] = {
            left: element.scrollLeft,
            top: element.scrollTop,
        };
    });

    return positions;
};

const restorePreservedScroll = (root = document, positions = {}) => {
    root.querySelectorAll('[data-preserve-scroll-key]').forEach((element) => {
        const position = positions[element.dataset.preserveScrollKey];

        if (! position) {
            return;
        }

        element.scrollLeft = position.left;
        element.scrollTop = position.top;
    });
};

const persistSortableOrder = async (list) => {
    if (! list?.dataset.sortableUrl) {
        return;
    }

    try {
        const response = await window.axios.patch(list.dataset.sortableUrl, sortablePayload(list), {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        dispatchCrudToast(response.data?.message ?? 'Orden actualizado correctamente.');
    } catch (error) {
        dispatchCrudToast(
            error.response?.data?.message || error.message || 'No fue posible guardar el nuevo orden.',
            'error',
        );
    }
};

document.addEventListener('submit', async (event) => {
    const form = event.target.closest('form[data-ajax-form]');

    if (! form || event.defaultPrevented) {
        return;
    }

    if (form.dataset.ajaxSubmitting === 'true') {
        event.preventDefault();
        event.stopPropagation();
        return;
    }

    event.preventDefault();
    event.stopPropagation();
    form.dataset.ajaxSubmitting = 'true';
    const submitter = event.submitter?.matches?.('[type="submit"]') ? event.submitter : form.querySelector('[type="submit"]');

    if (submitter) {
        submitter.disabled = true;
        submitter.classList.add('pointer-events-none', 'opacity-60');
    }

    try {
        const response = await window.axios.post(form.action, new FormData(form), {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        const payload = response.data ?? {};

        if (payload.structure_html) {
            const currentStructure = document.querySelector('[data-project-structure-root]');
            const selectedCategoryId = currentStructure?.dataset.selectedCategoryId
                || window.projectStructureSelection?.selectedCategoryId
                || '';
            const selectedSubcategoryId = currentStructure?.dataset.selectedSubcategoryId
                || window.projectStructureSelection?.selectedSubcategoryId
                || '';
            const preservedScroll = collectPreservedScroll(document);
            const scrollY = window.scrollY;
            const template = document.createElement('template');
            template.innerHTML = payload.structure_html.trim();
            const nextStructure = template.content.firstElementChild;

            if (currentStructure && nextStructure) {
                nextStructure.dataset.initialSelectedCategoryId = selectedCategoryId;
                nextStructure.dataset.initialSelectedSubcategoryId = selectedSubcategoryId;
                currentStructure.replaceWith(nextStructure);
                Alpine.initTree(nextStructure);
                window.initializeProjectStructureSorting?.(nextStructure);
                window.requestAnimationFrame(() => {
                    restorePreservedScroll(document, preservedScroll);
                    window.scrollTo({ top: scrollY });
                });
            }
        }

        document.querySelector('[data-action="close-modal"]')?.click();
        dispatchCrudToast(payload.message ?? 'Operación realizada correctamente.');
    } catch (error) {
        dispatchCrudToast(
            error.response?.data?.message || error.message || 'No fue posible guardar la información.',
            'error',
        );
    } finally {
        delete form.dataset.ajaxSubmitting;
        if (submitter) {
            submitter.disabled = false;
            submitter.classList.remove('pointer-events-none', 'opacity-60');
        }
    }
});

document.addEventListener('pointerdown', (event) => {
    const handle = event.target.closest('[data-sortable-handle]');

    if (! handle) {
        return;
    }

    const item = handle.closest('[data-sortable-item]');

    if (! item) {
        return;
    }

    item.draggable = true;
    item.dataset.sortableHandleActive = 'true';
    pointerSortableItem = item;
    pointerSortableList = item.closest('[data-sortable-list]');
    pointerStartY = event.clientY;
    pointerMoved = false;

    if (event.pointerType !== 'mouse') {
        handle.setPointerCapture?.(event.pointerId);
    }
});

document.addEventListener('pointermove', (event) => {
    if (! pointerSortableItem || ! pointerSortableList || event.pointerType === 'mouse') {
        return;
    }

    if (Math.abs(event.clientY - pointerStartY) < 8 && ! pointerMoved) {
        return;
    }

    event.preventDefault();
    pointerMoved = true;
    pointerSortableItem.classList.add('opacity-50', 'ring-2', 'ring-stone-300');

    const afterElement = sortableAfterElement(pointerSortableList, event.clientY);

    if (afterElement) {
        pointerSortableList.insertBefore(pointerSortableItem, afterElement);
    } else {
        pointerSortableList.appendChild(pointerSortableItem);
    }
}, { passive: false });

document.addEventListener('pointerup', () => {
    document.querySelectorAll('[data-sortable-item][draggable="true"]').forEach((item) => {
        if (item !== draggedSortableItem) {
            item.draggable = false;
            delete item.dataset.sortableHandleActive;
        }
    });

    if (pointerSortableItem && pointerSortableList) {
        pointerSortableItem.classList.remove('opacity-50', 'ring-2', 'ring-stone-300');

        if (pointerMoved) {
            persistSortableOrder(pointerSortableList);
        }
    }

    pointerSortableItem = null;
    pointerSortableList = null;
    pointerMoved = false;
});

document.addEventListener('click', (event) => {
    if (event.target.closest('[data-sortable-handle]')) {
        event.preventDefault();
        event.stopPropagation();
    }
}, true);

document.addEventListener('dragstart', (event) => {
    const item = event.target.closest('[data-sortable-item]');

    if (! item || item.dataset.sortableHandleActive !== 'true') {
        event.preventDefault();
        return;
    }

    draggedSortableItem = item;
    draggedSortableList = item.closest('[data-sortable-list]');
    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData('text/plain', item.dataset.sortableId ?? '');
    window.requestAnimationFrame(() => {
        item.classList.add('opacity-50', 'ring-2', 'ring-stone-300');
    });
});

document.addEventListener('dragover', (event) => {
    const list = event.target.closest('[data-sortable-list]');

    if (! list || ! draggedSortableItem || list !== draggedSortableList) {
        return;
    }

    event.preventDefault();
    const afterElement = sortableAfterElement(list, event.clientY);

    if (afterElement) {
        list.insertBefore(draggedSortableItem, afterElement);
    } else {
        list.appendChild(draggedSortableItem);
    }
});

document.addEventListener('drop', (event) => {
    const list = event.target.closest('[data-sortable-list]');

    if (! list || ! draggedSortableItem || list !== draggedSortableList) {
        return;
    }

    event.preventDefault();
    persistSortableOrder(list);
});

document.addEventListener('dragend', () => {
    if (draggedSortableItem) {
        draggedSortableItem.classList.remove('opacity-50', 'ring-2', 'ring-stone-300');
        draggedSortableItem.draggable = false;
        delete draggedSortableItem.dataset.sortableHandleActive;
    }

    draggedSortableItem = null;
    draggedSortableList = null;
});

window.initializeTransactionForms = (root = document) => {
    root.querySelectorAll('form[data-transaction-form]').forEach((form) => {
        if (form.dataset.transactionInitialized === 'true') {
            return;
        }

        form.dataset.transactionInitialized = 'true';

        const payloadNode = form.querySelector('[data-expense-payload]');
        const selectedNode = form.querySelector('[data-expense-selected]');

        if (! payloadNode || ! selectedNode) {
            return;
        }

        const payload = JSON.parse(payloadNode.textContent || '{}');
        const selected = JSON.parse(selectedNode.textContent || '{}');
        const projects = (payload.projects ?? []).map((item) => ({ ...item, id: String(item.id), company_id: String(item.company_id) }));
        const providers = (payload.providers ?? []).map((item) => ({ ...item, id: String(item.id), company_id: String(item.company_id) }));
        const products = (payload.products ?? []).map((item) => ({ ...item, id: String(item.id), company_id: String(item.company_id) }));
        const activities = (payload.activities ?? []).map((item) => ({ ...item, id: String(item.id), company_id: String(item.company_id) }));
        let invoices = (payload.invoices ?? []).map((item) => ({
            ...item,
            id: String(item.id),
            company_id: String(item.company_id),
            project_id: String(item.project_id),
            provider_id: String(item.provider_id),
        }));

        const projectField = form.querySelector('[data-expense-project]') ?? form.querySelector('input[name="project_id"]');
        const providerField = form.querySelector('[data-transaction-provider]');
        const providerSearch = form.querySelector('[data-transaction-provider-search]');
        const providerMenu = form.querySelector('[data-transaction-provider-menu]');
        const invoiceField = form.querySelector('[data-transaction-invoice]');
        const invoiceSearch = form.querySelector('[data-transaction-invoice-search]');
        const invoiceMenu = form.querySelector('[data-transaction-invoice-menu]');
        const productField = form.querySelector('[data-transaction-product]');
        const productSearch = form.querySelector('[data-transaction-product-search]');
        const productMenu = form.querySelector('[data-transaction-product-menu]');
        const activityField = form.querySelector('[data-transaction-activity]');
        const activitySearch = form.querySelector('[data-transaction-activity-search]');
        const activityMenu = form.querySelector('[data-transaction-activity-menu]');
        const activityToggle = form.querySelector('[data-transaction-is-activity]');
        const productWrapper = form.querySelector('[data-transaction-product-wrapper]');
        const activityWrapper = form.querySelector('[data-transaction-activity-wrapper]');
        const unitPriceField = form.querySelector('[data-transaction-unit-price]');
        const quantityField = form.querySelector('[data-transaction-quantity]');
        const totalPreviewEl = form.querySelector('[data-transaction-total-preview]');
        const providerClear = form.querySelector('[data-transaction-provider-clear]');
        const productClear = form.querySelector('[data-transaction-product-clear]');
        const activityClear = form.querySelector('[data-transaction-activity-clear]');
        const invoiceClear = form.querySelector('[data-transaction-invoice-clear]');

        const state = {
            projectId: selected.project_id ? String(selected.project_id) : '',
            providerId: selected.provider_id ? String(selected.provider_id) : '',
            invoiceId: selected.invoice_id ? String(selected.invoice_id) : '',
            productId: selected.product_id ? String(selected.product_id) : '',
            activityId: selected.activity_id ? String(selected.activity_id) : '',
            isActivity: Boolean(selected.is_activity),
        };

        if (! state.projectId && projectField?.value) {
            state.projectId = String(projectField.value);
        }

        const currentProject = () => projects.find((project) => project.id === state.projectId) ?? null;
        const optionLabel = (item) => item.name;
        const availableForProject = (items) => {
            const project = currentProject();

            if (project) {
                return items.filter((item) => item.company_id === project.company_id);
            }

            const availableCompanyIds = new Set(projects.map((item) => item.company_id));

            return items.filter((item) => availableCompanyIds.has(item.company_id));
        };
        const availableInvoices = () => invoices.filter((invoice) => (
            invoice.project_id === state.projectId
            && invoice.type === form.dataset.transactionType
            && (! invoice.provider_id || invoice.provider_id === 'null' || invoice.provider_id === state.providerId)
        ));
        const closeMenu = (menu) => menu?.classList.add('hidden');
        const openMenu = (menu) => menu?.classList.remove('hidden');

        const renderMenu = ({ menu, items, emptyText, onSelect, withSubgroup = false }) => {
            if (! menu) {
                return;
            }

            menu.innerHTML = '';

            if (items.length === 0) {
                const empty = document.createElement('div');
                empty.className = 'px-4 py-3 text-sm text-stone-500';
                empty.textContent = emptyText;
                menu.appendChild(empty);
                return;
            }

            items.forEach((item) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'block w-full rounded-xl px-4 py-3 text-left transition hover:bg-stone-100 focus:bg-stone-100 focus:outline-none';
                button.innerHTML = `<span class="block whitespace-nowrap text-sm font-medium text-stone-900">${item.name}</span>${withSubgroup && item.subgroup_name ? `<span class="mt-0.5 block whitespace-nowrap text-xs text-stone-500">${item.subgroup_name}</span>` : ''}`;
                button.addEventListener('mousedown', (event) => {
                    event.preventDefault();
                    onSelect(item);
                });
                menu.appendChild(button);
            });
        };

        const syncSearch = (items, id, search, hidden) => {
            const selectedItem = items.find((item) => item.id === id);

            if (search) {
                search.value = selectedItem ? optionLabel(selectedItem) : '';
            }

            if (hidden) {
                hidden.value = selectedItem ? selectedItem.id : '';
            }
        };

        const resolveTypedValue = (items, search, hidden, key) => {
            const value = (search?.value ?? '').trim().toLocaleLowerCase();
            const match = items.find((item) => optionLabel(item).toLocaleLowerCase() === value);
            state[key] = match ? match.id : '';

            if (hidden) {
                hidden.value = state[key];
            }
        };

        const invoiceLabel = (invoice) => invoice?.label ?? 'Factura sin numero';

        const updateClearButtons = () => {
            providerClear?.classList.toggle('hidden', ! state.providerId);
            productClear?.classList.toggle('hidden', ! state.productId);
            activityClear?.classList.toggle('hidden', ! state.activityId);
            invoiceClear?.classList.toggle('hidden', ! state.invoiceId);
        };

        const syncCatalogVisibility = () => {
            if (activityToggle) {
                activityToggle.checked = state.isActivity;
            }

            productWrapper?.classList.toggle('hidden', state.isActivity);
            activityWrapper?.classList.toggle('hidden', ! state.isActivity);
        };

        const syncLists = () => {
            const availableProviders = availableForProject(providers);
            const availableProducts = availableForProject(products);
            const availableActivities = availableForProject(activities);

            if (! availableProviders.some((provider) => provider.id === state.providerId)) {
                state.providerId = '';
            }

            if (! availableProducts.some((product) => product.id === state.productId)) {
                state.productId = '';
            }

            if (! availableActivities.some((activity) => activity.id === state.activityId)) {
                state.activityId = '';
            }

            if (! availableInvoices().some((invoice) => invoice.id === state.invoiceId)) {
                state.invoiceId = '';
            }

            syncSearch(availableProviders, state.providerId, providerSearch, providerField);
            syncSearch(availableInvoices().map((invoice) => ({ ...invoice, name: invoiceLabel(invoice) })), state.invoiceId, invoiceSearch, invoiceField);
            syncSearch(availableProducts, state.productId, productSearch, productField);
            syncSearch(availableActivities, state.activityId, activitySearch, activityField);

            renderMenu({
                menu: providerMenu,
                items: availableProviders,
                emptyText: 'Sin proveedores disponibles',
                onSelect: (item) => {
                    state.providerId = item.id;
                    if (providerField) providerField.value = item.id;
                    if (providerSearch) providerSearch.value = item.name;
                    closeMenu(providerMenu);
                },
            });

            renderMenu({
                menu: productMenu,
                items: availableProducts,
                emptyText: 'Sin productos disponibles',
                withSubgroup: true,
                onSelect: (item) => {
                    state.productId = item.id;
                    state.activityId = '';
                    if (productField) productField.value = item.id;
                    if (productSearch) productSearch.value = item.name;
                    if (activityField) activityField.value = '';
                    if (activitySearch) activitySearch.value = '';
                    updateClearButtons();
                    closeMenu(productMenu);
                },
            });

            renderMenu({
                menu: activityMenu,
                items: availableActivities,
                emptyText: 'Sin actividades disponibles',
                withSubgroup: true,
                onSelect: (item) => {
                    state.activityId = item.id;
                    state.productId = '';
                    if (activityField) activityField.value = item.id;
                    if (activitySearch) activitySearch.value = item.name;
                    if (productField) productField.value = '';
                    if (productSearch) productSearch.value = '';
                    updateClearButtons();
                    closeMenu(activityMenu);
                },
            });

            syncCatalogVisibility();
            updateClearButtons();
        };

        const syncUnitPrice = (preserveCaret = false) => {
            if (! unitPriceField) {
                return;
            }

            const rawValue = unitPriceField.value;
            const selectionStart = unitPriceField.selectionStart ?? rawValue.length;
            const digitsBeforeCaret = countAmountDigits(rawValue.slice(0, selectionStart));
            const formattedValue = formatIntegerAmount(rawValue);

            unitPriceField.value = formattedValue;

            if (preserveCaret) {
                const nextCaret = caretPositionFromDigits(formattedValue, digitsBeforeCaret);
                unitPriceField.setSelectionRange(nextCaret, nextCaret);
            }
        };

        const updateTotalPreview = () => {
            if (! totalPreviewEl) {
                return;
            }

            const rawPrice = normalizeIntegerAmount(unitPriceField?.value ?? '');
            const price = parseFloat(rawPrice) || 0;
            const qty = parseFloat(quantityField?.value ?? '') || 0;

            if (price > 0 && qty > 0 && qty !== 1) {
                const total = Math.round(price * qty);
                const formattedQty = Number.isInteger(qty)
                    ? String(qty)
                    : qty.toLocaleString('es-CO', { maximumFractionDigits: 2 });
                totalPreviewEl.innerHTML = `<span style="display:block;font-size:0.7rem;line-height:1rem;color:#a8a29e;margin-bottom:1px">${formattedQty} x $ ${formatIntegerAmount(String(Math.round(price)))}</span><span style="display:block;font-size:1.25rem;line-height:1.5rem;font-weight:700;color:#1c1917;letter-spacing:-0.01em">$ ${formatIntegerAmount(String(total))}</span>`;
                totalPreviewEl.classList.remove('hidden');
            } else {
                totalPreviewEl.classList.add('hidden');
            }
        };

        if (! state.projectId && projects.length === 1) {
            state.projectId = projects[0].id;
        }

        if (projectField) {
            projectField.value = state.projectId;
            projectField.addEventListener('change', (event) => {
                state.projectId = String(event.target.value || '');
                state.providerId = '';
                state.invoiceId = '';
                state.productId = '';
                state.activityId = '';
                syncLists();
            });
        }

        providerSearch?.addEventListener('focus', () => {
            const items = availableForProject(providers);
            const term = providerSearch.value.trim().toLocaleLowerCase();
            renderMenu({
                menu: providerMenu,
                items: items.filter((item) => item.name.toLocaleLowerCase().includes(term)),
                emptyText: 'Sin proveedores disponibles',
                onSelect: (item) => {
                    state.providerId = item.id;
                    state.invoiceId = '';
                    if (providerField) providerField.value = item.id;
                    if (providerSearch) providerSearch.value = item.name;
                    updateClearButtons();
                    closeMenu(providerMenu);
                },
            });
            openMenu(providerMenu);
        });
        providerSearch?.addEventListener('input', () => {
            const items = availableForProject(providers);
            const term = providerSearch.value.trim().toLocaleLowerCase();
            resolveTypedValue(items, providerSearch, providerField, 'providerId');
            renderMenu({
                menu: providerMenu,
                items: items.filter((item) => item.name.toLocaleLowerCase().includes(term)),
                emptyText: 'Sin proveedores disponibles',
                onSelect: (item) => {
                    state.providerId = item.id;
                    state.invoiceId = '';
                    if (providerField) providerField.value = item.id;
                    if (providerSearch) providerSearch.value = item.name;
                    updateClearButtons();
                    closeMenu(providerMenu);
                },
            });
            openMenu(providerMenu);
        });
        providerSearch?.addEventListener('blur', () => {
            window.setTimeout(() => {
                syncSearch(availableForProject(providers), state.providerId, providerSearch, providerField);
                closeMenu(providerMenu);
            }, 120);
        });

        const renderInvoiceMenu = (items) => {
            renderMenu({
                menu: invoiceMenu,
                items: [{ id: '', name: 'Sin factura' }, ...items.map((invoice) => ({ ...invoice, name: invoiceLabel(invoice) }))],
                emptyText: 'Sin facturas disponibles',
                onSelect: (item) => {
                    state.invoiceId = item.id;
                    if (invoiceField) invoiceField.value = item.id;
                    if (invoiceSearch) invoiceSearch.value = item.name === 'Sin factura' ? '' : item.name;
                    updateClearButtons();
                    closeMenu(invoiceMenu);
                },
            });
        };

        invoiceSearch?.addEventListener('focus', () => {
            const term = invoiceSearch.value.trim().toLocaleLowerCase();
            renderInvoiceMenu(availableInvoices().filter((item) => invoiceLabel(item).toLocaleLowerCase().includes(term)));
            openMenu(invoiceMenu);
        });
        invoiceSearch?.addEventListener('input', () => {
            const term = invoiceSearch.value.trim().toLocaleLowerCase();
            const normalized = availableInvoices().map((invoice) => ({ ...invoice, name: invoiceLabel(invoice) }));
            resolveTypedValue(normalized, invoiceSearch, invoiceField, 'invoiceId');
            renderInvoiceMenu(availableInvoices().filter((item) => invoiceLabel(item).toLocaleLowerCase().includes(term)));
            openMenu(invoiceMenu);
        });
        invoiceSearch?.addEventListener('blur', () => {
            window.setTimeout(() => {
                syncSearch(availableInvoices().map((invoice) => ({ ...invoice, name: invoiceLabel(invoice) })), state.invoiceId, invoiceSearch, invoiceField);
                closeMenu(invoiceMenu);
            }, 120);
        });

        const bindCatalogSearch = ({ search, hidden, menu, key, itemsSource, emptyText, clearOther }) => {
            search?.addEventListener('focus', () => {
                const items = itemsSource();
                const term = search.value.trim().toLocaleLowerCase();
                renderMenu({
                    menu,
                    items: items.filter((item) => item.name.toLocaleLowerCase().includes(term) || (item.subgroup_name ?? '').toLocaleLowerCase().includes(term)),
                    emptyText,
                    withSubgroup: true,
                    onSelect: (item) => {
                        state[key] = item.id;
                        if (hidden) hidden.value = item.id;
                        search.value = item.name;
                        clearOther();
                        updateClearButtons();
                        closeMenu(menu);
                    },
                });
                openMenu(menu);
            });

            search?.addEventListener('input', () => {
                const items = itemsSource();
                const term = search.value.trim().toLocaleLowerCase();
                resolveTypedValue(items, search, hidden, key);
                if (state[key]) {
                    clearOther();
                }
                renderMenu({
                    menu,
                    items: items.filter((item) => item.name.toLocaleLowerCase().includes(term) || (item.subgroup_name ?? '').toLocaleLowerCase().includes(term)),
                    emptyText,
                    withSubgroup: true,
                    onSelect: (item) => {
                        state[key] = item.id;
                        if (hidden) hidden.value = item.id;
                        search.value = item.name;
                        clearOther();
                        updateClearButtons();
                        closeMenu(menu);
                    },
                });
                openMenu(menu);
            });

            search?.addEventListener('blur', () => {
                window.setTimeout(() => {
                    syncSearch(itemsSource(), state[key], search, hidden);
                    closeMenu(menu);
                }, 120);
            });
        };

        bindCatalogSearch({
            search: productSearch,
            hidden: productField,
            menu: productMenu,
            key: 'productId',
            itemsSource: () => availableForProject(products),
            emptyText: 'Sin productos disponibles',
            clearOther: () => {
                state.activityId = '';
                if (activityField) activityField.value = '';
                if (activitySearch) activitySearch.value = '';
            },
        });

        bindCatalogSearch({
            search: activitySearch,
            hidden: activityField,
            menu: activityMenu,
            key: 'activityId',
            itemsSource: () => availableForProject(activities),
            emptyText: 'Sin actividades disponibles',
            clearOther: () => {
                state.productId = '';
                if (productField) productField.value = '';
                if (productSearch) productSearch.value = '';
            },
        });

        providerClear?.addEventListener('click', () => {
            state.providerId = '';
            state.invoiceId = '';
            if (providerField) providerField.value = '';
            if (providerSearch) providerSearch.value = '';
            if (invoiceField) invoiceField.value = '';
            if (invoiceSearch) invoiceSearch.value = '';
            syncLists();
        });
        productClear?.addEventListener('click', () => {
            state.productId = '';
            if (productField) productField.value = '';
            if (productSearch) productSearch.value = '';
            syncLists();
        });
        activityClear?.addEventListener('click', () => {
            state.activityId = '';
            if (activityField) activityField.value = '';
            if (activitySearch) activitySearch.value = '';
            syncLists();
        });
        invoiceClear?.addEventListener('click', () => {
            state.invoiceId = '';
            if (invoiceField) invoiceField.value = '';
            if (invoiceSearch) invoiceSearch.value = '';
            syncLists();
        });

        activityToggle?.addEventListener('change', () => {
            state.isActivity = Boolean(activityToggle.checked);

            if (state.isActivity) {
                state.productId = '';
                if (productField) productField.value = '';
                if (productSearch) productSearch.value = '';
                closeMenu(productMenu);
            } else {
                state.activityId = '';
                if (activityField) activityField.value = '';
                if (activitySearch) activitySearch.value = '';
                closeMenu(activityMenu);
            }

            syncCatalogVisibility();
            updateClearButtons();
        });

        unitPriceField?.addEventListener('input', () => syncUnitPrice(true));
        unitPriceField?.addEventListener('blur', () => syncUnitPrice());
        unitPriceField?.addEventListener('input', updateTotalPreview);
        quantityField?.addEventListener('input', updateTotalPreview);
        quantityField?.addEventListener('blur', updateTotalPreview);

        form.addEventListener('submit', () => {
            if (unitPriceField) {
                unitPriceField.value = normalizeIntegerAmount(unitPriceField.value);
            }
        });

        form.addEventListener('invoice-added', (event) => {
            const invoice = event.detail ?? {};
            if (! invoice.id) return;
            const normalized = {
                ...invoice,
                id: String(invoice.id),
                company_id: String(invoice.company_id ?? ''),
                project_id: String(invoice.project_id ?? ''),
                provider_id: String(invoice.provider_id ?? ''),
            };
            invoices = invoices.filter((inv) => inv.id !== normalized.id);
            invoices.push(normalized);
            state.invoiceId = normalized.id;
            if (invoiceField) invoiceField.value = normalized.id;
            if (invoiceSearch) invoiceSearch.value = invoiceLabel(normalized);
            syncLists();
        });

        syncLists();
        syncCatalogVisibility();
        syncUnitPrice();
        updateTotalPreview();
    });
};

window.initializeInvoiceInlineRows = () => {
    const addBtn = document.querySelector('[data-invoice-add-row]');
    const saveAllBtn = document.querySelector('[data-invoice-save-all]');
    const tbody = document.querySelector('[data-invoice-tbody]');
    const itemModeSelect = document.querySelector('[data-invoice-item-mode]');
    const productsNode = document.querySelector('[data-invoice-products]');
    const activitiesNode = document.querySelector('[data-invoice-activities]');
    const invoicePageRootSelector = '[data-invoice-page-root]';

    if (! tbody || ! tbody.hasAttribute('data-invoice-editable')) return;

    const storeUrl = addBtn?.dataset.storeUrl;
    const transactionType = addBtn?.dataset.transactionType ?? 'expense';
    const projectId = addBtn?.dataset.projectId;
    const providerId = addBtn?.dataset.providerId;
    const invoiceId = addBtn?.dataset.invoiceId;

    const products = productsNode ? JSON.parse(productsNode.textContent || '[]').map((p) => ({ ...p, id: String(p.id) })) : [];
    const activities = activitiesNode ? JSON.parse(activitiesNode.textContent || '[]').map((a) => ({ ...a, id: String(a.id) })) : [];
    const activityToggleThemes = {
        yes: 'border-emerald-200 bg-emerald-100 text-emerald-800',
        no: 'border-rose-200 bg-rose-100 text-rose-700',
    };
    const parsePrice = (str) => parseInt(String(str ?? '').trim().replace(/\./g, '').replace(/[^0-9]/g, ''), 10) || 0;
    const parseQty = (str) => parseInt(String(str ?? '').trim().replace(/\./g, '').replace(/[^0-9]/g, ''), 10) || 0;
    const formatPrice = (num) => new Intl.NumberFormat('es-CO', { maximumFractionDigits: 0 }).format(num || 0);
    const getInvoiceItemMode = () => itemModeSelect?.value === 'activity' ? 'activity' : 'product';
    const fieldLabels = { invoice_id: 'factura', product_id: 'producto', activity_id: 'actividad', provider_id: 'proveedor', project_id: 'proyecto', unit_price: 'valor unitario', quantity: 'cantidad', expense_date: 'fecha', purchase_date: 'fecha' };
    const humanizeError = (msg) => {
        if (! msg) return 'No fue posible guardar.';
        return msg
            .replace(/The selected ([\w_]+) is invalid\./i, (_, f) => `El campo "${fieldLabels[f] ?? f.replace(/_/g, ' ')}" no es valido o no esta disponible.`)
            .replace(/The ([\w_]+) field is required\./i, (_, f) => `El campo "${fieldLabels[f] ?? f.replace(/_/g, ' ')}" es obligatorio.`)
            .replace(/The ([\w_]+) must be a number\./i, (_, f) => `El campo "${fieldLabels[f] ?? f.replace(/_/g, ' ')}" debe ser un numero.`)
            .replace(/The ([\w_]+) must be at least ([\d.]+)\./i, (_, f, v) => `El campo "${fieldLabels[f] ?? f.replace(/_/g, ' ')}" debe ser al menos ${v}.`);
    };

    const updateInvoiceTotal = () => {
        const totalEl = document.querySelector('[data-invoice-total-amount]');
        if (! totalEl) return;
        let sum = 0;
        tbody.querySelectorAll('tr[data-item-id], tr[data-inline-row]').forEach((tr) => {
            const qty = parseQty(tr.querySelector('[data-item-quantity]')?.value) || 1;
            const price = parsePrice(tr.querySelector('[data-item-unit-price]')?.value);
            sum += qty * price;
        });
        totalEl.textContent = '$ ' + formatPrice(sum);
    };

    const captureInvoicePageState = () => {
        const xScroll = document.querySelector('[data-invoice-items-x-scroll]');

        return {
            windowTop: window.scrollY || window.pageYOffset || 0,
            itemsLeft: xScroll?.scrollLeft ?? 0,
        };
    };

    const restoreInvoicePageState = (state = {}) => {
        window.requestAnimationFrame(() => {
            window.scrollTo({
                top: state.windowTop ?? 0,
                left: 0,
                behavior: 'auto',
            });

            const xScroll = document.querySelector('[data-invoice-items-x-scroll]');
            if (xScroll) {
                xScroll.scrollLeft = state.itemsLeft ?? 0;
            }
        });
    };

    const refreshInvoicePage = async (toastMessage = null) => {
        const currentRoot = document.querySelector(invoicePageRootSelector);
        if (! currentRoot) return false;

        const preservedState = captureInvoicePageState();
        const response = await window.axios.get(window.location.href, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html, application/xhtml+xml',
            },
        });

        const parser = new DOMParser();
        const nextDocument = parser.parseFromString(response.data, 'text/html');
        const nextRoot = nextDocument.querySelector(invoicePageRootSelector);

        if (! nextRoot) {
            return false;
        }

        currentRoot.outerHTML = nextRoot.outerHTML;

        window.requestAnimationFrame(() => {
            const mountedRoot = document.querySelector(invoicePageRootSelector);
            if (mountedRoot) {
                Alpine.initTree(mountedRoot);
                window.initializeInvoiceAttachmentForms?.(mountedRoot);
                window.initializeInvoiceShowForms?.(mountedRoot);
                window.initializeInvoiceInlineRows?.();
            }

            restoreInvoicePageState(preservedState);

            if (toastMessage) {
                window.dispatchEvent(new CustomEvent('crud-toast', {
                    detail: { message: toastMessage },
                }));
            }
        });

        return true;
    };

    const trashSvg = `<svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.257 3.099c.366-.446.911-.699 1.486-.699h.514c.575 0 1.12.253 1.486.699L12.85 4H16a1 1 0 110 2h-1l-.867 10.142A2 2 0 0112.14 18H7.86a2 2 0 01-1.993-1.858L5 6H4a1 1 0 010-2h3.15l1.107-.901zM8 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm4-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>`;

    const buildCatalogMenu = (menu, items, emptyText, onSelect) => {
        menu.innerHTML = '';
        if (items.length === 0) {
            const el = document.createElement('div');
            el.className = 'px-4 py-3 text-sm text-stone-400';
            el.textContent = emptyText;
            menu.appendChild(el);
            menu.classList.remove('hidden');
            return;
        }
        items.forEach((item) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'block w-full rounded-xl px-4 py-2.5 text-left transition hover:bg-stone-100 focus:outline-none';
            btn.innerHTML = `<div class="text-sm font-medium text-stone-900">${item.name}</div>${item.subgroup_name ? `<div class="text-xs text-stone-400">${item.subgroup_name}</div>` : ''}`;
            btn.addEventListener('mousedown', (e) => {
                e.preventDefault();
                onSelect(item);
            });
            menu.appendChild(btn);
        });
        menu.classList.remove('hidden');
    };

    const getUsedIds = (selector, excludeTr = null) => {
        const ids = new Set();
        tbody.querySelectorAll('tr[data-item-id], tr[data-inline-row]').forEach((tr) => {
            if (tr === excludeTr) return;
            const val = tr.querySelector(selector)?.value;
            if (val) ids.add(String(val));
        });
        return ids;
    };

    const initCatalogCombobox = (tr, options) => {
        const searchEl = tr.querySelector(options.searchSelector);
        const fieldEl = tr.querySelector(options.fieldSelector);
        const menuEl = tr.querySelector(options.menuSelector);
        const subgroupEl = tr.querySelector(options.subgroupSelector);

        if (! searchEl || ! fieldEl || ! menuEl) return null;

        document.body.appendChild(menuEl);
        menuEl.style.position = 'fixed';
        menuEl.style.zIndex = '9999';

        const positionMenu = () => {
            const rect = searchEl.getBoundingClientRect();
            menuEl.style.top = `${rect.bottom + 2}px`;
            menuEl.style.left = `${rect.left}px`;
            menuEl.style.minWidth = `${Math.max(rect.width, 288)}px`;
        };

        let selectedId = String(fieldEl.value || '');
        const findItemById = (id) => options.items.find((item) => item.id === String(id)) ?? null;

        const syncSubgroup = (item) => {
            if (! subgroupEl) return;
            subgroupEl.textContent = item?.subgroup_name || '';
            subgroupEl.classList.toggle('hidden', ! item?.subgroup_name);
        };

        const applySelection = (item) => {
            selectedId = item.id;
            fieldEl.value = item.id;
            searchEl.value = item.name;
            syncSubgroup(item);
            menuEl.classList.add('hidden');
            options.onSelect?.(item);
        };

        searchEl.addEventListener('focus', () => {
            positionMenu();
            const used = getUsedIds(options.fieldSelector, tr);
            const term = searchEl.value.trim().toLocaleLowerCase();
            buildCatalogMenu(menuEl, options.items.filter((item) => ! used.has(item.id) && (item.name.toLocaleLowerCase().includes(term) || (item.subgroup_name ?? '').toLocaleLowerCase().includes(term))), options.emptyText, applySelection);
        });

        searchEl.addEventListener('input', () => {
            const term = searchEl.value.trim().toLocaleLowerCase();
            const used = getUsedIds(options.fieldSelector, tr);
            const match = options.items.find((item) => item.name.toLocaleLowerCase() === term && ! used.has(item.id));
            selectedId = match ? match.id : '';
            fieldEl.value = selectedId;
            positionMenu();
            buildCatalogMenu(menuEl, options.items.filter((item) => ! used.has(item.id) && (item.name.toLocaleLowerCase().includes(term) || (item.subgroup_name ?? '').toLocaleLowerCase().includes(term))), options.emptyText, applySelection);
            options.onInput?.(selectedId);
        });

        searchEl.addEventListener('blur', () => {
            window.setTimeout(() => {
                const match = options.items.find((item) => item.id === selectedId);
                searchEl.value = match ? match.name : '';
                fieldEl.value = selectedId;
                syncSubgroup(match);
                menuEl.classList.add('hidden');
                options.onInput?.(selectedId);
            }, 120);
        });

        syncSubgroup(options.items.find((item) => item.id === selectedId));

        return {
            getSelectedId: () => selectedId,
            getSelectedItem: () => findItemById(selectedId),
            setSelection: (item) => {
                if (! item) {
                    selectedId = '';
                    fieldEl.value = '';
                    searchEl.value = '';
                    syncSubgroup(null);
                    return;
                }

                applySelection({
                    ...item,
                    id: String(item.id),
                });
            },
            clear: () => {
                selectedId = '';
                fieldEl.value = '';
                searchEl.value = '';
                syncSubgroup(null);
            },
        };
    };

    const attachRow = (tr, isNew) => {
        if (! isNew) {
            if (tr.dataset.rowInitialized === 'true') return;
            tr.dataset.rowInitialized = 'true';
        }

        const deleteUrl = tr.dataset.itemDeleteUrl;
        const updateUrl = tr.dataset.itemUpdateUrl;
        const itemDate = tr.dataset.itemDate;
        const productWrapper = tr.querySelector('[data-item-product-wrapper]');
        const activityWrapper = tr.querySelector('[data-item-activity-wrapper]');
        const quantityInput = tr.querySelector('[data-item-quantity]');
        const unitPriceInput = tr.querySelector('[data-item-unit-price]');
        const totalEl = tr.querySelector('[data-item-total]');
        const deleteBtn = tr.querySelector('[data-item-delete]');
        let dirty = isNew;
        const markDirty = () => { dirty = true; };
        let lastProductSelection = null;
        let lastActivitySelection = null;

        const productCombobox = initCatalogCombobox(tr, {
            searchSelector: '[data-item-product-search]',
            fieldSelector: '[data-item-product]',
            menuSelector: '[data-item-product-menu]',
            subgroupSelector: '[data-item-product-subgroup]',
            items: products,
            emptyText: 'Sin productos disponibles',
            onSelect: (item) => {
                lastProductSelection = item;
                markDirty();
            },
            onInput: (selectedId) => {
                if (selectedId) {
                    lastProductSelection = productCombobox?.getSelectedItem() ?? lastProductSelection;
                }
                markDirty();
            },
        });
        const activityCombobox = initCatalogCombobox(tr, {
            searchSelector: '[data-item-activity-search]',
            fieldSelector: '[data-item-activity]',
            menuSelector: '[data-item-activity-menu]',
            subgroupSelector: '[data-item-activity-subgroup]',
            items: activities,
            emptyText: 'Sin actividades disponibles',
            onSelect: (item) => {
                lastActivitySelection = item;
                markDirty();
            },
            onInput: (selectedId) => {
                if (selectedId) {
                    lastActivitySelection = activityCombobox?.getSelectedItem() ?? lastActivitySelection;
                }
                markDirty();
            },
        });
        lastProductSelection = productCombobox?.getSelectedItem() ?? null;
        lastActivitySelection = activityCombobox?.getSelectedItem() ?? null;

        const syncMode = () => {
            const isActivity = getInvoiceItemMode() === 'activity';
            productWrapper?.classList.toggle('hidden', isActivity);
            activityWrapper?.classList.toggle('hidden', ! isActivity);
        };

        syncMode();

        if (quantityInput?.value) {
            const v = parseQty(quantityInput.value);
            quantityInput.value = v > 0 ? formatPrice(v) : '';
        }
        if (unitPriceInput?.value) {
            const v = parsePrice(unitPriceInput.value);
            unitPriceInput.value = v > 0 ? formatPrice(v) : '';
        }

        const updateTotal = () => {
            const qty = parseQty(quantityInput?.value) || 1;
            const price = parsePrice(unitPriceInput?.value);
            if (totalEl) totalEl.textContent = price > 0 ? '$ ' + formatPrice(qty * price) : '-';
            updateInvoiceTotal();
        };

        quantityInput?.addEventListener('input', () => {
            const v = parseQty(quantityInput.value);
            quantityInput.value = v > 0 ? formatPrice(v) : '';
            updateTotal();
            markDirty();
        });
        unitPriceInput?.addEventListener('input', () => {
            const v = parsePrice(unitPriceInput.value);
            unitPriceInput.value = v > 0 ? formatPrice(v) : '';
            updateTotal();
            markDirty();
        });

        deleteBtn?.addEventListener('click', async () => {
            if (isNew) {
                tr.remove();
                updateInvoiceTotal();
                return;
            }

            if (! confirm('Deseas archivar este item? Esta accion no se puede deshacer.')) return;
            deleteBtn.disabled = true;
            try {
                await window.axios.delete(deleteUrl, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                await refreshInvoicePage('Item archivado correctamente.');
            } catch {
                deleteBtn.disabled = false;
                window.dispatchEvent(new CustomEvent('crud-toast', {
                    detail: { message: 'No fue posible archivar el item.', type: 'error' },
                }));
            }
        });

        tr._invoiceRow = {
            isNew,
            isDirty: () => dirty,
            markDirty,
            syncMode,
            isActivity: () => getInvoiceItemMode() === 'activity',
            getProductId: () => productCombobox?.getSelectedId() ?? '',
            getActivityId: () => activityCombobox?.getSelectedId() ?? '',
            getQuantity: () => {
                const v = parseQty(quantityInput?.value);
                return v > 0 ? v : null;
            },
            getUnitPrice: () => parsePrice(unitPriceInput?.value),
            getUpdateUrl: () => updateUrl,
            getDate: () => itemDate,
        };

        updateTotal();
    };

    tbody.querySelectorAll('tr[data-item-id]').forEach((tr) => attachRow(tr, false));

    const buildNewRow = () => {
        const tr = document.createElement('tr');
        tr.dataset.inlineRow = 'true';
        tr.className = 'border-b border-sky-100 bg-sky-50/40';
        tr.innerHTML = `
            <td class="px-6 py-2.5 sm:px-8">
                <div data-item-product-wrapper>
                    <div class="relative">
                        <input type="text" data-item-product-search placeholder="Buscar producto..." autocomplete="off" class="block w-full min-w-[160px] rounded-xl border-stone-300 text-sm shadow-sm focus:border-stone-900 focus:ring-stone-900">
                        <input type="hidden" data-item-product value="">
                        <div data-item-product-menu class="hidden max-h-52 overflow-y-auto rounded-xl border border-stone-200 bg-white p-1 shadow-xl"></div>
                    </div>
                    <div class="mt-0.5 hidden text-xs text-stone-400" data-item-product-subgroup></div>
                </div>
                <div class="hidden" data-item-activity-wrapper>
                    <div class="relative">
                        <input type="text" data-item-activity-search placeholder="Buscar actividad..." autocomplete="off" class="block w-full min-w-[160px] rounded-xl border-stone-300 text-sm shadow-sm focus:border-stone-900 focus:ring-stone-900">
                        <input type="hidden" data-item-activity value="">
                        <div data-item-activity-menu class="hidden max-h-52 overflow-y-auto rounded-xl border border-stone-200 bg-white p-1 shadow-xl"></div>
                    </div>
                    <div class="mt-0.5 hidden text-xs text-stone-400" data-item-activity-subgroup></div>
                </div>
            </td>
            <td class="px-4 py-2.5">
                <input type="text" inputmode="numeric" data-item-quantity placeholder="1" class="block w-20 rounded-xl border-stone-300 text-right text-sm shadow-sm focus:border-stone-900 focus:ring-stone-900">
            </td>
            <td class="px-4 py-2.5">
                <input type="text" data-item-unit-price inputmode="decimal" placeholder="0" class="block w-28 rounded-xl border-stone-300 text-right text-sm shadow-sm focus:border-stone-900 focus:ring-stone-900">
            </td>
            <td class="whitespace-nowrap px-4 py-2.5 text-right text-sm font-semibold text-stone-700" data-item-total>-</td>
            <td class="py-2.5 pl-8 pr-4 sm:pr-6">
                <button type="button" data-item-delete class="rounded-xl border border-rose-200 p-1.5 text-rose-600 transition hover:bg-rose-50" title="Eliminar fila">${trashSvg}</button>
            </td>`;

        tbody.appendChild(tr);
        attachRow(tr, true);
        tr.querySelector(getInvoiceItemMode() === 'activity' ? '[data-item-activity-search]' : '[data-item-product-search]')?.focus();
    };

    itemModeSelect?.addEventListener('change', () => {
        tbody.querySelectorAll('tr[data-item-id], tr[data-inline-row]').forEach((tr) => {
            if (! tr._invoiceRow) return;
            tr._invoiceRow.syncMode?.();
            tr._invoiceRow.markDirty?.();
        });
    });

    if (addBtn && addBtn.dataset.inlineInitialized !== 'true') {
        addBtn.dataset.inlineInitialized = 'true';
        addBtn.addEventListener('click', buildNewRow);
    }

    if (saveAllBtn && saveAllBtn.dataset.inlineInitialized !== 'true') {
        saveAllBtn.dataset.inlineInitialized = 'true';
        saveAllBtn.addEventListener('click', async () => {
            const rows = [...tbody.querySelectorAll('tr[data-item-id], tr[data-inline-row]')].filter((tr) => tr._invoiceRow);
            const currentMode = getInvoiceItemMode();
            const invalidRows = rows.filter((tr) => {
                const r = tr._invoiceRow;
                if (! r.isNew && ! r.isDirty()) return false;
                const hasCatalog = currentMode === 'activity' ? Boolean(r.getActivityId()) : Boolean(r.getProductId());
                const hasPrice = r.getUnitPrice() > 0;
                const hasQty = r.getQuantity() !== null;
                const hasAny = hasCatalog || hasPrice || hasQty;
                return hasAny && ! (hasCatalog && hasPrice);
            });

            if (invalidRows.length > 0) {
                window.dispatchEvent(new CustomEvent('crud-toast', {
                    detail: { message: `Hay filas incompletas: selecciona ${currentMode === 'activity' ? 'actividad' : 'producto'} y valor unitario.`, type: 'error' },
                }));
                return;
            }

            saveAllBtn.disabled = true;

            try {
                const dateKey = transactionType === 'purchase' ? 'purchase_date' : 'expense_date';
                const today = new Date().toISOString().split('T')[0];
                const promises = [];

                for (const tr of rows) {
                    const r = tr._invoiceRow;
                    const isActivityMode = currentMode === 'activity';
                    const payloadRow = {
                        project_id: projectId,
                        provider_id: providerId || null,
                        invoice_id: invoiceId,
                        product_id: isActivityMode ? null : (r.getProductId() || null),
                        activity_id: isActivityMode ? (r.getActivityId() || null) : null,
                        is_activity: isActivityMode,
                        unit_price: r.getUnitPrice(),
                        quantity: r.getQuantity(),
                    };

                    if (r.isNew) {
                        if ((! payloadRow.product_id && ! payloadRow.activity_id) || payloadRow.unit_price <= 0 || ! storeUrl) continue;
                        promises.push(window.axios.post(storeUrl, {
                            ...payloadRow,
                            [dateKey]: today,
                        }, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }));
                    } else if (r.isDirty()) {
                        promises.push(window.axios.patch(r.getUpdateUrl(), {
                            ...payloadRow,
                            [dateKey]: r.getDate() || today,
                        }, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }));
                    }
                }

                if (promises.length === 0) {
                    window.dispatchEvent(new CustomEvent('crud-toast', {
                        detail: { message: 'No hay cambios para guardar.' },
                    }));
                    saveAllBtn.disabled = false;
                    return;
                }

                await Promise.all(promises);
                await refreshInvoicePage('Factura guardada correctamente.');
            } catch (error) {
                saveAllBtn.disabled = false;
                const raw = Object.values(error.response?.data?.errors ?? {}).flat()[0] || error.response?.data?.message;
                window.dispatchEvent(new CustomEvent('crud-toast', { detail: { message: humanizeError(raw), type: 'error' } }));
            }
        });
    }
};

window.initializeProjectStructureSorting();
window.initializeInvoiceAttachmentForms?.();
window.initializeAssetAttachmentForms?.();
window.initializeInvoiceShowForms?.();
window.initializeInvoiceInlineRows?.();

Alpine.start();
