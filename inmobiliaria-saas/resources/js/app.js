import './bootstrap';

import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

window.Alpine = Alpine;
window.Chart = Chart;

Alpine.data('crudTable', (config = {}) => ({
    modalOpen: false,
    modalTitle: '',
    modalHtml: '',
    modalUrl: '',
    nestedModalOpen: false,
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

        window.addEventListener('crud-toast', (event) => {
            this.showToast(event.detail?.message, event.detail?.type ?? 'success');
        });

        window.addEventListener('asset-type-manager-closed', (event) => {
            this.refreshOpenModalPreservingForm(event.detail?.values ?? null, true);
        });

        window.addEventListener('asset-type-manager-opened', () => {
            if (this.modalOpen) {
                this.nestedModalOpen = true;
            }
        });
    },

    shouldReloadAfterMutation() {
        return this.config.reloadOnMutate === true;
    },

    reloadCurrentPage() {
        window.location.assign(window.location.href);
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
                    window.initializeAssetForms?.(this.$refs.modalContent);
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

    closeModal() {
        this.modalOpen = false;
        this.modalTitle = '';
        this.modalHtml = '';
        this.modalUrl = '';
        this.nestedModalOpen = false;
        this.error = null;
    },

    async refreshOpenModalPreservingForm(values = null, revealWhenDone = false) {
        if (! this.modalOpen || ! this.modalUrl || this.loading) {
            if (revealWhenDone) {
                this.nestedModalOpen = false;
            }

            return;
        }

        const preservedValues = values ?? this.collectModalFormValues();

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
                window.initializeAssetForms?.(this.$refs.modalContent);

                window.requestAnimationFrame(() => {
                    this.restoreModalFormValues(preservedValues);
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

        const openStructureModal = form.querySelector('[data-expense-structure-modal]:not(.hidden)');

        if (openStructureModal && openStructureModal.contains(document.activeElement)) {
            return;
        }

        this.clearFormErrors(form);
        this.saving = true;
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
                this.reloadCurrentPage();
                return;
            }

            this.applyRowChange(response.data);
            form.reset();
            this.closeModal();
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
        }
    },

    async handleClick(event) {
        const button = event.target.closest('[data-action]');

        if (! button) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

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
            const response = await window.axios.patch(button.dataset.url, {
                status: nextStatus,
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

            if (this.shouldReloadAfterMutation()) {
                this.reloadCurrentPage();
                return;
            }

            this.applyRowChange(response.data);

            if (! response.data.row_html) {
                this.removeRow(response.data.id);
            }

            this.showToast(response.data.message ?? 'Registro eliminado correctamente.');
        } catch (error) {
            this.showToast(this.resolveErrorMessage(error, 'No fue posible eliminar el registro.'), 'error');
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

        if (payload.modal_html) {
            this.modalHtml = payload.modal_html;
            this.$nextTick(() => {
                if (this.$refs.modalContent) {
                    Alpine.initTree(this.$refs.modalContent);
                    window.initializeExpenseForms?.(this.$refs.modalContent);
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

Alpine.data('assetTypeManager', (config = {}) => ({
    types: config.types ?? [],
    selectedTypeId: String(config.selectedTypeId ?? ''),
    previousTypeId: String(config.selectedTypeId ?? ''),
    companyId: String(config.initialCompanyId ?? ''),
    entityName: config.entityName ?? 'activo',
    storeUrl: config.storeUrl,
    indexUrl: config.indexUrl,
    managerOpen: false,
    managerSaving: false,
    managerLoading: false,
    managerError: '',
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
        if (event.target.value === '__manage__') {
            this.selectedTypeId = this.previousTypeId || '';
            this.openManager();
            return;
        }

        this.previousTypeId = this.selectedTypeId;
    },

    openManager() {
        this.managerOpen = true;
        this.managerError = '';
        this.resetDraft();
        window.dispatchEvent(new CustomEvent('asset-type-manager-opened'));
        this.$nextTick(() => {
            this.loadTypes(true);
        });
    },

    closeManager() {
        const form = this.$root.closest('form[data-ajax-form]');
        const values = form
            ? Array.from(form.elements)
                .filter((field) => field.name && field.type !== 'file')
                .map((field) => ({
                    name: field.name,
                    type: field.type,
                    value: field.value,
                    checked: field.checked,
                }))
            : [];

        this.managerOpen = false;
        this.resetDraft();
        window.dispatchEvent(new CustomEvent('asset-type-manager-closed', {
            detail: { values },
        }));
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

            this.applyTypes(response.data.types ?? []);

            if (! this.draft.id) {
                const created = this.types.find((type) => type.name.toLowerCase() === payload.name.toLowerCase());
                if (created?.status === 'active') {
                    this.selectedTypeId = String(created.id);
                    this.previousTypeId = this.selectedTypeId;
                }
            }

            this.resetDraft();
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

        if (! type?.update_url) {
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
            this.selectedTypeId = this.activeTypes.length > 0 ? String(this.activeTypes[0].id) : '';
        }

        this.previousTypeId = this.selectedTypeId;
    },

    async deleteType(type) {
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
}));

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

    event.preventDefault();
    event.stopPropagation();

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

window.initializeProjectStructureSorting();

Alpine.start();
