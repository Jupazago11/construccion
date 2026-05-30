@php
    $statusOptions = auth()->user()->isSuperAdmin()
        ? ['planning', 'active', 'paused', 'completed', 'cancelled', 'deleted']
        : ['planning', 'active', 'paused', 'completed', 'cancelled'];
    $canDeleteProject = (($project->active_categories_count ?? 0) === 0) && (($project->active_expenses_count ?? 0) === 0);

    $elapsedTime = null;

    if ($project->start_date) {
        $diff = $project->start_date->copy()->startOfDay()->diff(now()->startOfDay());
        $parts = [];

        if ($diff->y > 0) {
            $parts[] = $diff->y.' '.($diff->y === 1 ? 'año' : 'años');
        }

        if ($diff->m > 0) {
            $parts[] = $diff->m.' '.($diff->m === 1 ? 'mes' : 'meses');
        }

        if ($diff->d > 0 || $parts === []) {
            $parts[] = $diff->d.' '.($diff->d === 1 ? 'día' : 'días');
        }

        $elapsedTime = implode(' y ', $parts);
    }
@endphp

<article data-row-id="{{ $project->id }}" class="rounded-2xl border border-stone-200 bg-white shadow-sm transition hover:border-stone-300 hover:shadow-md">
    <div class="flex h-full flex-col">
        <div class="flex items-start justify-between gap-4 border-b border-stone-100 px-5 py-4">
            <div class="min-w-0">
                <h3 class="break-words text-base font-semibold leading-6 text-stone-950">{{ $project->name }}</h3>
                <p class="mt-1 text-sm text-stone-500">{{ $project->project_type ?: 'Sin tipo definido' }}</p>
            </div>

            <div class="flex shrink-0 flex-wrap items-center justify-end gap-2">
                <button
                    type="button"
                    data-action="status-modal"
                    data-url="{{ route('projects.status', $project) }}"
                    data-current-status="{{ $project->status }}"
                    data-status-options='@json($statusOptions)'
                    data-entity-label="proyecto"
                    title="Cambiar estado"
                >
                    <x-status-badge
                        :value="$project->status"
                        :label="$project->status === 'active' ? 'En construcción' : null"
                        class="cursor-pointer transition hover:opacity-85"
                    />
                </button>

                <button
                    type="button"
                    data-action="edit"
                    data-url="{{ route('projects.edit', $project) }}"
                    data-title="Editar proyecto"
                    class="rounded-xl border border-stone-200 bg-white p-2 text-stone-500 transition hover:border-stone-300 hover:bg-stone-50 hover:text-stone-900"
                    title="Editar"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M17.414 2.586a2 2 0 010 2.828l-8.5 8.5a2 2 0 01-.878.497l-3 1a1 1 0 01-1.265-1.265l1-3a2 2 0 01.497-.878l8.5-8.5a2 2 0 012.828 0z" />
                    </svg>
                </button>

                @if ($canDeleteProject)
                    <button
                        type="button"
                        data-action="delete"
                        data-url="{{ route('projects.destroy', $project) }}"
                        data-confirm-message="¿Deseas archivar este proyecto? Solo se permite si no tiene dependencias."
                        class="rounded-xl border border-stone-200 bg-white p-2 text-stone-400 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700"
                        title="Eliminar"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.366-.446.911-.699 1.486-.699h.514c.575 0 1.12.253 1.486.699L12.85 4H16a1 1 0 110 2h-1l-.867 10.142A2 2 0 0112.14 18H7.86a2 2 0 01-1.993-1.858L5 6H4a1 1 0 010-2h3.15l1.107-.901zM8 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm4-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </button>
                @endif
            </div>
        </div>

        <div class="grid gap-0 border-b border-stone-100 text-sm sm:grid-cols-2">
            <div class="border-b border-stone-100 px-5 py-4 sm:border-b-0 sm:border-r">
                <p class="text-xs font-medium uppercase tracking-wide text-stone-400">Ubicación</p>
                <p class="mt-1 font-medium text-stone-800">{{ $project->city ?: 'Sin ciudad' }}</p>
                <p class="text-stone-500">{{ $project->country ?: 'Sin país' }}</p>
            </div>

            <div class="px-5 py-4">
                <p class="text-xs font-medium uppercase tracking-wide text-stone-400">Inicio</p>
                <button
                    type="button"
                    data-action="edit"
                    data-url="{{ route('projects.edit-date', $project) }}"
                    data-title="Cambiar fecha de inicio"
                    class="mt-1 block text-left font-medium text-stone-800 underline-offset-2 transition hover:text-stone-500 hover:underline"
                    title="Editar fecha"
                >{{ $project->start_date?->format('Y-m-d') ?: 'Sin fecha' }}</button>
                @if ($elapsedTime)
                    <p class="mt-1 text-xs text-stone-500">Tiempo: {{ $elapsedTime }}</p>
                @endif
            </div>
        </div>

        @if ($project->description)
            <div class="border-b border-stone-100 px-5 py-4 text-sm leading-6 text-stone-600">
                {{ $project->description }}
            </div>
        @endif

        <div class="mt-auto grid gap-2 px-5 py-4">
            <div class="grid grid-cols-1 gap-2">
                <a
                    href="{{ route('gastos2.index', ['project_id' => $project->id]) }}"
                    class="inline-flex min-w-0 items-center justify-center rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm font-semibold text-rose-800 transition hover:border-rose-300 hover:bg-rose-100 hover:text-rose-900"
                >
                    Gastos
                </a>
            </div>
        </div>
    </div>
</article>
