@php
    $selected = [
        'project_id' => $purchase->project_id,
        'provider_id' => $purchase->provider_id,
        'invoice_id' => $purchase->invoice_id,
        'product_id' => $purchase->product_id,
        'subtotal_amount' => $purchase->subtotal_amount ?? $purchase->total_amount ?? 0,
    ];
    $projects = $payload['projects'] ?? [];
    $singleProject = count($projects) === 1 ? $projects[0] : null;
@endphp

<form method="POST" action="{{ $action }}" data-ajax-form data-transaction-form data-transaction-type="{{ $payload['transactionType'] ?? 'purchase' }}" data-invoice-store-url="{{ $payload['invoiceStoreUrl'] ?? '' }}" class="flex h-full min-h-0 flex-col gap-4 overflow-hidden sm:gap-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <script type="application/json" data-expense-payload>{!! json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
    <script type="application/json" data-expense-selected>{!! json_encode($selected, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>

    <div class="min-h-0 flex-1 overflow-y-auto pr-1">
        <div class="grid gap-6 md:grid-cols-2">
            <div class="md:col-span-2">
                <x-input-label for="project_id" :value="'Proyecto'" />
                @if ($singleProject)
                    <input type="hidden" name="project_id" value="{{ $singleProject['id'] }}" data-expense-project>
                    <div class="mt-1 rounded-2xl border border-stone-200 bg-stone-50 px-4 py-3 text-sm text-stone-700">{{ $singleProject['name'] }}</div>
                @else
                    <select id="project_id" name="project_id" data-expense-project class="mt-1 block w-full rounded-2xl border-stone-300 shadow-sm focus:border-stone-900 focus:ring-stone-900">
                        <option value="">Selecciona un proyecto</option>
                        @foreach ($projects as $projectOption)
                            <option value="{{ $projectOption['id'] }}">{{ $projectOption['name'] }}</option>
                        @endforeach
                    </select>
                @endif
                <p class="mt-2 hidden text-sm text-rose-600" data-error-for="project_id"></p>
            </div>

            <input
                id="purchase_date"
                name="purchase_date"
                type="hidden"
                value="{{ $purchase->exists ? (optional($purchase->purchase_date)->format('Y-m-d') ?: $purchase->purchase_date) : now()->toDateString() }}"
            >

            <div class="relative">
                <x-input-label for="provider_search" :value="'Proveedor'" />
                <input id="provider_search" data-transaction-provider-search class="mt-1 block w-full rounded-2xl border-stone-300 shadow-sm focus:border-stone-900 focus:ring-stone-900" autocomplete="off" required>
                <input type="hidden" name="provider_id" data-transaction-provider>
                <div data-transaction-provider-menu class="absolute left-0 right-0 z-[70] mt-2 hidden max-h-64 overflow-y-auto rounded-2xl border border-stone-200 bg-white p-1 shadow-xl"></div>
                <p class="mt-2 hidden text-sm text-rose-600" data-error-for="provider_id"></p>
            </div>

            <div class="relative">
                <x-input-label for="product_search" :value="'Producto'" />
                <input id="product_search" data-transaction-product-search class="mt-1 block w-full rounded-2xl border-stone-300 shadow-sm focus:border-stone-900 focus:ring-stone-900" autocomplete="off" required>
                <input type="hidden" name="product_id" data-transaction-product>
                <div data-transaction-product-menu class="absolute left-0 right-0 z-[70] mt-2 hidden max-h-64 overflow-y-auto rounded-2xl border border-stone-200 bg-white p-1 shadow-xl"></div>
                <p class="mt-2 hidden text-sm text-rose-600" data-error-for="product_id"></p>
            </div>

            <div class="relative md:col-span-2">
                <div class="flex items-center justify-between gap-3">
                    <x-input-label for="invoice_search" :value="'Factura (opcional)'" />
                    <button type="button" class="app-create-button-sm" title="Crear factura" data-transaction-create-invoice>+</button>
                </div>
                <input id="invoice_search" data-transaction-invoice-search class="mt-1 block w-full rounded-2xl border-stone-300 shadow-sm focus:border-stone-900 focus:ring-stone-900" autocomplete="off" placeholder="Sin factura">
                <input type="hidden" name="invoice_id" data-transaction-invoice>
                <div data-transaction-invoice-menu class="absolute left-0 right-0 z-[70] mt-2 hidden max-h-64 overflow-y-auto rounded-2xl border border-stone-200 bg-white p-1 shadow-xl"></div>
                <p class="mt-2 hidden text-sm text-rose-600" data-error-for="invoice_id"></p>
            </div>

            <div data-transaction-invoice-create class="hidden md:col-span-2 rounded-2xl border border-stone-200 bg-stone-50 p-4">
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <x-input-label for="invoice_number" :value="'Número de factura'" />
                        <x-text-input id="invoice_number" type="text" class="mt-1 block w-full" data-invoice-number />
                    </div>
                    <div>
                        <x-input-label for="invoice_date" :value="'Fecha de factura'" />
                        <x-text-input id="invoice_date" type="date" class="mt-1 block w-full" data-invoice-date :value="now()->toDateString()" />
                    </div>
                    <div class="md:col-span-2">
                        <x-input-label for="invoice_description" :value="'Observación (opcional)'" />
                        <textarea id="invoice_description" rows="2" class="mt-1 block w-full rounded-2xl border-stone-300 shadow-sm focus:border-stone-900 focus:ring-stone-900" data-invoice-description></textarea>
                    </div>
                    <div class="flex justify-end gap-3 md:col-span-2">
                        <button type="button" class="rounded-2xl border border-stone-300 px-4 py-2 text-sm font-medium text-stone-700 transition hover:bg-white" data-invoice-cancel>Cancelar</button>
                        <button type="button" class="rounded-2xl bg-stone-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-stone-700 disabled:cursor-wait disabled:opacity-60" data-invoice-save>Crear factura</button>
                    </div>
                </div>
            </div>

            <div>
                <x-input-label for="subtotal_amount" :value="'Costo'" />
                <x-text-input id="subtotal_amount" name="subtotal_amount" type="text" inputmode="decimal" autocomplete="off" class="mt-1 block w-full" :value="$purchase->subtotal_amount ?? $purchase->total_amount ?? 0" required />
                <p class="mt-2 hidden text-sm text-rose-600" data-error-for="subtotal_amount"></p>
            </div>

            <div>
                <x-input-label for="quantity" :value="'Cantidad (opcional)'" />
                <x-text-input id="quantity" name="quantity" type="text" class="mt-1 block w-full" :value="$purchase->quantity" placeholder="Ej. 18 unidades, 6 latas" />
                <p class="mt-2 hidden text-sm text-rose-600" data-error-for="quantity"></p>
            </div>

            <div class="md:col-span-2">
                <x-input-label for="description" :value="'Descripción (opcional)'" />
                <textarea id="description" name="description" rows="2" class="mt-1 block w-full rounded-2xl border-stone-300 shadow-sm focus:border-stone-900 focus:ring-stone-900">{{ $purchase->description }}</textarea>
                <p class="mt-2 hidden text-sm text-rose-600" data-error-for="description"></p>
            </div>
        </div>
    </div>

    <div class="sticky bottom-0 z-10 mt-auto shrink-0 border-t border-stone-200 bg-white px-1 pb-[calc(env(safe-area-inset-bottom)+0.5rem)] pt-4 shadow-[0_-8px_18px_rgba(255,255,255,0.92)]">
        <div class="flex items-center justify-end gap-3">
            <button type="button" data-action="close-modal" class="rounded-2xl border border-stone-300 px-4 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-50">Cancelar</button>
            <button type="submit" class="rounded-2xl bg-stone-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-stone-700">{{ $purchase->exists ? 'Actualizar compra' : 'Crear compra' }}</button>
        </div>
    </div>
</form>
