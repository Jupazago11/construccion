@php
    $activeGroups = $groups->where('status', 'active')->values();
    $activeSubgroups = $subgroups->where('status', 'active')->values();
@endphp

<x-app-layout
    x-data="productCatalog({
        companyId: {{ \Illuminate\Support\Js::from($filters['company_id']) }},
        isSuperAdmin: {{ auth()->user()->isSuperAdmin() ? 'true' : 'false' }},
        urls: {
            groupStore: '{{ route('product-catalog.groups.store', [], false) }}',
            groupUpdate: '{{ route('product-catalog.groups.update', ['productGroup' => '__ID__'], false) }}',
            groupStatus: '{{ route('product-catalog.groups.status', ['productGroup' => '__ID__'], false) }}',
            groupDestroy: '{{ route('product-catalog.groups.destroy', ['productGroup' => '__ID__'], false) }}',
            subgroupStore: '{{ route('product-catalog.subgroups.store', [], false) }}',
            subgroupUpdate: '{{ route('product-catalog.subgroups.update', ['productSubgroup' => '__ID__'], false) }}',
            subgroupStatus: '{{ route('product-catalog.subgroups.status', ['productSubgroup' => '__ID__'], false) }}',
            subgroupDestroy: '{{ route('product-catalog.subgroups.destroy', ['productSubgroup' => '__ID__'], false) }}',
            productStore: '{{ route('product-catalog.products.store', [], false) }}',
            productUpdate: '{{ route('product-catalog.products.update', ['product' => '__ID__'], false) }}',
            productStatus: '{{ route('product-catalog.products.status', ['product' => '__ID__'], false) }}',
            productDestroy: '{{ route('product-catalog.products.destroy', ['product' => '__ID__'], false) }}'
        },
        groups: {{ \Illuminate\Support\Js::from($activeGroups->map(fn ($g) => ['id' => $g->id, 'name' => $g->name, 'company_id' => $g->company_id])->values()) }},
        subgroups: {{ \Illuminate\Support\Js::from($activeSubgroups->map(fn ($s) => ['id' => $s->id, 'name' => $s->name, 'company_id' => $s->company_id, 'product_group_id' => $s->product_group_id])->values()) }}
    })"
