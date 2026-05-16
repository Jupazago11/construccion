@php
    $activeNoveltyTypes = $activeNoveltyTypes ?? $noveltyTypes->where('status', 'active')->values();
    $selectedNoveltyTypeId = old('asset_novelty_type_id', $novelty->asset_novelty_type_id ?: ($activeNoveltyTypes->first()['id'] ?? null));
    $formUid = 'asset-novelty-' . $asset->id . '-' . ($novelty->exists ? $novelty->id : 'new') . '-' . substr(md5($action), 0, 8);
@endphp

<form
    method="POST"
    action="{{ $action }}"
    data-ajax-form
    data-asset-form
    x-data="assetTypeManager({
        types: @js($noveltyTypes),
        selectedTypeId: @js($selectedNoveltyTypeId ? (string) $selectedNoveltyTypeId : ''),
        storeUrl: @js(route('asset-novelty-types.store')),
        indexUrl: @js(route('asset-novelty-types.index')),
        initialCompanyId: @js((string) $asset->company_id),
        entityName: 'novedad',
    })"
    x-init="init()"
    class="flex h-full min-h-0 flex-col gap-4 overflow-hidden sm:gap-6"
>
    @csrf
    @if (($method ?? 'POST') !== 'POST')
        @method($method)
    @endif

    <div class="min-h-0 flex-1 overflow-y-auto pr-1">
        <div class="space-y-6">
            <div class="rounded-2xl border border-stone-200 bg-stone-50 px-4 py-3 text-sm text-stone-700">
                <div class="font-semibold text-stone-900">{{ $asset->name }}</div>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <div>
                    <x-input-label for="{{ $formUid }}-asset-novelty-type-id" :value="'Tipo de novedad'" />
                    <div class="mt-1 flex items-center gap-2">
                        <select
                            id="{{ $formUid }}-asset-novelty-type-id"
                            name="asset_novelty_type_id"
                            autocomplete="off"
                            class="block w-full rounded-2xl border-stone-300 shadow-sm focus:border-stone-900 focus:ring-stone-900"
                            x-effect="syncTypeSelect($el)"
                            x-on:change="handleTypeChange($event)"
                            required
                        ></select>
                        <button
                            type="button"
                            class="app-create-button-sm shrink-0"
                            title="Administrar tipos de novedad"
                            x-on:click.stop.prevent="openManager()"
                        >
                            +
                        </button>
                    </div>
                    <p class="mt-2 hidden text-sm text-rose-600" data-error-for="asset_novelty_type_id"></p>
                </div>

                <div>
                    <x-input-label for="{{ $formUid }}-cost" :value="'Costo'" />
                    <x-text-input
                        id="{{ $formUid }}-cost"
                        name="cost"
                        type="text"
                        inputmode="numeric"
                        class="mt-1 block w-full"
                        :value="$novelty->cost !== null ? number_format((float) $novelty->cost, 0, ',', '.') : ''"
                        autocomplete="off"
                        data-currency-input
                        placeholder="Ej. 1.000.000"
                        required
                    />
                    <p class="mt-2 hidden text-sm text-rose-600" data-error-for="cost"></p>
                </div>

                <div>
                    <x-input-label for="{{ $formUid }}-novelty-date" :value="'Fecha'" />
                    <x-text-input id="{{ $formUid }}-novelty-date" name="novelty_date" type="date" class="mt-1 block w-full" :value="optional($novelty->novelty_date)->format('Y-m-d') ?: $novelty->novelty_date" autocomplete="off" required />
                    <p class="mt-2 hidden text-sm text-rose-600" data-error-for="novelty_date"></p>
                </div>

                <div class="md:col-span-2">
                    <x-input-label for="{{ $formUid }}-asset-status" :value="'Estado actual del activo'" />
                    <x-text-input id="{{ $formUid }}-asset-status" name="asset_status" type="text" class="mt-1 block w-full" :value="$novelty->asset_status" placeholder="Ej. Operativo, en mantenimiento, fuera de servicio" autocomplete="off" required />
                    <p class="mt-2 hidden text-sm text-rose-600" data-error-for="asset_status"></p>
                </div>

                <div class="md:col-span-2">
                    <x-input-label for="{{ $formUid }}-description" :value="'Descripción'" />
                    <textarea id="{{ $formUid }}-description" name="description" rows="4" class="mt-1 block w-full rounded-2xl border-stone-300 shadow-sm focus:border-stone-900 focus:ring-stone-900" autocomplete="off" required>{{ $novelty->description }}</textarea>
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
                            <div class="mt-1"><span class="font-semibold text-stone-700">Tipo:</span> {{ $recentNovelty->type?->name ?: 'Sin tipo' }}</div>
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

        <template x-teleport="body">
        <div
            x-show="managerOpen"
            x-cloak
            class="fixed inset-0 z-[80] flex items-center justify-center bg-black/50 p-4"
        >
            <div class="flex max-h-[88vh] w-full max-w-2xl flex-col overflow-hidden rounded-3xl bg-white shadow-2xl" x-on:click.stop>
                <div class="flex items-center justify-between gap-3 border-b border-stone-200 px-5 py-4">
                    <h3 class="text-base font-semibold text-stone-900">Tipos de novedad</h3>
                    <button type="button" class="rounded-full p-2 text-stone-500 transition hover:bg-stone-100 hover:text-stone-900" x-on:click="closeManager()">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                    </button>
                </div>

                <div class="min-h-0 flex-1 space-y-4 overflow-y-auto p-5">
                    <div
                        x-show="managerLoading"
                        class="rounded-2xl border border-dashed border-stone-300 bg-stone-50 px-4 py-10 text-center text-sm font-medium text-stone-500"
                    >
                        Cargando tipos...
                    </div>

                    <div x-show="! managerLoading" class="space-y-4">
                    <div class="rounded-2xl border border-stone-200 bg-stone-50 p-4">
                        <div class="grid gap-3 md:grid-cols-[1fr_auto]">
                            <div>
                                <label class="block font-medium text-sm text-gray-700">Tipo
                                    <x-text-input
                                        name="asset_novelty_type_name_draft"
                                        type="text"
                                        class="mt-1 block w-full"
                                        x-model="draft.name"
                                        x-on:keydown.enter.prevent="saveType()"
                                        autocomplete="off"
                                        aria-label="Tipo de novedad"
                                    />
                                </label>
                            </div>

                            <div>
                                <x-input-label :value="'Da valor'" />
                                <div class="mt-1">
                                    <label class="inline-flex cursor-pointer items-center gap-2 rounded-2xl border border-stone-300 bg-white px-4 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-50">
                                        <input type="checkbox" class="rounded border-stone-300 text-stone-900 shadow-sm focus:ring-stone-900" x-model="draft.adds_value" autocomplete="off">
                                        Da valor al activo
                                    </label>
                                </div>
                            </div>

                        </div>

                        <p class="mt-3 text-sm text-rose-600" x-show="managerError" x-text="managerError"></p>

                        <div class="mt-4 flex justify-end gap-2">
                            <button type="button" class="rounded-2xl border border-stone-300 px-4 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-50" x-on:click="resetDraft()" x-show="draft.id">
                                Cancelar edición
                            </button>
                            <button type="button" class="app-create-text-button" x-on:click="saveType()" x-bind:disabled="managerSaving">
                                <span x-text="draft.id ? 'Actualizar tipo' : 'Crear tipo'"></span>
                            </button>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-2xl border border-stone-200">
                        <table class="min-w-full divide-y divide-stone-200 text-sm">
                            <thead class="bg-stone-50 text-left text-stone-500">
                                <tr>
                                    <th class="px-4 py-3 font-medium">Tipo</th>
                                    <th class="px-4 py-3 font-medium">Da valor</th>
                                    <th class="px-4 py-3 font-medium">Estado</th>
                                    <th class="px-4 py-3 font-medium"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-stone-100">
                                <template x-for="type in types" :key="type.id">
                                    <tr>
                                        <td class="px-4 py-3 font-semibold text-stone-900" x-text="type.name"></td>
                                        <td class="px-4 py-3">
                                            <div class="inline-flex items-center">
                                                <button
                                                    type="button"
                                                    class="rounded-full border px-3 py-1 text-xs font-semibold uppercase transition"
                                                    :class="type.adds_value ? 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100' : 'border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100'"
                                                    x-on:click.stop.prevent="quickUpdateType(type, { adds_value: ! type.adds_value }, $event)"
                                                    x-text="type.adds_value ? 'Sí' : 'No'"
                                                    title="Cambiar si da valor"
                                                >
                                                </button>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <button
                                                type="button"
                                                class="rounded-full border px-3 py-1 text-xs font-semibold uppercase transition"
                                                :class="type.status === 'active' ? 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100' : 'border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100'"
                                                x-on:click.stop.prevent="quickUpdateType(type, { status: type.status === 'active' ? 'inactive' : 'active' }, $event)"
                                                x-text="type.status === 'active' ? 'Activo' : 'Inactivo'"
                                                title="Cambiar estado"
                                            >
                                            </button>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex justify-end gap-2">
                                                <button type="button" class="rounded-2xl border border-stone-200 p-2 text-stone-600 transition hover:bg-stone-100 hover:text-stone-900" title="Editar" x-on:click="editType(type)">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                        <path d="M17.414 2.586a2 2 0 010 2.828l-8.5 8.5a2 2 0 01-.878.497l-3 1a1 1 0 01-1.265-1.265l1-3a2 2 0 01.497-.878l8.5-8.5a2 2 0 012.828 0z" />
                                                    </svg>
                                                </button>
                                                <button type="button" class="rounded-2xl border border-rose-200 p-2 text-rose-700 transition hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-40" title="Eliminar" x-on:click="deleteType(type)" x-bind:disabled="! type.can_delete">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M8.257 3.099c.366-.446.911-.699 1.486-.699h.514c.575 0 1.12.253 1.486.699L12.85 4H16a1 1 0 110 2h-1l-.867 10.142A2 2 0 0112.14 18H7.86a2 2 0 01-1.993-1.858L5 6H4a1 1 0 010-2h3.15l1.107-.901zM8 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm4-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    </div>
                </div>
            </div>
        </div>
        </template>
    </div>

    <x-modal-footer>
            <button type="submit" class="app-save-button disabled:cursor-wait disabled:opacity-60">
                {{ $novelty->exists ? 'Actualizar novedad' : 'Registrar novedad' }}
            </button>
    </x-modal-footer>
</form>
