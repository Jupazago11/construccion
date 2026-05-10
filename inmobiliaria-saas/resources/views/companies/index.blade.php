<x-app-layout x-data="crudTable({ flash: {{ \Illuminate\Support\Js::from(session('status')) }} })" x-on:click="handleClick($event)">
    <x-slot name="header">
        <x-page-header title="Empresas" description="Administra las empresas del SaaS, su estado operativo y la base multiempresa de la plataforma.">
            @can('create', App\Models\Company::class)
                <button type="button" data-action="create" data-url="{{ route('companies.create') }}" data-title="Nueva empresa" class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-emerald-200 bg-emerald-50 text-xl font-semibold leading-none text-emerald-900 transition hover:border-emerald-300 hover:bg-emerald-100" title="Nueva empresa">
                    +
                </button>
            @endcan
        </x-page-header>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <form method="GET" class="grid gap-4 rounded-3xl border border-stone-200 bg-white p-5 shadow-sm md:grid-cols-[1fr_220px_auto]">
                <div>
                    <x-input-label for="search" :value="'Buscar'" />
                    <x-text-input id="search" name="search" type="text" class="mt-1 block w-full" :value="$filters['search']" placeholder="Nombre, razón social, NIT o correo" />
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

                <div class="flex items-end gap-3">
                    <x-primary-button class="w-full justify-center md:w-auto">Filtrar</x-primary-button>
                </div>
            </form>

            <div class="overflow-hidden rounded-3xl border border-stone-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-stone-200 text-sm">
                        <thead class="bg-stone-50 text-left text-stone-500">
                            <tr>
                                <th class="px-6 py-4 font-medium">Empresa</th>
                                <th class="px-6 py-4 font-medium">Estado</th>
                                <th class="px-6 py-4 font-medium">Usuarios</th>
                                <th class="px-6 py-4 font-medium">Proyectos</th>
                                <th class="px-6 py-4 font-medium">Contacto</th>
                                <th class="px-6 py-4 font-medium"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100" x-ref="tbody">
                            @forelse ($companies as $company)
                                @include('companies._row', ['company' => $company])
                            @empty
                                <tr data-empty-state>
                                    <td colspan="6" class="px-6 py-10 text-center text-stone-500">
                                        No se encontraron empresas con los filtros actuales.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-stone-200 px-6 py-4">
                    {{ $companies->links() }}
                </div>
            </div>

            <x-ajax-crud-modal />
            <x-ajax-crud-toast />
        </div>
    </div>
</x-app-layout>