>
    <x-slot name="header">
        <x-page-header title="Catálogo de productos" description="">
            @can('create', App\Models\Product::class)
                <button type="button" class="app-create-button" title="Nuevo producto" x-on:click="openModal('product')">+</button>
            @endcan
        </x-page-header>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            {{-- Filtros --}}
            <section
                x-data="{
                    filtersOpen: window.innerWidth >= 768,
                    selectedGroup: '{{ $filters['group_id'] ?? '' }}',
                    allSubgroups: @js($subgroups->map(fn ($s) => ['id' => $s->id, 'name' => $s->name, 'product_group_id' => $s->product_group_id])->values()),
                    get filteredSubs() {
                        if (!this.selectedGroup) return this.allSubgroups;
                        return this.allSubgroups.filter(s => String(s.product_group_id) === String(this.selectedGroup));
                    }
                }"
                class="overflow-hidden rounded-3xl border border-stone-200 bg-white shadow-sm"
            >
                <div class="flex items-center justify-between gap-3 px-5 py-4">
                    <h2 class="text-sm font-semibold text-stone-900">Filtros</h2>

                    <button
                        type="button"
                        class="inline-flex items-center rounded-2xl border border-stone-300 px-3 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-50 md:hidden"
                        x-on:click="filtersOpen = !filtersOpen"
                        x-text="filtersOpen ? 'Ocultar' : 'Expandir'"
                    ></button>
                </div>

                <form method="GET" action="{{ route('product-catalog.index') }}" class="border-t border-stone-200 p-5">
                    <div
                        x-show="filtersOpen"
                        x-transition:enter="transition ease-out duration-[875ms]"
                        x-transition:enter-start="opacity-0 -translate-y-2"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-[613ms]"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 -translate-y-2"
                        x-cloak
                    >
                    @if (auth()->user()->isSuperAdmin())
                        <div class="mb-4">
                            <x-input-label for="filter_company_id" value="Empresa" />
                            <select
                                id="filter_company_id"
                                name="company_id"
                                class="mt-1 block w-full rounded-2xl border-stone-300 text-sm shadow-sm focus:border-stone-900 focus:ring-stone-900 sm:max-w-xs"
                                x-on:change="$el.form.submit()"
                            >
                                <option value="">Todas las empresas</option>
                                @foreach ($companies as $company)
                                    <option value="{{ $company->id }}" @selected((string) $filters['company_id'] === (string) $company->id)>{{ $company->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="sm:col-span-2">
                            <x-input-label for="filter_search" value="Buscar" />
                            <x-text-input
                                id="filter_search"
                                name="search"
                                type="text"
                                class="mt-1 block w-full"
                                placeholder="Nombre del producto..."
                                value="{{ $filters['search'] }}"
                                autocomplete="off"
                            />
                        </div>

                        <div>
                            <x-input-label for="filter_group_id" value="Grupo" />
                            <select
                                id="filter_group_id"
                                name="group_id"
                                x-model="selectedGroup"
                                class="mt-1 block w-full rounded-2xl border-stone-300 text-sm shadow-sm focus:border-stone-900 focus:ring-stone-900"
                            >
                                <option value="">Todos los grupos</option>
                                @foreach ($groups as $group)
                                    <option value="{{ $group->id }}" @selected($filters['group_id'] == $group->id)>{{ $group->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <x-input-label for="filter_subgroup_id" value="Subgrupo" />
                            <select
                                id="filter_subgroup_id"
                                name="subgroup_id"
                                class="mt-1 block w-full rounded-2xl border-stone-300 text-sm shadow-sm focus:border-stone-900 focus:ring-stone-900"
                            >
                                <option value="">Todos los subgrupos</option>
                                <template x-for="sub in filteredSubs" :key="sub.id">
                                    <option
                                        :value="sub.id"
                                        :selected="sub.id == {{ $filters['subgroup_id'] ?? 0 }}"
                                        x-text="sub.name"
                                    ></option>
                                </template>
                            </select>
                        </div>
                    </div>

                    <div class="mt-4 flex items-center gap-3">
                        <x-primary-button>Filtrar</x-primary-button>
                        <a
                            href="{{ route('product-catalog.index', auth()->user()->isSuperAdmin() && $filters['company_id'] ? ['company_id' => $filters['company_id']] : []) }}"
                            class="rounded-2xl border border-stone-300 px-4 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-50"
                        >Limpiar</a>
                    </div>
                    </div>
                </form>
            </section>

            {{-- Tabla de productos --}}
            <section class="overflow-hidden rounded-3xl border border-stone-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-stone-200 px-5 py-4">
                    <h2 class="text-sm font-semibold text-stone-900">
                        Productos
                        <span class="ml-1 font-normal text-stone-400">({{ $products->total() }})</span>
                    </h2>
                </div>
                <div class="overflow-x-auto" x-ref="productsTable">@include('product-catalog._products_table')</div>
            </section>

            <x-ajax-crud-toast />
        </div>
    </div>

    <div
        x-show="modalOpen"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center bg-stone-950/45 px-4 py-6"
    >
        <div
            x-show="modalOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="grid max-h-[88dvh] w-full max-w-2xl grid-rows-[auto,minmax(0,1fr),auto] overflow-visible rounded-3xl border border-stone-200 bg-white shadow-2xl"
        >
            <div class="flex items-start justify-between gap-4 border-b border-stone-200 px-6 py-5">
                <div>
                    <h3 class="text-lg font-semibold text-stone-900" x-text="editingId ? 'Editar' : 'Nuevo registro'"></h3>
                </div>
                <button type="button" class="rounded-full p-2 text-stone-500 transition hover:bg-stone-100 hover:text-stone-900" title="Cerrar" x-on:click="closeModal()">×</button>
            </div>

            <form class="min-h-0 overflow-visible px-6 py-5 transition-all duration-300 ease-out" x-on:submit.prevent="saveRecord">
                <div class="mb-5 grid grid-cols-3 gap-2 rounded-2xl bg-stone-100 p-1">
                    <button type="button" class="rounded-xl px-3 py-2 text-sm font-medium transition" :class="tab === 'group' ? 'bg-white text-stone-950 shadow-sm' : 'text-stone-600'" x-on:click="setTab('group')">Grupo</button>
                    <button type="button" class="rounded-xl px-3 py-2 text-sm font-medium transition" :class="tab === 'subgroup' ? 'bg-white text-stone-950 shadow-sm' : 'text-stone-600'" x-on:click="setTab('subgroup')">Subgrupo</button>
                    <button type="button" class="rounded-xl px-3 py-2 text-sm font-medium transition" :class="tab === 'product' ? 'bg-white text-stone-950 shadow-sm' : 'text-stone-600'" x-on:click="setTab('product')">Producto</button>
                </div>

                @if (auth()->user()->isSuperAdmin())
                    <div class="mb-5" x-show="!editingId">
                        <x-input-label for="catalog_company_id" :value="'Empresa'" />
                        <select id="catalog_company_id" x-model="form.company_id" class="mt-1 block w-full rounded-2xl border-stone-300 shadow-sm focus:border-stone-900 focus:ring-stone-900">
                            <option value="">Selecciona una empresa</option>
                            @foreach ($companies as $company)
                                <option value="{{ $company->id }}">{{ $company->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="space-y-5 transition-all duration-300 ease-out">
                    <div
                        class="relative"
                        x-show="tab === 'subgroup' || tab === 'product'"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 -translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 -translate-y-1"
                    >
                        <x-input-label for="product_group_search" :value="'Grupo'" />
                        <input
                            id="product_group_search"
                            x-model="groupSearch"
                            x-on:focus="openGroupMenu()"
                            x-on:input="syncGroupFromSearch(); openGroupMenu()"
                            x-on:blur="normalizeGroupSearch()"
                            class="mt-1 block w-full rounded-2xl border-stone-300 shadow-sm focus:border-stone-900 focus:ring-stone-900"
                            autocomplete="off"
                            placeholder="Escribe para buscar un grupo"
                        >
                        <div x-show="groupMenuOpen" x-on:mousedown.prevent class="absolute left-0 right-0 z-[70] mt-2 max-h-64 overflow-y-auto rounded-2xl border border-stone-200 bg-white p-1 shadow-xl">
                            <template x-for="group in visibleGroups" :key="group.id">
                                <button type="button" class="block w-full rounded-xl px-4 py-3 text-left transition hover:bg-stone-100 focus:bg-stone-100 focus:outline-none" x-on:click="selectGroup(group)">
                                    <span class="block whitespace-nowrap text-sm font-medium text-stone-900" x-text="group.name"></span>
                                </button>
                            </template>
                            <div x-show="visibleGroups.length === 0" class="px-4 py-3 text-sm text-stone-500">Sin grupos disponibles</div>
                        </div>
                    </div>

                    <div
                        class="relative"
                        x-show="tab === 'product'"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 -translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 -translate-y-1"
                    >
                        <x-input-label for="product_subgroup_search" :value="'Subgrupo'" />
                        <input
                            id="product_subgroup_search"
                            x-model="subgroupSearch"
                            x-on:focus="openSubgroupMenu()"
                            x-on:input="syncSubgroupFromSearch(); openSubgroupMenu()"
                            x-on:blur="normalizeSubgroupSearch()"
                            class="mt-1 block w-full rounded-2xl border-stone-300 shadow-sm focus:border-stone-900 focus:ring-stone-900"
                            autocomplete="off"
                            placeholder="Escribe para buscar un subgrupo"
                        >
                        <div x-show="subgroupMenuOpen" x-on:mousedown.prevent class="absolute left-0 right-0 z-[70] mt-2 max-h-64 overflow-y-auto rounded-2xl border border-stone-200 bg-white p-1 shadow-xl">
                            <template x-for="subgroup in visibleSubgroups" :key="subgroup.id">
                                <button type="button" class="block w-full rounded-xl px-4 py-3 text-left transition hover:bg-stone-100 focus:bg-stone-100 focus:outline-none" x-on:click="selectSubgroup(subgroup)">
                                    <span class="block whitespace-nowrap text-sm font-medium text-stone-900" x-text="subgroup.name"></span>
                                </button>
                            </template>
                            <div x-show="visibleSubgroups.length === 0" class="px-4 py-3 text-sm text-stone-500">Sin subgrupos disponibles</div>
                        </div>
                    </div>

                    <div>
                        <label for="catalog_name" class="block text-sm font-medium text-gray-700" x-text="tab === 'group' ? 'Nombre del grupo' : (tab === 'subgroup' ? 'Nombre del subgrupo' : 'Nombre del producto o actividad')"></label>
                        <x-text-input id="catalog_name" x-model="form.name" type="text" class="mt-1 block w-full" required />
                    </div>
                </div>
            </form>

            <div class="border-t border-stone-200 px-6 py-4">
                <div class="flex items-center justify-end gap-3">
                    <button type="button" class="rounded-2xl border border-stone-300 px-4 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-50" x-on:click="closeModal()">Cancelar</button>
                    <button type="button" class="rounded-2xl bg-stone-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-stone-700 disabled:cursor-wait disabled:opacity-60" x-on:click="saveRecord" :disabled="saving" x-text="saving ? 'Guardando...' : (editingId ? 'Actualizar' : 'Guardar')"></button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
