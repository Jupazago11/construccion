@php
    $statusOptions = auth()->user()->isSuperAdmin()
        ? ['planning', 'active', 'paused', 'completed', 'cancelled', 'deleted']
        : ['planning', 'active', 'paused', 'completed', 'cancelled'];
    $isPastelProject = (($loop->index ?? $project->id) % 2) === 0;
    $projectTheme = $isPastelProject
        ? 'border-sky-200 bg-sky-50/40 hover:border-sky-300'
        : 'border-stone-200 bg-white hover:border-stone-300';

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

<article data-row-id="{{ $project->id }}" class="rounded-3xl border {{ $projectTheme }} p-6 shadow-sm transition hover:shadow-md">
    <div class="flex flex-col gap-4">
        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0 space-y-1">
                <h3 class="break-words text-xl font-semibold text-stone-900">{{ $project->name }}</h3>
                <p class="text-sm text-stone-500">{{ $project->project_type ?: 'Sin tipo definido' }}</p>
            </div>

            <div class="flex shrink-0 items-center gap-2">
                <button
                    type="button"
                    data-action="status-modal"
                    data-url="{{ route('projects.status', $project) }}"
                    data-current-status="{{ $project->status }}"
                    data-status-options='@json($statusOptions)'
                    data-entity-label="proyecto"
                    title="Cambiar estado"
                >
                    <x-status-badge :value="$project->status" class="cursor-pointer transition hover:opacity-80" />
                </button>

                <button
                    type="button"
                    data-action="delete"
                    data-url="{{ route('projects.destroy', $project) }}"
                    data-confirm-message="¿Deseas archivar este proyecto? Solo se permite si no tiene dependencias."
                    class="rounded-2xl border border-rose-200 bg-rose-50 p-2 text-rose-700 transition hover:bg-rose-100"
                    title="Eliminar"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.366-.446.911-.699 1.486-.699h.514c.575 0 1.12.253 1.486.699L12.85 4H16a1 1 0 110 2h-1l-.867 10.142A2 2 0 0112.14 18H7.86a2 2 0 01-1.993-1.858L5 6H4a1 1 0 010-2h3.15l1.107-.901zM8 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm4-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
        </div>

        <div class="grid gap-4 text-sm text-stone-600 sm:grid-cols-2">
            <div class="rounded-2xl bg-white/85 px-4 py-3 ring-1 ring-stone-200">
                <p class="text-xs font-medium uppercase tracking-[0.18em] text-stone-400">Ubicacion</p>
                <p class="mt-2">{{ $project->city ?: 'Sin ciudad' }}</p>
                <p>{{ $project->country ?: 'Sin pais' }}</p>
            </div>

            <div class="rounded-2xl bg-white/85 px-4 py-3 ring-1 ring-stone-200">
                <p class="text-xs font-medium uppercase tracking-[0.18em] text-stone-400">Inicio</p>
                <p class="mt-2">{{ $project->start_date?->format('Y-m-d') ?: 'Sin fecha' }}</p>
                @if ($elapsedTime)
                    <p class="mt-1 text-xs font-semibold text-stone-600">Tiempo: {{ $elapsedTime }}</p>
                @endif
            </div>
        </div>

        @if ($project->description)
            <div class="rounded-2xl border border-stone-200 bg-white/85 px-4 py-3 text-sm text-stone-600">
                {{ $project->description }}
            </div>
        @endif

        <div class="grid grid-cols-[2.5rem_1fr_2.5rem] items-center gap-3 border-t border-stone-200 pt-4">
            <div></div>

            <a
                href="{{ route('projects.categories.index', $project) }}"
                class="inline-flex min-w-0 items-center justify-center rounded-2xl border border-sky-200 bg-sky-50 px-4 py-2 text-sm font-semibold text-sky-900 transition hover:border-sky-300 hover:bg-sky-100"
            >
                Ver Proyecto
            </a>

            <button
                type="button"
                data-action="edit"
                data-url="{{ route('projects.edit', $project) }}"
                data-title="Editar proyecto"
                class="rounded-2xl border border-amber-200 bg-amber-50 p-2 text-amber-800 transition hover:bg-amber-100"
                title="Editar"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M17.414 2.586a2 2 0 010 2.828l-8.5 8.5a2 2 0 01-.878.497l-3 1a1 1 0 01-1.265-1.265l1-3a2 2 0 01.497-.878l8.5-8.5a2 2 0 012.828 0z" />
                </svg>
            </button>
        </div>
    </div>
</article>
