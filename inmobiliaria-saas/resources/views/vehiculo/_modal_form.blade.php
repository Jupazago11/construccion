@php
    $formUid = 'vehiculo-' . ($record->exists ? $record->id : 'new');
    $initialCategory = old('category', $record->category ?: 'gasto');
    $initialConcept = old('concept', $record->concept ?: '');
    $initialConcepts = \App\Models\VehicleRecord::CONCEPTS_BY_CATEGORY[$initialCategory] ?? [];
@endphp

<form
    method="POST"
    action="{{ $action }}"
    data-ajax-form
    data-vehiculo-form
    x-data="{
        category: @js($initialCategory),
        concept: @js(in_array($initialConcept, $initialConcepts, true) ? $initialConcept : ($initialConcepts[0] ?? '')),
        conceptsByCategory: @js(\App\Models\VehicleRecord::CONCEPTS_BY_CATEGORY),
        concepts: @js($initialConcepts),
        selectCategory(value) {
            this.category = value;
            this.concepts = this.conceptsByCategory[value] ?? [];
            if (! this.concepts.includes(this.concept)) {
                this.concept = this.concepts[0] ?? '';
            }
        },
    }"
    class="flex h-full min-h-0 flex-col gap-4 overflow-hidden sm:gap-6"
>
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="min-h-0 flex-1 overflow-y-auto pr-1">
        <div class="grid gap-6 md:grid-cols-2">
            <div>
                <x-input-label :value="'Categoría'" />
                <div class="mt-1 inline-flex w-full rounded-full border border-stone-300 p-1" role="radiogroup" aria-label="Categoría">
                    <label
                        class="flex-1 cursor-pointer rounded-full px-4 py-2 text-center text-sm font-semibold transition"
                        :class="category === 'ingreso' ? 'bg-emerald-600 text-white shadow-sm' : 'text-stone-500 hover:text-stone-900'"
                    >
                        <input type="radio" name="category" value="ingreso" x-model="category" x-on:change="selectCategory('ingreso')" class="sr-only" required />
                        Ingreso
                    </label>
                    <label
                        class="flex-1 cursor-pointer rounded-full px-4 py-2 text-center text-sm font-semibold transition"
                        :class="category === 'gasto' ? 'bg-rose-600 text-white shadow-sm' : 'text-stone-500 hover:text-stone-900'"
                    >
                        <input type="radio" name="category" value="gasto" x-model="category" x-on:change="selectCategory('gasto')" class="sr-only" required />
                        Gasto
                    </label>
                </div>
                <p class="mt-2 hidden text-sm text-rose-600" data-error-for="category"></p>
            </div>

            <div>
                <x-input-label for="{{ $formUid }}-concept" :value="'Concepto'" />
                <select
                    id="{{ $formUid }}-concept"
                    name="concept"
                    autocomplete="off"
                    class="mt-1 block w-full rounded-2xl border-stone-300 shadow-sm focus:border-stone-900 focus:ring-stone-900"
                    x-model="concept"
                    required
                >
                    <template x-for="option in concepts" :key="option">
                        <option :value="option" x-text="option"></option>
                    </template>
                </select>
                <p class="mt-2 hidden text-sm text-rose-600" data-error-for="concept"></p>
            </div>

            <div>
                <x-input-label for="{{ $formUid }}-amount" :value="'Valor'" />
                <x-text-input
                    id="{{ $formUid }}-amount"
                    name="amount"
                    type="text"
                    inputmode="numeric"
                    class="mt-1 block w-full"
                    :value="$record->amount !== null ? number_format((float) $record->amount, 0, ',', '.') : ''"
                    autocomplete="off"
                    data-currency-input
                    required
                />
                <p class="mt-2 hidden text-sm text-rose-600" data-error-for="amount"></p>
            </div>

            <div class="md:col-span-2">
                <x-input-label for="{{ $formUid }}-description" :value="'Descripción (opcional)'" />
                <x-text-input id="{{ $formUid }}-description" name="description" type="text" class="mt-1 block w-full" :value="$record->description" autocomplete="off" />
                <p class="mt-2 hidden text-sm text-rose-600" data-error-for="description"></p>
            </div>
        </div>
    </div>

    <x-modal-footer>
        @if ($record->exists)
            <div class="flex w-full items-center justify-between gap-3">
                <button
                    type="button"
                    data-action="delete"
                    data-url="{{ route('vehiculo.destroy', ['record' => $record] + request()->query()) }}"
                    data-confirm-message="¿Deseas archivar este registro?"
                    class="rounded-2xl border border-rose-200 p-2.5 text-rose-700 transition hover:bg-rose-50"
                    title="Archivar registro"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.366-.446.911-.699 1.486-.699h.514c.575 0 1.12.253 1.486.699L12.85 4H16a1 1 0 110 2h-1l-.867 10.142A2 2 0 0112.14 18H7.86a2 2 0 01-1.993-1.858L5 6H4a1 1 0 010-2h3.15l1.107-.901zM8 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm4-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </button>
                <button type="submit" class="app-save-button disabled:cursor-wait disabled:opacity-60">
                    Actualizar registro
                </button>
            </div>
        @else
            <button type="submit" class="app-save-button disabled:cursor-wait disabled:opacity-60">
                Crear registro
            </button>
        @endif
    </x-modal-footer>
</form>
