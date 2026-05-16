<form method="POST" action="{{ $action }}" data-ajax-form class="flex h-full min-h-0 flex-col gap-4 overflow-hidden sm:gap-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="min-h-0 flex-1 overflow-y-auto pr-1">
        <div class="grid gap-6 md:grid-cols-2">
            <div class="md:col-span-2">
                <x-input-label for="name" :value="'Nombre de la categoría'" />
                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="$category->name" required />
                <p class="mt-2 hidden text-sm text-rose-600" data-error-for="name"></p>
            </div>

            <div class="md:col-span-2">
                <x-input-label for="description" :value="'Descripción'" />
                <textarea id="description" name="description" rows="5" class="mt-1 block w-full rounded-2xl border-stone-300 shadow-sm focus:border-stone-900 focus:ring-stone-900">{{ $category->description }}</textarea>
                <p class="mt-2 hidden text-sm text-rose-600" data-error-for="description"></p>
            </div>
        </div>
    </div>

    <x-modal-footer>
        <button type="submit" class="app-save-button">
            {{ $category->exists ? 'Actualizar categoría' : 'Crear categoría' }}
        </button>
    </x-modal-footer>
</form>
