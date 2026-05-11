<form method="POST" action="{{ $action }}" data-ajax-form data-asset-form class="flex h-full min-h-0 flex-col gap-4 overflow-hidden sm:gap-6">
    @csrf
    @if (($method ?? 'POST') !== 'POST')
        @method($method)
    @endif

    <div class="min-h-0 flex-1 overflow-y-auto pr-1">
        <div class="space-y-6">
            <div class="rounded-2xl border border-stone-200 bg-stone-50 px-4 py-3 text-sm text-stone-700">
                <div class="font-semibold text-stone-900">{{ $asset->name }}</div>
                <div class="mt-1">Registra costos, observaciones y el estado actual del activo.</div>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <div>
                    <x-input-label for="cost" :value="'Costo'" />
                    <x-text-input
                        id="cost"
                        name="cost"
                        type="text"
                        inputmode="numeric"
                        class="mt-1 block w-full"
                        :value="$novelty->cost !== null ? number_format((float) $novelty->cost, 0, ',', '.') : ''"
                        data-currency-input
                        required
                    />
                    <p class="mt-2 hidden text-sm text-rose-600" data-error-for="cost"></p>
                </div>

                <div>
                    <x-input-label for="novelty_date" :value="'Fecha'" />
                    <x-text-input id="novelty_date" name="novelty_date" type="date" class="mt-1 block w-full" :value="optional($novelty->novelty_date)->format('Y-m-d') ?: $novelty->novelty_date" required />
                    <p class="mt-2 hidden text-sm text-rose-600" data-error-for="novelty_date"></p>
                </div>

                <div class="md:col-span-2">
                    <x-input-label for="asset_status" :value="'Estado actual del activo'" />
                    <x-text-input id="asset_status" name="asset_status" type="text" class="mt-1 block w-full" :value="$novelty->asset_status" placeholder="Ej. Operativo, en mantenimiento, fuera de servicio" required />
                    <p class="mt-2 hidden text-sm text-rose-600" data-error-for="asset_status"></p>
                </div>

                <div class="md:col-span-2">
                    <x-input-label for="description" :value="'Descripción'" />
                    <textarea id="description" name="description" rows="4" class="mt-1 block w-full rounded-2xl border-stone-300 shadow-sm focus:border-stone-900 focus:ring-stone-900" required>{{ $novelty->description }}</textarea>
                    <p class="mt-2 hidden text-sm text-rose-600" data-error-for="description"></p>
                </div>
            </div>

            @unless ($novelty->exists)
                <div class="space-y-3">
                    <div class="flex items-center justify-between gap-4">
                        <h3 class="text-sm font-semibold text-stone-900">Novedades recientes</h3>
                        <span class="text-xs text-stone-500">{{ $asset->novelties->count() }} visibles</span>
                    </div>

                    @forelse ($asset->novelties as $recentNovelty)
                        <div class="rounded-2xl border border-stone-200 px-4 py-3 text-sm text-stone-600">
                            <div class="flex items-center justify-between gap-4">
                                <div class="font-medium text-stone-900">{{ $recentNovelty->novelty_date?->format('Y-m-d') }}</div>
                                <div class="flex items-center gap-2">
                                    <div>$ {{ number_format((float) $recentNovelty->cost, 0, ',', '.') }}</div>
                                    <button
                                        type="button"
                                        data-action="edit"
                                        data-url="{{ route('assets.novelties.edit', ['asset' => $asset, 'novelty' => $recentNovelty] + request()->query()) }}"
                                        data-title="Editar novedad"
                                        class="rounded-xl border border-stone-200 p-1.5 text-stone-600 transition hover:bg-stone-100 hover:text-stone-900"
                                        title="Editar novedad"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M17.414 2.586a2 2 0 010 2.828l-8.5 8.5a2 2 0 01-.878.497l-3 1a1 1 0 01-1.265-1.265l1-3a2 2 0 01.497-.878l8.5-8.5a2 2 0 012.828 0z" />
                                        </svg>
                                    </button>
                                    <button
                                        type="button"
                                        data-action="delete"
                                        data-url="{{ route('assets.novelties.destroy', ['asset' => $asset, 'novelty' => $recentNovelty] + request()->query()) }}"
                                        data-confirm-message="¿Deseas eliminar esta novedad?"
                                        class="rounded-xl border border-rose-200 p-1.5 text-rose-700 transition hover:bg-rose-50"
                                        title="Eliminar novedad"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.366-.446.911-.699 1.486-.699h.514c.575 0 1.12.253 1.486.699L12.85 4H16a1 1 0 110 2h-1l-.867 10.142A2 2 0 0112.14 18H7.86a2 2 0 01-1.993-1.858L5 6H4a1 1 0 010-2h3.15l1.107-.901zM8 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm4-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <div class="mt-1"><span class="font-semibold text-stone-700">Estado:</span> {{ $recentNovelty->asset_status }}</div>
                            <div class="mt-1">{{ $recentNovelty->description }}</div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-stone-300 bg-stone-50 px-4 py-8 text-center text-sm text-stone-500">
                            Este activo aún no tiene novedades registradas.
                        </div>
                    @endforelse
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
                {{ $novelty->exists ? 'Actualizar novedad' : 'Registrar novedad' }}
            </button>
        </div>
    </div>
</form>
