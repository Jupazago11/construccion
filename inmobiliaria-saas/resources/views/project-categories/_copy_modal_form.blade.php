<form method="POST" action="{{ $action }}" data-ajax-form class="flex h-full flex-col gap-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="min-h-0 flex-1 overflow-y-auto pr-1">
        <div class="space-y-6">
            <div class="rounded-3xl border border-stone-200 bg-stone-50 px-4 py-4 text-sm text-stone-600">
                Se copiarán categorías, subcategorías y auxiliares desde otro proyecto de la misma empresa.
                Si existen nombres repetidos en el proyecto destino, se crearán con un sufijo automático para evitar conflictos.
            </div>

            <div>
                <x-input-label for="source_project_id" :value="'Proyecto origen'" />
                <select id="source_project_id" name="source_project_id" class="mt-1 block w-full rounded-2xl border-stone-300 shadow-sm focus:border-stone-900 focus:ring-stone-900">
                    <option value="">Selecciona un proyecto</option>
                    @foreach ($sourceProjects as $sourceProject)
                        <option value="{{ $sourceProject->id }}">
                            {{ $sourceProject->name }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-2 hidden text-sm text-rose-600" data-error-for="source_project_id"></p>
            </div>
        </div>
    </div>

    <div class="sticky bottom-0 flex items-center justify-end gap-3 border-t border-stone-200 bg-white pt-5">
        <button type="button" data-action="close-modal" class="rounded-2xl border border-stone-300 px-4 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-50">
            Cancelar
        </button>
        <button type="submit" class="rounded-2xl bg-stone-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-stone-700">
            Copiar estructura
        </button>
    </div>
</form>
