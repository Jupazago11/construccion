@php
    $expenseFormSelected = [
        'project_id' => $expense->project_id,
        'category_id' => $expense->category_id,
        'subcategory_id' => $expense->subcategory_id,
        'auxiliary_id' => $expense->auxiliary_id,
        'provider_id' => $expense->provider_id,
        'subtotal_amount' => $expense->subtotal_amount ?? $expense->total_amount ?? 0,
    ];

    $projects = $payload['projects'] ?? [];
    $singleProject = count($projects) === 1 ? $projects[0] : null;
@endphp

<form
    method="POST"
    action="{{ $action }}"
    data-ajax-form
    data-expense-form
    class="flex h-full min-h-0 flex-col gap-4 overflow-hidden sm:gap-6"
>
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <script type="application/json" data-expense-payload>{!! json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
    <script type="application/json" data-expense-selected>{!! json_encode($expenseFormSelected, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>

    <div class="min-h-0 flex-1 overflow-y-auto pr-1">
        <div class="grid gap-6 md:grid-cols-2">
            <div class="md:col-span-2">
                <x-input-label for="project_id" :value="'Proyecto'" />
                @if ($singleProject)
                    <input type="hidden" name="project_id" value="{{ $singleProject['id'] }}">
                    <div class="mt-1 rounded-2xl border border-stone-200 bg-stone-50 px-4 py-3 text-sm text-stone-700">
                        {{ $singleProject['name'] }}
                    </div>
                @else
                    <select
                        id="project_id"
                        name="project_id"
                        data-expense-project
                        class="mt-1 block w-full rounded-2xl border-stone-300 shadow-sm focus:border-stone-900 focus:ring-stone-900"
                    >
                        <option value="">Selecciona un proyecto</option>
                        @foreach ($projects as $projectOption)
                            <option value="{{ $projectOption['id'] }}">{{ $projectOption['name'] }}</option>
                        @endforeach
                    </select>
                @endif
                <p class="mt-2 text-xs text-stone-500" data-expense-project-info style="display:none;"></p>
                <p class="mt-2 hidden text-sm text-rose-600" data-error-for="project_id"></p>
            </div>

            <div class="md:col-span-2" data-expense-category-wrapper style="display:none;">
                <x-input-label for="category_id" :value="'Categoría'" />
                <select
                    id="category_id"
                    name="category_id"
                    data-expense-category
                    class="sr-only"
                >
                    <option value="">Selecciona una categoría</option>
                </select>
                <div class="mt-2 grid gap-2 sm:grid-cols-2" data-expense-category-cards></div>
                <p class="mt-2 hidden text-sm text-rose-600" data-error-for="category_id"></p>
            </div>

            <div class="md:col-span-2" data-expense-subcategory-wrapper style="display:none;">
                <x-input-label for="subcategory_id" :value="'Subcategoría'" />
                <select
                    id="subcategory_id"
                    name="subcategory_id"
                    data-expense-subcategory
                    class="sr-only"
                >
                    <option value="">Selecciona una subcategoría</option>
                </select>
                <div class="mt-2 grid gap-2 sm:grid-cols-2" data-expense-subcategory-cards></div>
                <p class="mt-2 hidden text-sm text-rose-600" data-error-for="subcategory_id"></p>
            </div>

            <div class="md:col-span-2" data-expense-auxiliary-wrapper style="display:none;">
                <x-input-label for="auxiliary_id" :value="'Auxiliar'" />
                <select
                    id="auxiliary_id"
                    name="auxiliary_id"
                    data-expense-auxiliary
                    class="sr-only"
                >
                    <option value="">Sin auxiliar</option>
                </select>
                <div class="mt-2 grid gap-2 sm:grid-cols-2" data-expense-auxiliary-cards></div>
                <p class="mt-2 hidden text-sm text-rose-600" data-error-for="auxiliary_id"></p>
            </div>

            <div>
                <x-input-label for="expense_date" :value="'Fecha del gasto'" />
                <x-text-input
                    id="expense_date"
                    name="expense_date"
                    type="date"
                    class="mt-1 block w-full"
                    :value="optional($expense->expense_date)->format('Y-m-d') ?: $expense->expense_date"
                    required
                />
                <p class="mt-2 hidden text-sm text-rose-600" data-error-for="expense_date"></p>
            </div>

            <div>
                <x-input-label for="payment_method" :value="'Método de pago'" />
                <select id="payment_method" name="payment_method" class="mt-1 block w-full rounded-2xl border-stone-300 shadow-sm focus:border-stone-900 focus:ring-stone-900">
                    <option value="">Selecciona un método</option>
                    @foreach (['cash' => 'Efectivo', 'bank_transfer' => 'Transferencia bancaria', 'credit_card' => 'Tarjeta de crédito', 'debit_card' => 'Tarjeta débito', 'other' => 'Otro'] as $value => $label)
                        <option value="{{ $value }}" @selected(($expense->payment_method ?: 'cash') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="mt-2 hidden text-sm text-rose-600" data-error-for="payment_method"></p>
            </div>

            <div>
                <x-input-label for="provider_id" :value="'Proveedor (opcional)'" />
                <select
                    id="provider_id"
                    name="provider_id"
                    data-expense-provider
                    class="mt-1 block w-full rounded-2xl border-stone-300 shadow-sm focus:border-stone-900 focus:ring-stone-900"
                >
                    <option value="">Sin proveedor</option>
                </select>
                <p class="mt-2 hidden text-sm text-rose-600" data-error-for="provider_id"></p>
            </div>

            <div>
                <x-input-label for="subtotal_amount" :value="'Total del gasto'" />
                <x-text-input
                    id="subtotal_amount"
                    name="subtotal_amount"
                    type="text"
                    inputmode="decimal"
                    autocomplete="off"
                    class="mt-1 block w-full"
                    :value="$expense->subtotal_amount ?? $expense->total_amount ?? 0"
                    required
                />
                <p class="mt-2 text-xs text-stone-500">Usaremos este valor como total final del gasto.</p>
                <p class="mt-2 hidden text-sm text-rose-600" data-error-for="subtotal_amount"></p>
            </div>

            <div class="md:col-span-2">
                <x-input-label for="description" :value="'Descripción'" />
                <textarea id="description" name="description" rows="5" class="mt-1 block w-full rounded-2xl border-stone-300 shadow-sm focus:border-stone-900 focus:ring-stone-900" required>{{ $expense->description }}</textarea>
                <p class="mt-2 hidden text-sm text-rose-600" data-error-for="description"></p>
            </div>
        </div>
    </div>

    <div class="sticky bottom-0 z-10 mt-auto shrink-0 border-t border-stone-200 bg-white px-1 pb-[calc(env(safe-area-inset-bottom)+0.5rem)] pt-4 shadow-[0_-8px_18px_rgba(255,255,255,0.92)]">
        <div class="flex items-center justify-end gap-3">
            <button type="button" data-action="close-modal" class="rounded-2xl border border-stone-300 px-4 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-50">
                Cancelar
            </button>
            <button type="submit" class="rounded-2xl bg-stone-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-stone-700">
                {{ $expense->exists ? 'Actualizar gasto' : 'Crear gasto' }}
            </button>
        </div>
    </div>
</form>
