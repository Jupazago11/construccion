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
            this.$nextTick(() => {
                if (this.$refs.modalContent) {
                    Alpine.initTree(this.$refs.modalContent);
                    window.initializeExpenseForms?.(this.$refs.modalContent);
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
            const formData = event.submitter
                ? new FormData(form, event.submitter)
                : new FormData(form);
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

        const optionButtons = options.map((status) => {
            const isCurrent = status === currentStatus;

            return `
                <button
                    type="button"
                    data-action="pick-status"
                    data-status-value="${status}"
                    value="${status}"
                    class="rounded-2xl border px-4 py-3 text-left text-sm transition ${isCurrent
                        ? 'border-stone-900 bg-stone-900 text-white shadow-sm'
                        : 'border-stone-200 bg-white text-stone-700 hover:border-stone-300 hover:bg-stone-50'}"
                >
                    <span class="block font-medium">${this.humanizeStatus(status)}</span>
                    <span class="mt-1 block text-xs opacity-70">${isCurrent ? 'Estado actual' : 'Seleccionar este estado'}</span>
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

        if (payload.attachments_html && this.$refs.attachments) {
            this.$refs.attachments.innerHTML = payload.attachments_html;
        }

        if (payload.structure_html && this.$refs.structure) {
            this.$refs.structure.innerHTML = payload.structure_html;
            this.$nextTick(() => Alpine.initTree(this.$refs.structure));
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

    humanizeStatus(status) {
        const labels = {
            planning: 'Planeación',
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
        const categories = (payload.categories ?? []).map((item) => ({ ...item, id: String(item.id), project_id: String(item.project_id) }));
        const subcategories = (payload.subcategories ?? []).map((item) => ({
            ...item,
            id: String(item.id),
            project_id: String(item.project_id),
            category_id: String(item.category_id),
        }));
        const auxiliaries = (payload.auxiliaries ?? []).map((item) => ({
            ...item,
            id: String(item.id),
            project_id: String(item.project_id),
            category_id: String(item.category_id),
            subcategory_id: String(item.subcategory_id),
        }));
        const providers = (payload.providers ?? []).map((item) => ({ ...item, id: String(item.id), company_id: String(item.company_id) }));

        const projectField = form.querySelector('[data-expense-project]');
        const projectInfo = form.querySelector('[data-expense-project-info]');
        const categoryWrapper = form.querySelector('[data-expense-category-wrapper]');
        const categoryField = form.querySelector('[data-expense-category]');
        const categoryCards = form.querySelector('[data-expense-category-cards]');
        const subcategoryWrapper = form.querySelector('[data-expense-subcategory-wrapper]');
        const subcategoryField = form.querySelector('[data-expense-subcategory]');
        const subcategoryCards = form.querySelector('[data-expense-subcategory-cards]');
        const auxiliaryWrapper = form.querySelector('[data-expense-auxiliary-wrapper]');
        const auxiliaryField = form.querySelector('[data-expense-auxiliary]');
        const auxiliaryCards = form.querySelector('[data-expense-auxiliary-cards]');
        const providerField = form.querySelector('[data-expense-provider]');
        const subtotalField = form.querySelector('#subtotal_amount');

        const state = {
            projectId: selected.project_id ? String(selected.project_id) : '',
            categoryId: selected.category_id ? String(selected.category_id) : '',
            subcategoryId: selected.subcategory_id ? String(selected.subcategory_id) : '',
            auxiliaryId: selected.auxiliary_id ? String(selected.auxiliary_id) : '',
            providerId: selected.provider_id ? String(selected.provider_id) : '',
        };

        const debug = (label, extra = {}) => {
            console.log(`[expenseForm] ${label}`, { ...state, ...extra });
        };

        const normalizeAmount = (value) => {
            const raw = String(value ?? '').trim();

            if (raw === '') {
                return '';
            }

            if (/^\d+([.,]00)?$/.test(raw)) {
                return raw.replace(/[.,]00$/, '');
            }

            return raw.replace(/\D/g, '');
        };

        const formatAmountInput = (value) => {
            const normalized = normalizeAmount(value);

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

        const countDigits = (value) => String(value ?? '').replace(/\D/g, '').length;

        const caretFromDigits = (formattedValue, digitCount) => {
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

        const syncTotalPreview = (preserveCaret = false) => {
            if (! subtotalField) {
                return;
            }

            const rawValue = subtotalField.value;
            const selectionStart = subtotalField.selectionStart ?? rawValue.length;
            const digitsBeforeCaret = countDigits(rawValue.slice(0, selectionStart));
            const formattedValue = formatAmountInput(rawValue);

            subtotalField.value = formattedValue;

            if (preserveCaret) {
                const nextCaret = caretFromDigits(formattedValue, digitsBeforeCaret);
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

        const syncProjectInfo = () => {
            const project = getSelectedProject();

            if (! projectInfo) {
                return;
            }

            projectInfo.textContent = project
                ? `Empresa: ${project.company_name ?? 'Sin empresa'} | Estado: ${project.status}`
                : '';
            projectInfo.style.display = project ? '' : 'none';
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
                auxiliaryWrapper.style.display = availableAuxiliaries.length > 0 ? '' : 'none';
            }

            debug('syncAuxiliaries', { availableAuxiliaries });
        };

        const syncSubcategories = () => {
            const availableSubcategories = subcategories.filter((item) => (
                item.project_id === state.projectId
                && item.category_id === state.categoryId
            ));

            replaceOptions(subcategoryField, availableSubcategories, 'Selecciona una subcategoría');
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

            syncAuxiliaries();
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
            debug('syncCategories', { availableCategories });
        };

        const syncProject = () => {
            syncProjectInfo();
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

        if (subtotalField) {
            subtotalField.addEventListener('input', () => {
                syncTotalPreview(true);
                debug('subtotal:input', { subtotal: subtotalField.value });
            });

            subtotalField.addEventListener('blur', () => {
                syncTotalPreview();
            });

            form.addEventListener('submit', () => {
                subtotalField.value = normalizeAmount(subtotalField.value);
            });
        }

        syncProject();
        syncTotalPreview();
        debug('init:complete');
    });
};

Alpine.start();
