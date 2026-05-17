<x-app-layout x-data="crudTable()" x-on:click="handleClick($event)">
    <x-slot name="header">
        <x-page-header title="Auditoría" description="Consulta la trazabilidad de acciones del SaaS y filtra por empresa, proyecto, evento o módulo." />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <form method="GET" class="grid gap-4 rounded-3xl border border-stone-200 bg-white p-5 shadow-sm md:grid-cols-[220px_220px_180px_180px_180px_180px_auto]">
                @if (auth()->user()->isSuperAdmin())
                    <div>
                        <x-input-label for="company_id" :value="'Empresa'" />
                        <select id="company_id" name="company_id" class="mt-1 block w-full rounded-2xl border-stone-300 shadow-sm focus:border-stone-900 focus:ring-stone-900">
                            <option value="">Todas</option>
                            @foreach ($companies as $company)
                                <option value="{{ $company->id }}" @selected((string) $filters['company_id'] === (string) $company->id)>{{ $company->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div>
                    <x-input-label for="project_id" :value="'Proyecto'" />
                    <select id="project_id" name="project_id" class="mt-1 block w-full rounded-2xl border-stone-300 shadow-sm focus:border-stone-900 focus:ring-stone-900">
                        <option value="">Todos</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}" @selected((string) $filters['project_id'] === (string) $project->id)>{{ $project->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <x-input-label for="log_name" :value="'Módulo'" />
                    <select id="log_name" name="log_name" class="mt-1 block w-full rounded-2xl border-stone-300 shadow-sm focus:border-stone-900 focus:ring-stone-900">
                        <option value="">Todos</option>
                        @foreach ($logNames as $item)
                            <option value="{{ $item }}" @selected($filters['log_name'] === $item)>{{ $item }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <x-input-label for="event" :value="'Evento'" />
                    <select id="event" name="event" class="mt-1 block w-full rounded-2xl border-stone-300 shadow-sm focus:border-stone-900 focus:ring-stone-900">
                        <option value="">Todos</option>
                        @foreach ($events as $item)
                            <option value="{{ $item }}" @selected($filters['event'] === $item)>{{ $item }}</option>
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

            <div class="overflow-hidden rounded-3xl border border-stone-200 bg-white shadow-sm" data-ajax-table>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-stone-200 text-sm">
                        <thead class="bg-stone-50 text-left text-stone-500">
                            <tr>
                                <th class="px-6 py-4 font-medium">Fecha</th>
                                <th class="px-6 py-4 font-medium">Evento</th>
                                <th class="px-6 py-4 font-medium">Usuario</th>
                                <th class="px-6 py-4 font-medium">Empresa / Proyecto</th>
                                <th class="px-6 py-4 font-medium">Registro</th>
                                <th class="px-6 py-4 font-medium">Detalle</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100" x-ref="tbody">
                            @include('audit._table_body', ['activities' => $activities])
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-stone-200 px-6 py-4" x-ref="pagination" data-ajax-pagination>
                    {{ $activities->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
