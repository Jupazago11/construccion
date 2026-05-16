<form method="POST" action="{{ $action }}" data-ajax-form class="flex h-full min-h-0 flex-col gap-4 overflow-hidden sm:gap-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="min-h-0 flex-1 overflow-y-auto pr-1">
        <div class="grid gap-6 md:grid-cols-2">
            @if ($lockCategory ?? false)
                <input type="hidden" name="category_id" value="{{ $selectedCategory?->id }}">

                <div class="md:col-span-2">
                    <div class="flex items-center gap-2 rounded-2xl border border-stone-200 bg-stone-50 px-4 py-3 text-sm text-stone-700">
                        <span class="font-medium text-stone-500">Categoría:</span>
                        <span class="font-semibold text-stone-900">{{ $selectedCategory?->name }}</span>
                    </div>
                    <p class="mt-2 hidden text-sm text-rose-600" data-error-for="category_id"></p>
                </div>
            @else
                <div class="md:col-span-2">
                    <x-input-label for="category_id" :value="'Categoría'" />
                    <select id="category_id" name="category_id" class="mt-1 block w-full rounded-2xl border-stone-300 shadow-sm focus:border-stone-900 focus:ring-stone-900">
                        <option value="">Selecciona una categoría</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(($subcategory->category_id ?: request('category_id')) == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                    <p class="mt-2 hidden text-sm text-rose-600" data-error-for="category_id"></p>
                </div>
            @endif

            <div class="md:col-span-2">
                <x-input-label for="name" :value="'Nombre de la subcategoría'" />
                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="$subcategory->name" required />
                <p class="mt-2 hidden text-sm text-rose-600" data-error-for="name"></p>
            </div>

            <div class="md:col-span-2">
                <x-input-label for="description" :value="'Descripción'" />
                <textarea id="description" name="description" rows="5" class="mt-1 block w-full rounded-2xl border-stone-300 shadow-sm focus:border-stone-900 focus:ring-stone-900">{{ $subcategory->description }}</textarea>
                <p class="mt-2 hidden text-sm text-rose-600" data-error-for="description"></p>
            </div>
        </div>
    </div>

    <x-modal-footer>
        <button type="submit" class="app-save-button">
            {{ $subcategory->exists ? 'Actualizar subcategoría' : 'Crear subcategoría' }}
        </button>
    </x-modal-footer>
</form>
