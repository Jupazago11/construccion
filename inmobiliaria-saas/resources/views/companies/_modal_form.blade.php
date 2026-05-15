<form method="POST" action="{{ $action }}" data-ajax-form class="flex h-full min-h-0 flex-col gap-4 overflow-hidden sm:gap-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="min-h-0 flex-1 overflow-y-auto pr-1">
        <div class="grid gap-6 md:grid-cols-2">
        <div>
            <x-input-label for="name" :value="'Nombre de la empresa'" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="$company->name" required />
            <p class="mt-2 hidden text-sm text-rose-600" data-error-for="name"></p>
        </div>

        <div>
            <x-input-label for="legal_name" :value="'Razón social'" />
            <x-text-input id="legal_name" name="legal_name" type="text" class="mt-1 block w-full" :value="$company->legal_name" />
            <p class="mt-2 hidden text-sm text-rose-600" data-error-for="legal_name"></p>
        </div>

        <div>
            <x-input-label for="nit" :value="'NIT'" />
            <x-text-input id="nit" name="nit" type="text" class="mt-1 block w-full" :value="$company->nit" />
            <p class="mt-2 hidden text-sm text-rose-600" data-error-for="nit"></p>
        </div>

        @if (auth()->user()->isSuperAdmin())
            <div>
                <x-input-label for="status" :value="'Estado'" />
                <select id="status" name="status" class="mt-1 block w-full rounded-2xl border-stone-300 shadow-sm focus:border-stone-900 focus:ring-stone-900">
                    @foreach (['active' => 'Activo', 'inactive' => 'Inactivo', 'deleted' => 'Eliminado'] as $value => $label)
                        <option value="{{ $value }}" @selected(($company->status ?: 'active') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="mt-2 hidden text-sm text-rose-600" data-error-for="status"></p>
            </div>
        @endif

        <div>
            <x-input-label for="email" :value="'Correo'" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="$company->email" />
            <p class="mt-2 hidden text-sm text-rose-600" data-error-for="email"></p>
        </div>

        <div>
            <x-input-label for="phone" :value="'Teléfono'" />
            <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="$company->phone" />
            <p class="mt-2 hidden text-sm text-rose-600" data-error-for="phone"></p>
        </div>

        <div>
            <x-input-label for="primary_color" :value="'Color principal'" />
            <x-text-input id="primary_color" name="primary_color" type="text" class="mt-1 block w-full" :value="$company->primary_color" placeholder="#1f2937" />
            <p class="mt-2 hidden text-sm text-rose-600" data-error-for="primary_color"></p>
        </div>

        </div>
    </div>

    <div class="sticky bottom-0 z-10 mt-auto shrink-0 border-t border-stone-200 bg-white px-1 pb-[calc(env(safe-area-inset-bottom)+0.5rem)] pt-4 shadow-[0_-8px_18px_rgba(255,255,255,0.92)]">
        <div class="flex items-center justify-end gap-3">
        <button type="button" data-action="close-modal" class="rounded-2xl border border-stone-300 px-4 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-50">
            Cancelar
        </button>
        <button type="submit" class="rounded-2xl bg-stone-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-stone-700 disabled:cursor-wait disabled:opacity-60">
            {{ $company->exists ? 'Actualizar empresa' : 'Crear empresa' }}
        </button>
        </div>
    </div>
</form>
