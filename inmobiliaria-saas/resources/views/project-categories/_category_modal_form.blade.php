<form method="POST" action="{{ $action }}" data-ajax-form class="flex h-full flex-col gap-6">
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

            <div>
                <x-input-label for="sort_order" :value="'Orden'" />
                <x-text-input id="sort_order" name="sort_order" type="number" min="0" class="mt-1 block w-full" :value="$category->sort_order ?? 0" />
                <p class="mt-2 hidden text-sm text-rose-600" data-error-for="sort_order"></p>
            </div>

            <div>
                <x-input-label for="status" :value="'Estado'" />
                <select id="status" name="status" class="mt-1 block w-full rounded-2xl border-stone-300 shadow-sm focus:border-stone-900 focus:ring-stone-900">
                    @foreach (['active' => 'Activo', 'inactive' => 'Inactivo'] as $value => $label)
                        <option value="{{ $value }}" @selected(($category->status ?: 'active') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="mt-2 hidden text-sm text-rose-600" data-error-for="status"></p>
            </div>

            <div class="md:col-span-2">
                <x-input-label for="description" :value="'Descripción'" />
                <textarea id="description" name="description" rows="5" class="mt-1 block w-full rounded-2xl border-stone-300 shadow-sm focus:border-stone-900 focus:ring-stone-900">{{ $category->description }}</textarea>
                <p class="mt-2 hidden text-sm text-rose-600" data-error-for="description"></p>
            </div>
        </div>
    </div>

    <div class="sticky bottom-0 flex items-center justify-end gap-3 border-t border-stone-200 bg-white pt-5">
        <button type="button" data-action="close-modal" class="rounded-2xl border border-stone-300 px-4 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-50">
            Cancelar
        </button>
        <button type="submit" class="rounded-2xl bg-stone-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-stone-700">
            {{ $category->exists ? 'Actualizar categoría' : 'Crear categoría' }}
        </button>
    </div>
</form>
