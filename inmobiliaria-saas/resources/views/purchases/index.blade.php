<x-app-layout x-data="crudTable({ flash: {{ \Illuminate\Support\Js::from(session('status')) }} })" x-on:click="handleClick($event)">
    <x-slot name="header">
        <x-page-header title="Compras" description="">
        </x-page-header>
    </x-slot>

    @can('create', App\Models\Purchase::class)
        <button type="button" data-action="create" data-url="{{ route('invoices.create', ['type' => 'purchase']) }}" data-title="Nueva factura" class="app-create-text-fab" title="Nueva factura">
            + Factura
        </button>
        <button type="button" data-action="create" data-url="{{ route('purchases.create', array_filter(['project_id' => $filters['project_id']])) }}" data-title="Nueva compra" class="app-create-button" title="Nueva compra">+</button>
    @endcan

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section x-data="{ filtersOpen: window.innerWidth >= 768 }" class="rounded-3xl border border-stone-200 bg-white shadow-sm">
                <div class="flex items-center justify-between gap-3 px-5 py-4">
                    <h2 class="text-sm font-semibold text-stone-900">Filtros</h2>

                    <button
                        type="button"
                        class="inline-flex items-center rounded-2xl border border-stone-300 px-3 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-50 md:hidden"
                        x-on:click="filtersOpen = !filtersOpen"
                        x-text="filtersOpen ? 'Ocultar' : 'Expandir'"
                    ></button>
                </div>

                <form
                    method="GET"
                    class="border-t border-stone-200 p-5"
                >
                    <div
                        x-show="filtersOpen"
                        x-transition:enter="transition ease-out duration-[875ms]"
                        x-transition:enter-start="opacity-0 -translate-y-2"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-[613ms]"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 -translate-y-2"
                        class="grid gap-4 md:grid md:grid-cols-[1fr_180px_220px_180px_180px_180px_auto]"
                        x-cloak
                    >
                        <div>
                            <x-input-label for="search" :value="'Buscar'" />
                            <x-text-input id="search" name="search" type="text" class="mt-1 block w-full" :value="$filters['search']" placeholder="Descripción, proyecto, producto o proveedor" />
                        </div>

                        @if (auth()->user()->isSuperAdmin())
                            <div>
                                <x-input-label for="company_id" :value="'Empresa'" />
                                <select id="company_id" name="company_id" class="mt-1 block w-full rounded-2xl border-stone-300 shadow-sm focus:border-stone-900 focus:ring-stone-900">
                                    <option value="">Todas las empresas</option>
                                    @foreach ($companies as $company)
                                        <option value="{{ $company->id }}" @selected((string) $filters['company_id'] === (string) $company->id)>{{ $company->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div>
                            <x-input-label for="project_id" :value="'Proyecto'" />
                            <select id="project_id" name="project_id" class="mt-1 block w-full rounded-2xl border-stone-300 shadow-sm focus:border-stone-900 focus:ring-stone-900">
                                <option value="">Todos los proyectos</option>
                                @foreach ($projects as $project)
                                    <option value="{{ $project->id }}" @selected((string) $filters['project_id'] === (string) $project->id)>{{ $project->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <x-input-label for="transaction_view" :value="'Vista'" />
                            <select id="transaction_view" name="transaction_view" class="mt-1 block w-full rounded-2xl border-stone-300 shadow-sm focus:border-stone-900 focus:ring-stone-900">
                                <option value="" @selected($filters['transaction_view'] === '')>Todo</option>
                                <option value="individual" @selected($filters['transaction_view'] === 'individual')>Gastos Individuales</option>
                                <option value="invoice" @selected($filters['transaction_view'] === 'invoice')>Facturas</option>
                            </select>
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

            <div class="overflow-hidden rounded-3xl border border-stone-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-stone-200 whitespace-nowrap text-sm">
                        <thead class="bg-stone-50 text-left text-stone-500">
                            <tr>
                                <th class="w-32 whitespace-nowrap px-6 py-4 font-medium">Fecha</th>
                                <th class="px-6 py-4 font-medium">Proyecto</th>
                                <th class="w-80 px-6 py-4 font-medium">Producto</th>
                                <th class="px-6 py-4 font-medium">Factura</th>
                                <th class="px-6 py-4 font-medium">Proveedor</th>
                                <th class="px-6 py-4 font-medium">Total</th>
                                <th class="px-6 py-4 font-medium">Estado</th>
                                <th class="px-6 py-4 font-medium"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100" x-ref="tbody">
                            @include('purchases._table_body', ['purchases' => $purchases])
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-stone-200 px-6 py-4">{{ $purchases->links() }}</div>
            </div>

            <x-ajax-crud-modal />
            <x-ajax-crud-toast />
        </div>
    </div>
</x-app-layout>
