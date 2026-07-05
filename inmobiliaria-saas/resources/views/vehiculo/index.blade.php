<x-public-layout x-data="crudTable({ flash: {{ \Illuminate\Support\Js::from(session('status')) }} })" x-on:click="handleClick($event)">
    <x-slot name="header">
        <x-page-header title="Registros del vehículo">
            <button type="button" data-action="create" data-url="{{ route('vehiculo.create', request()->query()) }}" data-title="Nuevo registro" class="app-create-button" title="Nuevo registro">
                +
            </button>
        </x-page-header>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @php
                $hasActiveFilters = $filters['search'] !== '' || $filters['category'] !== '' || $filters['date_from'] !== '' || $filters['date_to'] !== '';
            @endphp

            <section x-data="{ filtersOpen: {{ $hasActiveFilters ? 'true' : 'false' }} }" class="rounded-3xl border border-stone-200 bg-white shadow-sm">
                <div class="flex items-center justify-between gap-3 px-5 py-4">
                    <h2 class="text-sm font-semibold text-stone-900">Filtros</h2>
                    <button
                        type="button"
                        class="inline-flex items-center rounded-2xl border border-stone-300 px-3 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-50"
                        x-on:click="filtersOpen = !filtersOpen"
                        x-text="filtersOpen ? 'Ocultar' : 'Expandir'"
                    ></button>
                </div>

                <form method="GET" class="border-t border-stone-200 p-5">
                    <div
                        x-show="filtersOpen"
                        x-cloak
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 -translate-y-2"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 -translate-y-2"
                        class="grid gap-4 md:grid-cols-[1fr_180px_160px_160px_auto]"
                    >
                        <div>
                            <x-input-label for="search" :value="'Buscar'" />
                            <x-text-input id="search" name="search" type="text" class="mt-1 block w-full" :value="$filters['search']" placeholder="Concepto o descripción" />
                        </div>

                        <div>
                            <x-input-label for="category" :value="'Categoría'" />
                            <x-clearable-select id="category" name="category" :selected="$filters['category']">
                                <option value="">Todas</option>
                                <option value="ingreso" @selected($filters['category'] === 'ingreso')>Ingreso</option>
                                <option value="gasto" @selected($filters['category'] === 'gasto')>Gasto</option>
                            </x-clearable-select>
                        </div>

                        <div>
                            <x-input-label for="date_from" :value="'Desde'" />
                            <x-text-input id="date_from" name="date_from" type="date" class="mt-1 block w-full" :value="$filters['date_from']" />
                        </div>

                        <div>
                            <x-input-label for="date_to" :value="'Hasta'" />
                            <x-text-input id="date_to" name="date_to" type="date" class="mt-1 block w-full" :value="$filters['date_to']" />
                        </div>

                        <div class="flex items-end">
                            <x-primary-button class="w-full justify-center md:w-auto">Filtrar</x-primary-button>
                        </div>
                    </div>
                </form>
            </section>

            <div class="overflow-hidden rounded-3xl border border-stone-200 bg-white shadow-sm" data-ajax-table>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-stone-200 text-sm">
                        <thead class="bg-stone-50 text-left text-stone-500">
                            <tr>
                                <th class="px-6 py-4 font-medium">Fecha</th>
                                <th class="px-6 py-4 font-medium">Valor</th>
                                <th class="px-6 py-4 font-medium">Categoría</th>
                                <th class="px-6 py-4 font-medium">Concepto</th>
                                <th class="px-6 py-4 font-medium">Descripción</th>
                                <th class="px-6 py-4 font-medium"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100" x-ref="tbody">
                            @include('vehiculo._table_body', ['records' => $records])
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-stone-200 px-6 py-4" x-ref="pagination" data-ajax-pagination>
                    {{ $records->links() }}
                </div>
            </div>

            <div x-ref="summary">
                @include('vehiculo._summary', ['summary' => $summary])
            </div>

            <x-ajax-crud-modal />
            <x-ajax-crud-toast />
        </div>
    </div>
</x-public-layout>
