@props(['type'])

@php
    $typeLabel     = $type === 'expense' ? 'gastos' : 'compras';
    $listUrl       = route('invoices.index', ['type' => $type]);
    $statusBaseUrl = url('invoices');
@endphp

<div
    x-data="{
        open: false,
        loading: false,
        invoices: [],
        statusFilter: '',
        get filteredInvoices() {
            return this.statusFilter
                ? this.invoices.filter(i => i.status === this.statusFilter)
                : this.invoices;
        },
        async load() {
            this.loading = true;
            try {
                const res = await fetch('{{ $listUrl }}', {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                this.invoices = data.invoices ?? [];
            } catch {
                this.invoices = [];
            } finally {
                this.loading = false;
            }
        },
        toggle() {
            this.open = !this.open;
            if (this.open) this.load();
        },
        async toggleStatus(inv) {
            const next = inv.status === 'open' ? 'closed' : 'open';
            inv.status = next;
            const res = await fetch('{{ $statusBaseUrl }}/' + inv.id + '/status', {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                },
                body: JSON.stringify({ status: next }),
            });
            if (!res.ok) inv.status = next === 'open' ? 'closed' : 'open';
        },
        openInvoice(inv) {
            if (!inv?.show_url) {
                return;
            }

            this.open = false;
            window.location.assign(inv.show_url);
        },
        fmt(v) {
            return new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 0 }).format(v);
        }
    }"
    @keydown.escape.window="if (open) { open = false; $event.stopPropagation(); }"
>
    {{-- Eye button --}}
    <button
        type="button"
        @click="toggle()"
        class="app-invoice-list-fab flex items-center justify-center rounded-full border border-stone-200 bg-white text-stone-700 shadow-lg transition hover:bg-stone-50"
        title="Ver facturas de {{ $typeLabel }}"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
            <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
        </svg>
    </button>

    {{-- Backdrop --}}
    <div
        x-show="open"
        x-cloak
        @click="open = false"
        x-transition:enter="transition duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-40 bg-black/20"
    ></div>

    {{-- Slide panel --}}
    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
        class="fixed right-0 top-0 bottom-0 z-50 flex w-full max-w-sm flex-col bg-white shadow-2xl"
    >
        {{-- Header --}}
        <div class="flex shrink-0 items-center justify-between border-b border-stone-200 px-5 py-4">
            <h2 class="text-base font-semibold text-stone-900">Facturas de {{ $typeLabel }}</h2>
            <button
                @click="open = false"
                class="rounded-xl p-1.5 text-stone-400 transition hover:bg-stone-100 hover:text-stone-700"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>

        {{-- Status filter --}}
        <div class="flex shrink-0 gap-1.5 border-b border-stone-200 px-4 py-3">
            <template x-for="[val, label] in [['', 'Todas'], ['open', 'Abiertas'], ['closed', 'Cerradas']]" :key="val">
                <button
                    @click="statusFilter = val"
                    :class="statusFilter === val
                        ? 'bg-stone-900 text-white'
                        : 'text-stone-600 hover:bg-stone-100'"
                    class="rounded-xl px-3 py-1.5 text-xs font-medium transition"
                    x-text="label"
                ></button>
            </template>
        </div>

        {{-- Invoice list --}}
        <div class="min-h-0 flex-1 overflow-y-auto">
            <div x-show="loading" class="flex items-center justify-center py-16 text-sm text-stone-400">
                Cargando…
            </div>

            <div x-show="!loading && filteredInvoices.length === 0" class="flex items-center justify-center py-16 text-sm text-stone-400">
                Sin facturas.
            </div>

            <template x-for="inv in filteredInvoices" :key="inv.id">
                <div
                    role="button"
                    tabindex="0"
                    @click="openInvoice(inv)"
                    @keydown.enter.prevent="openInvoice(inv)"
                    @keydown.space.prevent="openInvoice(inv)"
                    class="flex w-full cursor-pointer items-center gap-3 border-b border-stone-100 px-5 py-3.5 text-left transition hover:bg-stone-50 focus:outline-none focus:ring-2 focus:ring-stone-300"
                >
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-stone-900" x-text="inv.invoice_number || 'Sin número'"></p>
                        <p class="truncate text-xs text-stone-500" x-text="inv.provider_name || '—'"></p>
                        <div class="flex items-center gap-2 text-xs text-stone-400">
                            <span x-text="inv.project_name" class="truncate"></span>
                            <span x-show="inv.invoice_date" x-text="'· ' + inv.invoice_date"></span>
                        </div>
                    </div>
                    <div class="flex shrink-0 flex-col items-end gap-1.5">
                        <p class="text-sm font-semibold text-stone-900" x-text="fmt(inv.total_amount)"></p>
                        <button
                            @click.stop="toggleStatus(inv)"
                            :class="inv.status === 'open'
                                ? 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200'
                                : 'bg-stone-100 text-stone-600 hover:bg-stone-200'"
                            class="rounded-full px-2.5 py-0.5 text-xs font-medium transition"
                            x-text="inv.status === 'open' ? 'Abierta' : 'Cerrada'"
                        ></button>
                    </div>
                </div>
            </template>
        </div>

        {{-- Footer --}}
        <div class="shrink-0 border-t border-stone-200 p-3">
            <button
                @click="load()"
                :disabled="loading"
                class="w-full rounded-2xl py-2 text-xs text-stone-500 transition hover:bg-stone-100 hover:text-stone-800 disabled:opacity-50"
            >
                Actualizar lista
            </button>
        </div>
    </div>
</div>
