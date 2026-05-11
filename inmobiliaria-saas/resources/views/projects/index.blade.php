<x-app-layout x-data="crudTable({ flash: {{ \Illuminate\Support\Js::from(session('status')) }}, reloadOnMutate: true })" x-on:click="handleClick($event)">
    <x-slot name="header">
        <x-page-header
            title="Proyectos"
            description="{{ auth()->user()->isSuperAdmin()
                ? 'Administra los proyectos de cada empresa y su estado operativo dentro del módulo financiero.'
                : '' }}"
        >
            @can('viewAny', App\Models\Asset::class)
                <a
                    href="{{ route('assets.index') }}"
                    class="inline-flex items-center rounded-2xl border border-amber-200 bg-amber-50 px-4 py-2 text-sm font-medium text-amber-900 transition hover:border-amber-300 hover:bg-amber-100"
                >
                    Activos
                </a>
            @endcan
            @can('create', App\Models\Project::class)
                <button
                    type="button"
                    data-action="create"
                    data-url="{{ route('projects.create') }}"
                    data-title="Nuevo proyecto"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-sky-200 bg-sky-50 text-xl font-semibold leading-none text-sky-900 transition hover:border-sky-300 hover:bg-sky-100"
                    title="Nuevo proyecto"
                >
                    +
                </button>
            @endcan
        </x-page-header>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (auth()->user()->isSuperAdmin())
                <form method="GET" class="grid gap-4 rounded-3xl border border-stone-200 bg-white p-5 shadow-sm md:grid-cols-[1fr_220px_220px_auto]">
                    <div>
                        <x-input-label for="search" :value="'Buscar'" />
                        <x-text-input id="search" name="search" type="text" class="mt-1 block w-full" :value="$filters['search']" placeholder="Nombre, ciudad, país o dirección" />
                    </div>

                    <div>
                        <x-input-label for="company_id" :value="'Empresa'" />
                        <select id="company_id" name="company_id" class="mt-1 block w-full rounded-2xl border-stone-300 shadow-sm focus:border-stone-900 focus:ring-stone-900">
                            <option value="">Todas las empresas</option>
                            @foreach ($companies as $company)
                                <option value="{{ $company->id }}" @selected((string) $filters['company_id'] === (string) $company->id)>
                                    {{ $company->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label for="status" :value="'Estado'" />
                        <select id="status" name="status" class="mt-1 block w-full rounded-2xl border-stone-300 shadow-sm focus:border-stone-900 focus:ring-stone-900">
                            <option value="">Todos</option>
                            @foreach ([
                                'planning' => 'En gestión',
                                'active' => 'Activo',
                                'paused' => 'Pausado',
                                'completed' => 'Completado',
                                'cancelled' => 'Cancelado',
                                'deleted' => 'Eliminado',
                            ] as $statusValue => $statusLabel)
                                <option value="{{ $statusValue }}" @selected($filters['status'] === $statusValue)>{{ $statusLabel }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-end">
                        <x-primary-button class="w-full justify-center md:w-auto">Filtrar</x-primary-button>
                    </div>
                </form>
            @endif

            @if (auth()->user()->isSuperAdmin())
                <div class="overflow-hidden rounded-3xl border border-stone-200 bg-white shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-stone-200 text-sm">
                            <thead class="bg-stone-50 text-left text-stone-500">
                                <tr>
                                    <th class="px-6 py-4 font-medium">Proyecto</th>
                                    <th class="px-6 py-4 font-medium">Empresa</th>
                                    <th class="px-6 py-4 font-medium">Ubicación</th>
                                    <th class="px-6 py-4 font-medium">Inicio</th>
                                    <th class="px-6 py-4 font-medium">Estado</th>
                                    <th class="px-6 py-4 font-medium"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-stone-100" x-ref="tbody">
                                @forelse ($projects as $project)
                                    @include('projects._row', ['project' => $project])
                                @empty
                                    <tr data-empty-state>
                                        <td colspan="6" class="px-6 py-10 text-center text-stone-500">
                                            No se encontraron proyectos con los filtros actuales.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($projects->hasPages())
                        <div class="border-t border-stone-200 px-6 py-4">
                            {{ $projects->links() }}
                        </div>
                    @endif
                </div>
            @else
                <div class="grid gap-4 md:grid-cols-2 md:gap-5 xl:grid-cols-3" x-ref="tbody">
                    @forelse ($projects as $project)
                        @if (! $loop->first)
                            <div class="h-px bg-stone-200/80 md:hidden" aria-hidden="true"></div>
                        @endif
                        @include('projects._card', ['project' => $project])
                    @empty
                        <div data-empty-state class="rounded-3xl border border-dashed border-stone-300 bg-white px-6 py-12 text-center text-stone-500 md:col-span-2 xl:col-span-3">
                            No se encontraron proyectos registrados.
                        </div>
                    @endforelse
                </div>

                @if ($projects->hasPages())
                    <div class="rounded-3xl border border-stone-200 bg-white px-6 py-4 shadow-sm">
                        {{ $projects->links() }}
                    </div>
                @endif
            @endif

            <x-ajax-crud-modal />
            <x-ajax-crud-toast />
        </div>
    </div>
</x-app-layout>
