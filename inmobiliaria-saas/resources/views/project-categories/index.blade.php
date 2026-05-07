<x-app-layout x-data="crudTable({ flash: {{ \Illuminate\Support\Js::from(session('status')) }} })" x-on:click="handleClick($event)">
    <x-slot name="header">
        <x-page-header :title="'Estructura financiera: '.$project->name" description="Administra categorías, subcategorías y auxiliares del proyecto desde una sola vista.">
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('projects.show', $project) }}" class="rounded-2xl border border-stone-300 px-4 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-50">
                    Ver proyecto
                </a>
                @can('create', App\Models\Category::class)
                    <button
                        type="button"
                        data-action="create"
                        data-url="{{ route('projects.categories.copy.create', $project) }}"
                        data-title="Copiar estructura"
                        @disabled(! $projectAllowsNewRecords || $availableSourceProjects->isEmpty())
                        class="rounded-2xl border border-stone-300 px-4 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-50 disabled:cursor-not-allowed disabled:border-stone-200 disabled:text-stone-400"
                    >
                        Copiar estructura
                    </button>
                @endcan
                @can('create', App\Models\Category::class)
                    <button
                        type="button"
                        data-action="create"
                        data-url="{{ route('projects.categories.create', $project) }}"
                        data-title="Nueva categoría"
                        @disabled(! $projectAllowsNewRecords)
                        class="rounded-2xl bg-stone-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-stone-700 disabled:cursor-not-allowed disabled:bg-stone-300"
                    >
                        Nueva categoría
                    </button>
                @endcan
            </div>
        </x-page-header>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @unless ($projectAllowsNewRecords)
                <div class="rounded-3xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800">
                    Este proyecto está en estado <strong>{{ [
                        'planning' => 'Planeación',
                        'active' => 'Activo',
                        'paused' => 'Pausado',
                        'completed' => 'Completado',
                        'cancelled' => 'Cancelado',
                        'deleted' => 'Eliminado',
                    ][$project->status] ?? $project->status }}</strong>.
                    No se permiten nuevos registros en la estructura financiera.
                </div>
            @endunless

            @if ($projectAllowsNewRecords && $availableSourceProjects->isEmpty())
                <div class="rounded-3xl border border-stone-200 bg-stone-50 px-5 py-4 text-sm text-stone-600">
                    No hay otros proyectos de esta empresa con estructura disponible para copiar.
                </div>
            @endif

            <div x-ref="summary">
                @include('project-categories._summary', ['project' => $project, 'summary' => $summary])
            </div>

            <div x-ref="structure">
                @include('project-categories._structure', ['project' => $project, 'projectAllowsNewRecords' => $projectAllowsNewRecords])
            </div>

            <x-ajax-crud-modal />
            <x-ajax-crud-toast />
        </div>
    </div>
</x-app-layout>
