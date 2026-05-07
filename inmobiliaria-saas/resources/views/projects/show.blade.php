<x-app-layout>
    <x-slot name="header">
        <x-page-header :title="$project->name" description="Detalle del proyecto, estado actual y contexto operativo dentro de la empresa.">
            @can('update', $project)
                <a href="{{ route('projects.edit', $project) }}" class="rounded-2xl border border-stone-300 px-4 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-50">
                    Editar proyecto
                </a>
            @endcan
        </x-page-header>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('status') }}
                </div>
            @endif

            <div class="grid gap-4 md:grid-cols-3">
                <x-metric-card label="Categorías" :value="$project->categories_count" hint="Árbol financiero asociado" />
                <x-metric-card label="Gastos" :value="$project->expenses_count" hint="Registros financieros vinculados" />
                <x-metric-card label="Estado" :value="[
                    'planning' => 'Planeación',
                    'active' => 'Activo',
                    'paused' => 'Pausado',
                    'completed' => 'Completado',
                    'cancelled' => 'Cancelado',
                    'deleted' => 'Eliminado',
                ][$project->status] ?? $project->status" hint="Ciclo de vida actual" />
            </div>

            <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
                <div class="grid gap-6 md:grid-cols-2">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-stone-400">Empresa</p>
                        <p class="mt-1 text-stone-900">{{ $project->company?->name ?: 'Sin empresa' }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-stone-400">Estado</p>
                        <div class="mt-1"><x-status-badge :value="$project->status" /></div>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-stone-400">Tipo de proyecto</p>
                        <p class="mt-1 text-stone-700">{{ $project->project_type ?: 'No definido' }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-stone-400">Fecha de inicio</p>
                        <p class="mt-1 text-stone-700">{{ $project->start_date?->format('Y-m-d') ?: 'No definida' }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-stone-400">País</p>
                        <p class="mt-1 text-stone-700">{{ $project->country ?: 'No definido' }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-stone-400">Departamento / Estado</p>
                        <p class="mt-1 text-stone-700">{{ $project->state ?: 'No definido' }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-stone-400">Ciudad</p>
                        <p class="mt-1 text-stone-700">{{ $project->city ?: 'No definida' }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-stone-400">Dirección</p>
                        <p class="mt-1 text-stone-700">{{ $project->address ?: 'No definida' }}</p>
                    </div>
                    <div class="md:col-span-2">
                        <p class="text-xs uppercase tracking-wide text-stone-400">Referencia de ubicación</p>
                        <p class="mt-1 text-stone-700">{{ $project->location_reference ?: 'No definida' }}</p>
                    </div>
                    <div class="md:col-span-2">
                        <p class="text-xs uppercase tracking-wide text-stone-400">Descripción</p>
                        <p class="mt-1 whitespace-pre-line text-stone-700">{{ $project->description ?: 'Sin descripción registrada.' }}</p>
                    </div>
                </div>
            </div>

            @can('delete', $project)
                <div class="rounded-3xl border border-rose-200 bg-rose-50 p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-rose-800">Archivar proyecto</h2>
                    <p class="mt-2 text-sm text-rose-700">
                        Esto cambia el estado del proyecto a <code>deleted</code>. No elimina físicamente el registro.
                    </p>
                    <form method="POST" action="{{ route('projects.destroy', $project) }}" class="mt-4">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="rounded-2xl bg-rose-700 px-4 py-2 text-sm font-medium text-white transition hover:bg-rose-600">
                            Archivar proyecto
                        </button>
                    </form>
                </div>
            @endcan
        </div>
    </div>
</x-app-layout>
