<form method="POST" action="{{ $action }}" data-ajax-form data-asset-form class="flex h-full min-h-0 flex-col gap-4 overflow-hidden sm:gap-6">
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
                            <option value="{{ $company->id }}" @selected(($asset->company_id ?: request('company_id')) == $company->id)>{{ $company->name }}</option>
                        @endforeach
                    </select>
                    <p class="mt-2 hidden text-sm text-rose-600" data-error-for="company_id"></p>
                </div>
            @endif

            <div class="md:col-span-2">
                <x-input-label for="name" :value="'Nombre del activo'" />
                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="$asset->name" required />
                <p class="mt-2 hidden text-sm text-rose-600" data-error-for="name"></p>
            </div>

            <div>
                <x-input-label for="asset_type" :value="'Tipo'" />
                <select id="asset_type" name="asset_type" class="mt-1 block w-full rounded-2xl border-stone-300 shadow-sm focus:border-stone-900 focus:ring-stone-900">
                    @foreach (['tool' => 'Herramienta', 'equipment' => 'Equipo'] as $value => $label)
                        <option value="{{ $value }}" @selected(($asset->asset_type ?: 'tool') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="mt-2 hidden text-sm text-rose-600" data-error-for="asset_type"></p>
            </div>

            <div>
                <x-input-label for="asset_condition" :value="'Estado del activo'" />
                <select id="asset_condition" name="asset_condition" class="mt-1 block w-full rounded-2xl border-stone-300 shadow-sm focus:border-stone-900 focus:ring-stone-900">
                    @foreach (['new' => 'Nuevo', 'used' => 'De segunda'] as $value => $label)
                        <option value="{{ $value }}" @selected(($asset->asset_condition ?: 'new') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="mt-2 hidden text-sm text-rose-600" data-error-for="asset_condition"></p>
            </div>

            <div>
                <x-input-label for="purchase_value" :value="'Valor de compra'" />
                <x-text-input
                    id="purchase_value"
                    name="purchase_value"
                    type="text"
                    inputmode="numeric"
                    class="mt-1 block w-full"
                    :value="$asset->purchase_value !== null ? number_format((float) $asset->purchase_value, 0, ',', '.') : ''"
                    data-currency-input
                    required
                />
                <p class="mt-2 hidden text-sm text-rose-600" data-error-for="purchase_value"></p>
            </div>

            <div>
                <x-input-label for="purchase_date" :value="'Fecha de compra'" />
                <x-text-input id="purchase_date" name="purchase_date" type="date" class="mt-1 block w-full" :value="optional($asset->purchase_date)->format('Y-m-d') ?: $asset->purchase_date" />
                <p class="mt-2 hidden text-sm text-rose-600" data-error-for="purchase_date"></p>
            </div>
        </div>
    </div>

    <div class="sticky bottom-0 z-10 mt-auto shrink-0 border-t border-stone-200 bg-white px-1 pb-[calc(env(safe-area-inset-bottom)+0.5rem)] pt-4 shadow-[0_-8px_18px_rgba(255,255,255,0.92)]">
        <div class="flex items-center justify-end gap-3">
            <button type="button" data-action="close-modal" class="rounded-2xl border border-stone-300 px-4 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-50">
                Cancelar
            </button>
            <button type="submit" class="rounded-2xl bg-stone-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-stone-700">
                {{ $asset->exists ? 'Actualizar activo' : 'Crear activo' }}
            </button>
        </div>
    </div>
</form>
