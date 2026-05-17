<x-app-layout
    x-data="{
        ...crudTable({ reloadOnMutate: true, flash: {{ \Illuminate\Support\Js::from(session('status')) }} }),
        previewOpen: false,
        previewUrl: '',
        previewType: '',
        previewName: '',
    }"
    x-on:click="handleClick($event)"
>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ $backUrl }}" class="inline-flex shrink-0 items-center gap-1.5 rounded-2xl border border-stone-200 px-3 py-2 text-sm font-medium text-stone-600 transition hover:bg-stone-50 hover:text-stone-900">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" /></svg>
                {{ $isPurchase ? 'Compras' : 'Gastos' }}
            </a>
            <span class="text-sm text-stone-400">{{ $isPurchase ? 'Factura de compras' : 'Factura de gastos' }}</span>
            <button
                type="button"
                data-action="invoice-status"
                data-url="{{ route('invoices.status', $invoice) }}"
                data-current-status="{{ $invoice->status }}"
                title="{{ $invoice->status === 'open' ? 'Clic para cerrar' : 'Clic para reabrir' }}"
            >
                <x-status-badge :value="$invoice->status" class="cursor-pointer transition hover:opacity-75" />
            </button>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8" data-invoice-page-root>
            <div class="overflow-hidden rounded-3xl border border-stone-200 bg-white shadow-sm print:shadow-none">
                <div class="border-b border-stone-200 bg-stone-50 px-6 py-5 sm:px-8">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-semibold uppercase tracking-widest text-stone-400">
                                {{ $isPurchase ? 'Factura de compras' : 'Factura de gastos' }}
                            </p>
                            <input
                                type="text"
                                value="{{ $invoice->invoice_number }}"
                                placeholder="Sin referencia"
                                data-invoice-show-field="invoice_number"
                                data-invoice-save-url="{{ route('invoices.update', $invoice) }}"
                                class="mt-1 block w-full rounded-2xl border border-stone-300 bg-white px-3 py-2 text-sm text-stone-900 placeholder:text-stone-400 shadow-sm focus:border-stone-900 focus:outline-none focus:ring-stone-900"
                            >
                        </div>
                        <input
                            type="date"
                            value="{{ $invoice->invoice_date?->format('Y-m-d') }}"
                            data-invoice-show-field="invoice_date"
                            data-invoice-save-url="{{ route('invoices.update', $invoice) }}"
                            class="mt-6 shrink-0 rounded-xl border border-stone-200 bg-white px-2.5 py-1.5 text-sm text-stone-600 shadow-sm focus:border-stone-900 focus:outline-none focus:ring-0"
                        >
                    </div>
                </div>

                <div
                    class="grid divide-y divide-stone-100 border-b border-stone-200 sm:grid-cols-2 sm:divide-x sm:divide-y-0"
                    data-invoice-show-providers-root
                    data-invoice-save-url="{{ route('invoices.update', $invoice) }}"
                >
                    <script type="application/json" data-invoice-show-providers>{!! json_encode(
                        $providers->map(fn ($p) => ['id' => $p->id, 'name' => $p->name])->values(),
                        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG
                    ) !!}</script>

                    <div class="px-6 py-4 sm:px-8">
                        <p class="text-xs font-semibold uppercase tracking-widest text-stone-400">Proveedor</p>
                        <div class="relative mt-2">
                            <input
                                type="text"
                                data-invoice-show-provider-search
                                value="{{ $invoice->provider?->name }}"
                                placeholder="Buscar proveedor..."
                                autocomplete="off"
                                class="block w-full rounded-2xl border-stone-300 pr-8 text-sm shadow-sm focus:border-stone-900 focus:ring-stone-900"
                            >
                            <button
                                type="button"
                                data-invoice-show-provider-clear
                                tabindex="-1"
                                title="Limpiar"
                                class="{{ $invoice->provider_id ? '' : 'hidden ' }}absolute right-2.5 top-1/2 -translate-y-1/2 text-stone-400 transition hover:text-stone-700"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                            </button>
                            <input type="hidden" data-invoice-show-provider value="{{ $invoice->provider_id }}">
                            <div data-invoice-show-provider-menu class="absolute left-0 right-0 z-[70] mt-1 hidden max-h-56 overflow-y-auto rounded-2xl border border-stone-200 bg-white p-1 shadow-xl"></div>
                        </div>
                    </div>

                    <div class="px-6 py-4 sm:px-8">
                        <p class="text-xs font-semibold uppercase tracking-widest text-stone-400">Proyecto</p>
                        <select
                            data-invoice-show-field="project_id"
                            data-invoice-save-url="{{ route('invoices.update', $invoice) }}"
                            class="mt-2 block w-full rounded-2xl border-stone-300 text-sm shadow-sm focus:border-stone-900 focus:ring-stone-900"
                        >
                            @foreach ($projects as $project)
                                <option value="{{ $project->id }}" {{ $project->id === $invoice->project_id ? 'selected' : '' }}>
                                    {{ $project->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                @if ($invoice->description)
                    <div class="border-b border-stone-200 px-6 py-3 sm:px-8">
                        <p class="text-xs font-semibold uppercase tracking-widest text-stone-400">Observacion</p>
                        <p class="mt-1 text-sm text-stone-600">{{ $invoice->description }}</p>
                    </div>
                @endif

                @php $isOpen = $invoice->status === 'open'; @endphp
                <script type="application/json" data-invoice-products>{!! json_encode($products, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
                <script type="application/json" data-invoice-activities>{!! json_encode($activities, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>

                @unless ($isOpen)
                    <div class="flex items-center gap-2 border-b border-amber-100 bg-amber-50 px-6 py-3 sm:px-8">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-amber-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" /></svg>
                        <p class="text-sm text-amber-700">Factura cerrada. Abrela para agregar o modificar items.</p>
                    </div>
                @endunless

                <div class="overflow-x-auto" data-invoice-items-x-scroll>
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-stone-200 bg-stone-50 text-left">
                                @if ($isOpen)
                                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-stone-500 sm:px-6">¿Actividad?</th>
                                @endif
                                <th class="px-6 py-3 text-xs font-semibold uppercase tracking-wide text-stone-500 sm:px-8">Producto / Actividad</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-stone-500">Cantidad</th>
                                <th class="whitespace-nowrap px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-stone-500">V. Unit.</th>
                                <th class="whitespace-nowrap px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-stone-500">Total</th>
                                @if ($isOpen)
                                    <th class="px-4 py-3 sm:pr-6"></th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100" x-ref="tbody" data-invoice-tbody {{ $isOpen ? 'data-invoice-editable' : '' }}>
                            @forelse ($items as $item)
                                @php($catalogItem = $item->activity ?: $item->product)
                                @php($catalogSubgroup = $item->activity?->subgroup ?: $item->product?->subgroup)
                                <tr
                                    data-item-id="{{ $item->id }}"
                                    @if ($isOpen)
                                        data-item-update-url="{{ $isPurchase ? route('purchases.update', $item) : route('expenses.update', $item) }}"
                                        data-item-delete-url="{{ $isPurchase ? route('purchases.destroy', $item) : route('expenses.destroy', $item) }}"
                                        data-item-date="{{ ($isPurchase ? $item->purchase_date : $item->expense_date)?->format('Y-m-d') }}"
                                    @endif
                                    class="border-b border-stone-100 transition-colors hover:bg-stone-50/30"
                                >
                                    @if ($isOpen)
                                        <td class="px-4 py-2.5 text-center align-top sm:px-6">
                                            <label class="inline-flex cursor-pointer items-center" data-item-activity-toggle>
                                                <input type="checkbox" data-item-is-activity class="sr-only" {{ $item->activity_id ? 'checked' : '' }}>
                                                <span class="inline-flex min-w-[3.5rem] justify-center rounded-full border px-3 py-1 text-xs font-semibold transition {{ $item->activity_id ? 'border-emerald-200 bg-emerald-100 text-emerald-800' : 'border-rose-200 bg-rose-100 text-rose-700' }}" data-item-activity-toggle-label>
                                                    {{ $item->activity_id ? 'Si' : 'No' }}
                                                </span>
                                            </label>
                                        </td>
                                    @endif
                                    <td class="px-6 py-2.5 sm:px-8">
                                        @if ($isOpen)
                                            <div data-item-product-wrapper class="{{ $item->activity_id ? 'hidden' : '' }}">
                                                <div class="relative">
                                                    <input
                                                        type="text"
                                                        data-item-product-search
                                                        value="{{ $item->product?->name }}"
                                                        placeholder="Buscar producto..."
                                                        autocomplete="off"
                                                        class="block w-full min-w-[160px] rounded-xl border-stone-300 text-sm shadow-sm focus:border-stone-900 focus:ring-stone-900"
                                                    >
                                                    <input type="hidden" data-item-product value="{{ $item->product_id }}">
                                                    <div data-item-product-menu class="hidden max-h-52 overflow-y-auto rounded-xl border border-stone-200 bg-white p-1 shadow-xl"></div>
                                                </div>
                                                <div class="mt-0.5 text-xs text-stone-400{{ $item->product?->subgroup ? '' : ' hidden' }}" data-item-product-subgroup>{{ $item->product?->subgroup?->name }}</div>
                                            </div>
                                            <div data-item-activity-wrapper class="{{ $item->activity_id ? '' : 'hidden' }}">
                                                <div class="relative">
                                                    <input
                                                        type="text"
                                                        data-item-activity-search
                                                        value="{{ $item->activity?->name }}"
                                                        placeholder="Buscar actividad..."
                                                        autocomplete="off"
                                                        class="block w-full min-w-[160px] rounded-xl border-stone-300 text-sm shadow-sm focus:border-stone-900 focus:ring-stone-900"
                                                    >
                                                    <input type="hidden" data-item-activity value="{{ $item->activity_id }}">
                                                    <div data-item-activity-menu class="hidden max-h-52 overflow-y-auto rounded-xl border border-stone-200 bg-white p-1 shadow-xl"></div>
                                                </div>
                                                <div class="mt-0.5 text-xs text-stone-400{{ $item->activity?->subgroup ? '' : ' hidden' }}" data-item-activity-subgroup>{{ $item->activity?->subgroup?->name }}</div>
                                            </div>
                                        @else
                                            <div class="mb-1 text-[11px] font-semibold uppercase tracking-[0.18em] {{ $item->activity_id ? 'text-emerald-700' : 'text-rose-700' }}">
                                                {{ $item->activity_id ? 'Actividad' : 'Producto' }}
                                            </div>
                                            <div class="whitespace-nowrap font-medium text-stone-700">{{ $catalogItem?->name ?: '-' }}</div>
                                            @if ($catalogSubgroup)
                                                <div class="mt-0.5 whitespace-nowrap text-xs text-stone-400">{{ $catalogSubgroup->name }}</div>
                                            @endif
                                        @endif
                                    </td>
                                    <td class="px-4 py-2.5 @if (!$isOpen) text-right text-stone-600 @endif">
                                        @if ($isOpen)
                                            <input
                                                type="text"
                                                inputmode="numeric"
                                                data-item-quantity
                                                value="{{ $item->quantity ? number_format((int) $item->quantity, 0, ',', '.') : '' }}"
                                                placeholder="1"
                                                class="block w-20 rounded-xl border-stone-300 text-right text-sm shadow-sm focus:border-stone-900 focus:ring-stone-900"
                                            >
                                        @else
                                            {{ $item->quantity ? number_format((int) $item->quantity, 0, ',', '.') : '-' }}
                                        @endif
                                    </td>
                                    <td class="px-4 py-2.5 @if (!$isOpen) whitespace-nowrap text-right text-stone-600 @endif">
                                        @if ($isOpen)
                                            <input
                                                type="text"
                                                data-item-unit-price
                                                value="{{ $item->unit_price > 0 ? number_format((float) $item->unit_price, 0, ',', '.') : '' }}"
                                                inputmode="decimal"
                                                placeholder="0"
                                                class="block w-28 rounded-xl border-stone-300 text-right text-sm shadow-sm focus:border-stone-900 focus:ring-stone-900"
                                            >
                                        @else
                                            $ {{ number_format((float) $item->unit_price, 0, ',', '.') }}
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-2.5 text-right text-sm font-semibold text-stone-700" @if ($isOpen) data-item-total @endif>
                                        $ {{ number_format((float) $item->total_amount, 0, ',', '.') }}
                                    </td>
                                    @if ($isOpen)
                                        <td class="py-2.5 pl-8 pr-4 sm:pr-6">
                                            <button
                                                type="button"
                                                data-item-delete
                                                class="rounded-xl border border-rose-200 p-1.5 text-rose-600 transition hover:bg-rose-50"
                                                title="{{ $isPurchase ? 'Archivar compra' : 'Archivar gasto' }}"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.257 3.099c.366-.446.911-.699 1.486-.699h.514c.575 0 1.12.253 1.486.699L12.85 4H16a1 1 0 110 2h-1l-.867 10.142A2 2 0 0112.14 18H7.86a2 2 0 01-1.993-1.858L5 6H4a1 1 0 010-2h3.15l1.107-.901zM8 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm4-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                                            </button>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $isOpen ? 6 : 4 }}" class="px-6 py-10 text-center text-stone-400 sm:px-8">
                                        Sin {{ $typeLabel }} registrados.
                                        @if ($isOpen)
                                            <span class="mt-1 block text-xs">Usa el boton <strong>+ Agregar</strong> para anadir el primero.</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="flex items-center justify-between border-t-2 border-stone-200 px-6 py-4 sm:px-8">
                    @if ($isOpen)
                        <div class="flex flex-col gap-2">
                            <button
                                type="button"
                                data-invoice-add-row
                                data-store-url="{{ $isPurchase ? route('purchases.store') : route('expenses.store') }}"
                                data-transaction-type="{{ $isPurchase ? 'purchase' : 'expense' }}"
                                data-project-id="{{ $invoice->project_id }}"
                                data-provider-id="{{ $invoice->provider_id }}"
                                data-invoice-id="{{ $invoice->id }}"
                                class="app-create-icon-button rounded-2xl px-3 py-1.5 text-sm font-medium"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" /></svg>
                                <span>Agregar</span>
                            </button>
                            <button
                                type="button"
                                data-invoice-save-all
                                class="rounded-2xl bg-stone-800 px-3 py-1.5 text-sm font-medium text-white transition hover:bg-stone-700 disabled:opacity-60"
                            >
                                Guardar factura
                            </button>
                        </div>
                    @else
                        <div></div>
                    @endif
                    <div class="text-right">
                        <p class="text-xs font-semibold uppercase tracking-widest text-stone-400">Total factura</p>
                        <p class="text-2xl font-bold text-stone-900" data-invoice-total-amount>$ {{ number_format((float) $invoice->total_amount, 0, ',', '.') }}</p>
                    </div>
                </div>
            </div>

            <div class="overflow-hidden rounded-3xl border border-stone-200 bg-white shadow-sm">
                <div class="flex items-center justify-between gap-3 border-b border-stone-200 px-6 py-4 sm:px-8">
                    <h3 class="text-sm font-semibold text-stone-900">Archivos adjuntos</h3>
                    <form data-invoice-attachment-form action="{{ route('invoices.attachments.store', $invoice) }}">
                        <input id="invoice_files_main" name="files[]" type="file" multiple accept="image/*,video/*,application/pdf" class="sr-only" data-invoice-file-input>
                        <label for="invoice_files_main" class="app-create-icon-button cursor-pointer rounded-2xl px-3 py-2 text-sm font-medium" title="Subir archivo">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" /></svg>
                            <span>Adjuntar</span>
                        </label>
                    </form>
                </div>
                <div class="p-6 sm:p-8" x-ref="attachments" data-invoice-attachments-root>
                    @include('invoices._attachments', ['invoice' => $invoice])
                </div>
            </div>

            <div class="flex items-center justify-between pb-2">
                <span class="text-xs text-stone-400">
                    {{ $items->count() }} {{ $typeLabel }} · Creada {{ $invoice->created_at?->format('d/m/Y') }}
                </span>
                <form
                    method="POST"
                    action="{{ route('invoices.destroy', $invoice) }}"
                    onsubmit="return confirm('Confirmas archivar esta factura? Se archivaran todos sus items.')"
                >
                    @csrf @method('DELETE')
                    <button type="submit" class="rounded-xl border border-rose-200 p-2 text-rose-600 transition hover:bg-rose-50" title="Archivar factura">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.257 3.099c.366-.446.911-.699 1.486-.699h.514c.575 0 1.12.253 1.486.699L12.85 4H16a1 1 0 110 2h-1l-.867 10.142A2 2 0 0112.14 18H7.86a2 2 0 01-1.993-1.858L5 6H4a1 1 0 010-2h3.15l1.107-.901zM8 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm4-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div
        x-show="previewOpen"
        x-cloak
        class="fixed inset-0 z-[70] flex items-center justify-center bg-black/70 p-4"
        x-on:keydown.escape.window="previewOpen = false"
    >
        <div class="max-h-[92vh] w-full max-w-5xl overflow-hidden rounded-3xl bg-white shadow-2xl">
            <div class="flex items-center justify-between gap-3 border-b border-stone-200 px-5 py-4">
                <h3 class="truncate text-sm font-semibold text-stone-900" x-text="previewName"></h3>
                <button type="button" class="rounded-2xl border border-stone-200 px-3 py-2 text-sm text-stone-700 transition hover:bg-stone-50" x-on:click="previewOpen = false">Cerrar</button>
            </div>
            <div class="flex max-h-[78vh] items-center justify-center bg-stone-950 p-3">
                <template x-if="previewType === 'image'">
                    <img :src="previewUrl" :alt="previewName" class="max-h-[74vh] max-w-full rounded-xl object-contain">
                </template>
                <template x-if="previewType === 'video'">
                    <video :src="previewUrl" class="max-h-[74vh] max-w-full rounded-xl" controls playsinline></video>
                </template>
            </div>
        </div>
    </div>

    <x-ajax-crud-modal />
    <x-ajax-crud-toast />
</x-app-layout>
