<form
    method="POST"
    action="{{ $action }}"
    data-ajax-form
    data-transaction-form
    data-transaction-type="{{ $isPurchase ? 'purchase' : 'expense' }}"
    class="flex h-full min-h-0 flex-col gap-4 overflow-hidden sm:gap-6"
>
    @csrf

    <script type="application/json" data-expense-payload>{!! json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
    <script type="application/json" data-expense-selected>{!! json_encode($selected, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>

    <input type="hidden" name="project_id" value="{{ $invoice->project_id }}">
    <input type="hidden" name="provider_id" value="{{ $invoice->provider_id }}">
    <input type="hidden" name="invoice_id" value="{{ $invoice->id }}">

    <div class="min-h-0 flex-1 overflow-y-auto pr-1">
        <div class="grid gap-6 md:grid-cols-2">
            <div>
                <x-input-label for="expense_date" :value="'Fecha'" />
                <x-text-input
                    id="expense_date"
                    name="expense_date"
                    type="date"
                    class="mt-1 block w-full"
                    :value="now()->toDateString()"
                    required
                />
                <p class="mt-2 hidden text-sm text-rose-600" data-error-for="expense_date"></p>
            </div>

            <div class="md:col-span-2">
                <label class="inline-flex items-center gap-3 rounded-2xl border border-stone-200 px-4 py-3 text-sm text-stone-700">
                    <input type="checkbox" name="is_activity" value="1" data-transaction-is-activity class="rounded border-stone-300 text-stone-900 shadow-sm focus:ring-stone-900">
                    <span>Es una actividad</span>
                </label>
            </div>

            <div class="relative" data-transaction-product-wrapper>
                <x-input-label for="product_search" :value="'Producto'" />
                <div class="relative mt-1">
                    <input id="product_search" data-transaction-product-search class="block w-full rounded-2xl border-stone-300 pr-8 shadow-sm focus:border-stone-900 focus:ring-stone-900" autocomplete="off">
                    <button type="button" data-transaction-product-clear tabindex="-1" title="Limpiar" class="absolute right-2.5 top-1/2 -translate-y-1/2 hidden text-stone-400 transition hover:text-stone-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                    </button>
                </div>
                <input type="hidden" name="product_id" data-transaction-product>
                <div data-transaction-product-menu class="absolute left-0 right-0 z-[70] mt-2 hidden max-h-64 overflow-y-auto rounded-2xl border border-stone-200 bg-white p-1 shadow-xl"></div>
                <p class="mt-2 hidden text-sm text-rose-600" data-error-for="product_id"></p>
            </div>

            <div class="relative hidden" data-transaction-activity-wrapper>
                <x-input-label for="activity_search" :value="'Actividad'" />
                <div class="relative mt-1">
                    <input id="activity_search" data-transaction-activity-search class="block w-full rounded-2xl border-stone-300 pr-8 shadow-sm focus:border-stone-900 focus:ring-stone-900" autocomplete="off">
                    <button type="button" data-transaction-activity-clear tabindex="-1" title="Limpiar" class="absolute right-2.5 top-1/2 -translate-y-1/2 hidden text-stone-400 transition hover:text-stone-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                    </button>
                </div>
                <input type="hidden" name="activity_id" data-transaction-activity>
                <div data-transaction-activity-menu class="absolute left-0 right-0 z-[70] mt-2 hidden max-h-64 overflow-y-auto rounded-2xl border border-stone-200 bg-white p-1 shadow-xl"></div>
                <p class="mt-2 hidden text-sm text-rose-600" data-error-for="activity_id"></p>
            </div>

            <div>
                <x-input-label for="unit_price" :value="'Valor unitario'" />
                <x-text-input
                    id="unit_price"
                    name="unit_price"
                    type="text"
                    inputmode="decimal"
                    autocomplete="off"
                    class="mt-1 block w-full"
                    value="0"
                    data-transaction-unit-price
                    required
                />
                <p class="mt-2 hidden text-sm text-rose-600" data-error-for="unit_price"></p>
            </div>

            <div>
                <x-input-label for="quantity" :value="'Cantidad (opcional)'" />
                <x-text-input
                    id="quantity"
                    name="quantity"
                    type="number"
                    inputmode="decimal"
                    step="any"
                    min="0"
                    class="mt-1 block w-full"
                    placeholder="1"
                    data-transaction-quantity
                />
                <div class="mt-2 hidden rounded-2xl border border-stone-200 bg-stone-50 px-3 py-2.5" data-transaction-total-preview></div>
                <p class="mt-2 hidden text-sm text-rose-600" data-error-for="quantity"></p>
            </div>

            <div class="md:col-span-2">
                <x-input-label for="description" :value="'Descripción (opcional)'" />
                <textarea id="description" name="description" rows="2" class="mt-1 block w-full rounded-2xl border-stone-300 shadow-sm focus:border-stone-900 focus:ring-stone-900"></textarea>
                <p class="mt-2 hidden text-sm text-rose-600" data-error-for="description"></p>
            </div>
        </div>
    </div>

    <x-modal-footer>
        <button type="submit" class="app-save-button disabled:cursor-wait disabled:opacity-60">
            Agregar ítem
        </button>
    </x-modal-footer>
</form>
