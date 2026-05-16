<form method="POST" action="{{ $action }}" data-ajax-form class="flex h-full min-h-0 flex-col gap-4 overflow-hidden sm:gap-6">
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

    <x-modal-footer>
        <button type="submit" class="app-save-button">
            Copiar estructura
        </button>
    </x-modal-footer>
</form>
