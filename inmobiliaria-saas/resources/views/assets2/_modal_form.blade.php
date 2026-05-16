@php
    $selectedAsset2TypeId = old('asset2_type_id', $asset2->asset2_type_id ?: ($asset2Types->firstWhere('status', 'active')['id'] ?? null));
    $formUid = 'asset2-' . ($asset2->exists ? $asset2->id : 'new') . '-' . substr(md5($action), 0, 8);
@endphp

<form
    method="POST"
    action="{{ $action }}"
    data-ajax-form
    data-asset-form
    x-data="assetTypeManager({
        types: @js($asset2Types),
        selectedTypeId: @js($selectedAsset2TypeId ? (string) $selectedAsset2TypeId : ''),
        storeUrl: @js(route('asset2-types.store')),
        indexUrl: @js(route('asset2-types.index')),
        initialCompanyId: @js((string) ($asset2->company_id ?: request('company_id') ?: auth()->user()->company_id)),
    })"
    x-init="init()"
    class="flex h-full min-h-0 flex-col gap-4 overflow-hidden sm:gap-6"
>
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="min-h-0 flex-1 overflow-y-auto pr-1">
        <div class="grid gap-6 md:grid-cols-2">
            @if (auth()->user()->isSuperAdmin())
                <div class="md:col-span-2">
                    <x-input-label for="{{ $formUid }}-company-id" :value="'Empresa'" />
                    <select
                        id="{{ $formUid }}-company-id"
                        name="company_id"
                        autocomplete="organization"
                        class="mt-1 block w-full rounded-2xl border-stone-300 shadow-sm focus:border-stone-900 focus:ring-stone-900"
                        x-on:change="companyId = $event.target.value; loadTypes()"
                    >
                        <option value="">Selecciona una empresa</option>
                        @foreach ($companies as $company)
                            <option value="{{ $company->id }}" @selected(($asset2->company_id ?: request('company_id')) == $company->id)>{{ $company->name }}</option>
                        @endforeach
                    </select>
                    <p class="mt-2 hidden text-sm text-rose-600" data-error-for="company_id"></p>
                </div>
            @endif

            <div class="md:col-span-2">
                <x-input-label for="{{ $formUid }}-name" :value="'Nombre del activo'" />
                <x-text-input id="{{ $formUid }}-name" name="name" type="text" class="mt-1 block w-full" :value="$asset2->name" autocomplete="off" required />
                <p class="mt-2 hidden text-sm text-rose-600" data-error-for="name"></p>
            </div>

            <div>
                <x-input-label for="{{ $formUid }}-asset2-type-id" :value="'Tipo'" />
                <div class="mt-1 flex items-center gap-2">
                    <select
                        id="{{ $formUid }}-asset2-type-id"
                        name="asset2_type_id"
                        autocomplete="off"
                        class="block w-full rounded-2xl border-stone-300 shadow-sm focus:border-stone-900 focus:ring-stone-900"
                        required
                    >
                        <option value="">Selecciona un tipo</option>

                        @foreach ($asset2Types as $type)
                            @if (($type['status'] ?? null) === 'active')
                                <option
                                    value="{{ $type['id'] }}"
                                    @selected((string) $selectedAsset2TypeId === (string) $type['id'])
                                >
                                    {{ $type['name'] }}
                                </option>
                            @endif
                        @endforeach
                    </select>
                    <button
                        type="button"
                        class="app-create-button-sm shrink-0"
                        title="Administrar tipos de activo"
                        x-on:click.stop.prevent="openManager()"
                    >
                        +
                    </button>
                </div>
                <p class="mt-2 hidden text-sm text-rose-600" data-error-for="asset2_type_id"></p>
            </div>

            <input type="hidden" name="asset_condition" value="{{ $asset2->asset_condition ?: 'new' }}">

            <div>
                <x-input-label for="{{ $formUid }}-purchase-value" :value="'Valor de compra'" />
                <x-text-input
                    id="{{ $formUid }}-purchase-value"
                    name="purchase_value"
                    type="text"
                    inputmode="numeric"
                    class="mt-1 block w-full"
                    :value="$asset2->purchase_value !== null ? number_format((float) $asset2->purchase_value, 0, ',', '.') : ''"
                    autocomplete="off"
                    data-currency-input
                />
                <p class="mt-2 hidden text-sm text-rose-600" data-error-for="purchase_value"></p>
            </div>

            <div>
                <x-input-label for="{{ $formUid }}-purchase-date" :value="'Fecha de compra'" />
                <x-text-input id="{{ $formUid }}-purchase-date" name="purchase_date" type="date" class="mt-1 block w-full" :value="optional($asset2->purchase_date)->format('Y-m-d') ?: $asset2->purchase_date" autocomplete="off" />
                <p class="mt-2 hidden text-sm text-rose-600" data-error-for="purchase_date"></p>
            </div>
        </div>

        <template x-teleport="body">
        <div
            x-show="managerOpen"
            x-cloak
            class="fixed inset-0 z-[80] flex items-center justify-center bg-black/50 p-4"
        >
            <div class="flex max-h-[88vh] w-full max-w-2xl flex-col overflow-hidden rounded-3xl bg-white shadow-2xl" x-on:click.stop>
                <div class="flex items-center justify-between gap-3 border-b border-stone-200 px-5 py-4">
                    <h3 class="text-base font-semibold text-stone-900">Tipos de activo</h3>
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
                        <div class="grid gap-3">
                            <div>
                                <label class="block font-medium text-sm text-gray-700">Tipo
                                    <x-text-input
                                        name="asset2_type_name_draft"
                                        type="text"
                                        class="mt-1 block w-full"
                                        x-model="draft.name"
                                        x-on:keydown.enter.prevent="saveType()"
                                        autocomplete="off"
                                        aria-label="Tipo de activo"
                                    />
                                </label>
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
                                    <th class="px-4 py-3 font-medium">Estado</th>
                                    <th class="px-4 py-3 font-medium"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-stone-100">
                                <template x-for="type in types" :key="type.id">
                                    <tr>
                                        <td class="px-4 py-3 font-semibold text-stone-900" x-text="type.name"></td>
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
                {{ $asset2->exists ? 'Actualizar activo' : 'Crear activo' }}
            </button>
    </x-modal-footer>
</form>
