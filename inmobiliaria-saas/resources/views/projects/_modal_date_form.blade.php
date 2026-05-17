<form
    data-ajax-form
    action="{{ $action }}"
    method="PATCH"
    class="space-y-5"
>
    <div>
        <x-input-label for="start_date" :value="'Fecha de inicio'" />
        <x-text-input
            id="start_date"
            name="start_date"
            type="date"
            class="mt-1 block w-full"
            :value="$project->start_date?->format('Y-m-d')"
        />
        <x-input-error class="mt-1" :messages="$errors->get('start_date')" />
    </div>

    <div class="flex justify-end gap-3">
        <button
            type="button"
            data-action="close-modal"
            class="rounded-2xl border border-stone-200 px-4 py-2 text-sm font-medium text-stone-600 transition hover:bg-stone-100"
        >
            Cancelar
        </button>
        <x-primary-button>
            Guardar
        </x-primary-button>
    </div>
</form>
