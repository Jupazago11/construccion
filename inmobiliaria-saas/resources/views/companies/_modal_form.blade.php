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

        @if (auth()->user()->isSuperAdmin() && $company->exists)
            <div class="md:col-span-2" data-company-logo-upload>
                <x-input-label :value="'Logo de la empresa'" />
                <div class="mt-2 flex items-center gap-4">
                    <div class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-stone-200 bg-stone-50" data-company-logo-preview>
                        @if ($company->logo_path)
                            <img src="{{ route('companies.logo', $company) }}" alt="Logo" class="h-full w-full object-contain">
                        @else
                            <svg class="h-7 w-7 text-stone-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                            </svg>
                        @endif
                    </div>
                    <div>
                        <input
                            type="file"
                            id="company_logo_{{ $company->id }}"
                            accept="image/png,image/jpeg,image/webp,image/svg+xml"
                            class="sr-only"
                            data-company-logo-input
                            data-upload-url="{{ route('companies.logo.store', $company) }}"
                        >
                        <label for="company_logo_{{ $company->id }}" class="app-create-icon-button h-9 w-9 cursor-pointer rounded-full" title="Subir logo">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" /></svg>
                        </label>
                        <p class="mt-1 text-xs text-stone-400">PNG, JPG, WebP o SVG · fondo blanco · cuadrado · máx 4 MB</p>
                        <p class="mt-1 hidden text-xs text-rose-600" data-company-logo-error></p>
                    </div>
                </div>
            </div>
        @endif
        </div>
    </div>

    <x-modal-footer>
        <button type="submit" class="app-save-button disabled:cursor-wait disabled:opacity-60">
            {{ $company->exists ? 'Actualizar empresa' : 'Crear empresa' }}
        </button>
    </x-modal-footer>
</form>
