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
    data-category-store-url-template="{{ route('projects.categories.store', ['project' => '__PROJECT__'], false) }}"
    data-subcategory-store-url-template="{{ route('projects.subcategories.store', ['project' => '__PROJECT__'], false) }}"
    data-auxiliary-store-url-template="{{ route('projects.auxiliaries.store', ['project' => '__PROJECT__'], false) }}"
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
                <p class="mt-2 hidden text-sm text-rose-600" data-error-for="project_id"></p>
            </div>

            <div class="md:col-span-2" data-expense-category-wrapper style="display:none;">
                <div class="flex items-center justify-between gap-3">
                    <x-input-label for="category_id" :value="'Categoría'" />
                    <button
                        type="button"
                        data-expense-create-structure="category"
                        class="app-create-button-sm"
                        title="Crear categoría"
                    >
                        +
                    </button>
                </div>
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
                <div class="flex items-center justify-between gap-3">
                    <x-input-label for="subcategory_id" :value="'Subcategoría (opcional)'" />
                    <button
                        type="button"
                        data-expense-create-structure="subcategory"
                        class="app-create-button-sm"
                        title="Crear subcategoría"
                    >
                        +
                    </button>
                </div>
                <select
                    id="subcategory_id"
                    name="subcategory_id"
                    data-expense-subcategory
                    class="sr-only"
                >
                    <option value="">Solo categoría</option>
                </select>
                <div class="mt-2 grid gap-2 sm:grid-cols-2" data-expense-subcategory-cards></div>
                <p class="mt-2 hidden rounded-2xl border border-stone-200 bg-stone-50 px-4 py-3 text-sm text-stone-500" data-expense-subcategory-empty>
                    Puedes guardar el gasto solo con la categoría.
                </p>
                <p class="mt-2 hidden text-sm text-rose-600" data-error-for="subcategory_id"></p>
            </div>

            <div class="md:col-span-2" data-expense-auxiliary-wrapper style="display:none;">
                <div class="flex items-center justify-between gap-3">
                    <x-input-label for="auxiliary_id" :value="'Auxiliar'" />
                    <button
                        type="button"
                        data-expense-create-structure="auxiliary"
                        class="app-create-button-sm"
                        title="Crear auxiliar"
                    >
                        +
                    </button>
                </div>
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
                <p class="mt-2 hidden text-sm text-rose-600" data-error-for="subtotal_amount"></p>
            </div>

            <div class="md:col-span-2">
                <x-input-label for="description" :value="'Descripción (opcional)'" />
                <textarea id="description" name="description" rows="5" class="mt-1 block w-full rounded-2xl border-stone-300 shadow-sm focus:border-stone-900 focus:ring-stone-900">{{ $expense->description }}</textarea>
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

    <div
        data-expense-structure-modal
        class="fixed inset-0 z-[60] hidden items-center justify-center bg-stone-950/45 px-4 py-6"
    >
        <div class="grid max-h-[88dvh] w-full max-w-xl grid-rows-[auto,minmax(0,1fr)] overflow-hidden rounded-3xl border border-stone-200 bg-white shadow-2xl">
            <div class="flex items-start justify-between gap-4 border-b border-stone-200 px-6 py-5">
                <div>
                    <h3 class="text-lg font-semibold text-stone-900" data-structure-modal-title></h3>
                    <p class="mt-1 text-sm text-stone-500" data-structure-modal-context></p>
                </div>
                <button type="button" data-structure-modal-close class="rounded-full p-2 text-stone-500 transition hover:bg-stone-100 hover:text-stone-900" title="Cerrar">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>

            <div class="min-h-0 overflow-y-auto px-6 py-5">
                <div data-structure-modal-alert class="mb-4 hidden rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"></div>

                <div class="space-y-5">
                    <div>
                        <x-input-label for="expense_structure_name" :value="'Nombre'" />
                        <x-text-input id="expense_structure_name" type="text" class="mt-1 block w-full" data-structure-name />
                        <p class="mt-2 hidden text-sm text-rose-600" data-structure-error-for="name"></p>
                    </div>

                    <div>
                        <x-input-label for="expense_structure_description" :value="'Descripción'" />
                        <textarea id="expense_structure_description" rows="4" class="mt-1 block w-full rounded-2xl border-stone-300 shadow-sm focus:border-stone-900 focus:ring-stone-900" data-structure-description></textarea>
                        <p class="mt-2 hidden text-sm text-rose-600" data-structure-error-for="description"></p>
                    </div>
                </div>
            </div>

            <div class="border-t border-stone-200 bg-white px-6 py-4">
                <div class="flex items-center justify-end gap-3">
                    <button type="button" data-structure-modal-close class="rounded-2xl border border-stone-300 px-4 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-50">
                        Cancelar
                    </button>
                    <button type="button" data-structure-save class="rounded-2xl bg-stone-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-stone-700 disabled:cursor-wait disabled:opacity-60">
                        Crear
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>
