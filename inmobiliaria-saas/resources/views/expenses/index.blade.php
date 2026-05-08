<x-app-layout x-data="crudTable({ flash: {{ \Illuminate\Support\Js::from(session('status')) }} })" x-on:click="handleClick($event)">
    <x-slot name="header">
        <x-page-header title="Gastos" description="Registra y administra los gastos reales del proyecto con su clasificación financiera completa.">
            @can('create', App\Models\Expense::class)
                <button type="button" data-action="create" data-url="{{ route('expenses.create') }}" data-title="Nuevo gasto" class="rounded-2xl bg-stone-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-stone-700">
                    Nuevo gasto
                </button>
            @endcan
        </x-page-header>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <form method="GET" class="grid gap-4 rounded-3xl border border-stone-200 bg-white p-5 shadow-sm md:grid-cols-[1fr_220px_220px_180px_180px_180px_auto]">
                <div>
                    <x-input-label for="search" :value="'Buscar'" />
                    <x-text-input id="search" name="search" type="text" class="mt-1 block w-full" :value="$filters['search']" placeholder="Número, descripción, proyecto o proveedor" />
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
                    <x-input-label for="status" :value="'Estado'" />
                    <select id="status" name="status" class="mt-1 block w-full rounded-2xl border-stone-300 shadow-sm focus:border-stone-900 focus:ring-stone-900">
                        <option value="">Todos</option>
                        @foreach (['active', 'inactive', 'deleted'] as $status)
                            <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ ['active' => 'Activo', 'inactive' => 'Inactivo', 'deleted' => 'Eliminado'][$status] }}</option>
                        @endforeach
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
            </form>

            <div class="overflow-hidden rounded-3xl border border-stone-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-stone-200 text-sm">
                        <thead class="bg-stone-50 text-left text-stone-500">
                            <tr>
                                <th class="px-6 py-4 font-medium">Gasto</th>
                                <th class="px-6 py-4 font-medium">Proyecto</th>
                                <th class="px-6 py-4 font-medium">Clasificación</th>
                                <th class="px-6 py-4 font-medium">Proveedor</th>
                                <th class="px-6 py-4 font-medium">Total</th>
                                <th class="px-6 py-4 font-medium">Estado</th>
                                <th class="px-6 py-4 font-medium"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100" x-ref="tbody">
                            @forelse ($expenses as $expense)
                                @include('expenses._row', ['expense' => $expense])
                            @empty
                                <tr data-empty-state>
                                    <td colspan="7" class="px-6 py-10 text-center text-stone-500">
                                        No se encontraron gastos con los filtros actuales.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-stone-200 px-6 py-4">
                    {{ $expenses->links() }}
                </div>
            </div>

            <x-ajax-crud-modal />
            <x-ajax-crud-toast />
        </div>
    </div>
</x-app-layout>
