@php
    $expenseFormSelected = [
        'project_id' => $expense->project_id,
        'provider_id' => $expense->provider_id,
        'invoice_id' => $expense->invoice_id,
        'product_id' => $expense->product_id,
        'unit_price' => $expense->unit_price ?? $expense->subtotal_amount ?? 0,
        'quantity' => $expense->quantity,
    ];

    $projects = $payload['projects'] ?? [];
    $singleProject = count($projects) === 1 ? $projects[0] : null;
@endphp

<form
    method="POST"
    action="{{ $action }}"
    data-ajax-form
    data-transaction-form
    data-transaction-type="{{ $payload['transactionType'] ?? 'expense' }}"
    class="flex h-full min-h-0 flex-col gap-4 overflow-hidden sm:gap-6"
>
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <script type="application/json" data-expense-payload>{!! json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
    <script type="application/json" data-expense-selected>{!! json_encode($expenseFormSelected, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>

    <div class="min-h-0 flex-1 overflow-y-auto pr-1">
        <div class="grid gap-6 md:grid-cols-2">
            <div class="md:col-span-2">
                <x-input-label for="project_id" :value="'Proyecto'" />
                @if ($singleProject)
                    <input type="hidden" name="project_id" value="{{ $singleProject['id'] }}" data-expense-project>
                    <div class="mt-1 rounded-2xl border border-stone-200 bg-stone-50 px-4 py-3 text-sm text-stone-700">
                        {{ $singleProject['name'] }}
                    </div>
                @else
                    <x-clearable-select id="project_id" name="project_id" data-expense-project :selected="(string) ($expense->project_id ?? '')">
                        <option value="">Selecciona un proyecto</option>
                        @foreach ($projects as $projectOption)
                            <option value="{{ $projectOption['id'] }}">{{ $projectOption['name'] }}</option>
                        @endforeach
                    </x-clearable-select>
                @endif
                <p class="mt-2 hidden text-sm text-rose-600" data-error-for="project_id"></p>
            </div>

            <input
                id="expense_date"
                name="expense_date"
                type="hidden"
                value="{{ $expense->exists ? (optional($expense->expense_date)->format('Y-m-d') ?: $expense->expense_date) : now()->toDateString() }}"
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
                <x-input-label for="invoice_search" :value="'Factura (opcional)'" />
                <input id="invoice_search" data-transaction-invoice-search class="mt-1 block w-full rounded-2xl border-stone-300 shadow-sm focus:border-stone-900 focus:ring-stone-900" autocomplete="off" placeholder="Sin factura">
                <input type="hidden" name="invoice_id" data-transaction-invoice>
                <div data-transaction-invoice-menu class="absolute left-0 right-0 z-[70] mt-2 hidden max-h-64 overflow-y-auto rounded-2xl border border-stone-200 bg-white p-1 shadow-xl"></div>
                <p class="mt-2 hidden text-sm text-rose-600" data-error-for="invoice_id"></p>
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
                    :value="$expense->unit_price ?? $expense->subtotal_amount ?? 0"
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
                    :value="$expense->quantity"
                    data-transaction-quantity
                    placeholder="1"
                />
                <div class="mt-2 hidden rounded-2xl border border-stone-200 bg-stone-50 px-3 py-2.5" data-transaction-total-preview></div>
                <p class="mt-2 hidden text-sm text-rose-600" data-error-for="quantity"></p>
            </div>

            <div class="md:col-span-2">
                <x-input-label for="description" :value="'Descripción (opcional)'" />
                <textarea id="description" name="description" rows="2" class="mt-1 block w-full rounded-2xl border-stone-300 shadow-sm focus:border-stone-900 focus:ring-stone-900">{{ $expense->description }}</textarea>
                <p class="mt-2 hidden text-sm text-rose-600" data-error-for="description"></p>
            </div>
        </div>
    </div>

    <x-modal-footer>
            <button type="submit" class="app-save-button disabled:cursor-wait disabled:opacity-60">
                {{ $expense->exists ? 'Actualizar gasto' : 'Crear gasto' }}
            </button>
    </x-modal-footer>
</form>
