<form method="POST" action="{{ $action }}" data-ajax-form class="flex h-full min-h-0 flex-col gap-4 overflow-hidden sm:gap-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="min-h-0 flex-1 overflow-y-auto pr-1">
        <div class="grid gap-6 md:grid-cols-2">
            @if (auth()->user()->isSuperAdmin())
                <div class="md:col-span-2">
                    <x-input-label for="company_id" :value="'Empresa'" />
                    <select id="company_id" name="company_id" class="mt-1 block w-full rounded-2xl border-stone-300 shadow-sm focus:border-stone-900 focus:ring-stone-900">
                        <option value="">Selecciona una empresa</option>
                        @foreach ($companies as $company)
                            <option value="{{ $company->id }}" @selected(($provider->company_id ?: request('company_id')) == $company->id)>{{ $company->name }}</option>
                        @endforeach
                    </select>
                    <p class="mt-2 hidden text-sm text-rose-600" data-error-for="company_id"></p>
                </div>
            @endif

            <div class="md:col-span-2">
                <x-input-label for="name" :value="'Nombre del proveedor'" />
                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="$provider->name" required />
                <p class="mt-2 hidden text-sm text-rose-600" data-error-for="name"></p>
            </div>

            <div class="md:col-span-2">
                <x-input-label for="location" :value="'Ubicación'" />
                <x-text-input id="location" name="location" type="text" class="mt-1 block w-full" :value="$provider->location" placeholder="Ej. Rionegro, Antioquia" />
                <p class="mt-2 hidden text-sm text-rose-600" data-error-for="location"></p>
            </div>

            <div>
                <x-input-label for="document_number" :value="'Documento'" />
                <x-text-input id="document_number" name="document_number" type="text" class="mt-1 block w-full" :value="$provider->document_number" />
                <p class="mt-2 hidden text-sm text-rose-600" data-error-for="document_number"></p>
            </div>

            <div>
                <x-input-label for="phone" :value="'Teléfono'" />
                <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="$provider->phone" />
                <p class="mt-2 hidden text-sm text-rose-600" data-error-for="phone"></p>
            </div>

            <div>
                <x-input-label for="email" :value="'Correo'" />
                <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="$provider->email" />
                <p class="mt-2 hidden text-sm text-rose-600" data-error-for="email"></p>
            </div>

            @unless($provider->exists)
                <div>
                    <x-input-label for="status" :value="'Estado'" />
                    <select id="status" name="status" class="mt-1 block w-full rounded-2xl border-stone-300 shadow-sm focus:border-stone-900 focus:ring-stone-900">
                        @foreach (['active' => 'Activo', 'inactive' => 'Inactivo'] as $value => $label)
                            <option value="{{ $value }}" @selected(($provider->status ?: 'active') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="mt-2 hidden text-sm text-rose-600" data-error-for="status"></p>
                </div>
            @endunless
        </div>
    </div>

    <div class="sticky bottom-0 z-10 mt-auto shrink-0 border-t border-stone-200 bg-white px-1 pb-[calc(env(safe-area-inset-bottom)+0.5rem)] pt-4 shadow-[0_-8px_18px_rgba(255,255,255,0.92)]">
        <div class="flex items-center justify-end gap-3">
        <button type="button" data-action="close-modal" class="rounded-2xl border border-stone-300 px-4 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-50">
            Cancelar
        </button>
        <button type="submit" class="rounded-2xl bg-stone-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-stone-700">
            {{ $provider->exists ? 'Actualizar proveedor' : 'Crear proveedor' }}
        </button>
        </div>
    </div>
</form>
