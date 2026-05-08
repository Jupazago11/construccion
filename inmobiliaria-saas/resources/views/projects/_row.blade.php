@php($statusOptions = ['planning', 'active', 'paused', 'completed', 'cancelled', 'deleted'])

<tr class="align-top" data-row-id="{{ $project->id }}">
    <td class="px-6 py-4">
        <div class="font-semibold text-stone-900">{{ $project->name }}</div>
        <div class="text-stone-500">{{ $project->project_type ?: 'Sin tipo definido' }}</div>
    </td>
    <td class="px-6 py-4 text-stone-600">
        {{ $project->company?->name ?: 'Sin empresa' }}
    </td>
    <td class="px-6 py-4 text-stone-600">
        <div>{{ $project->city ?: 'Sin ciudad' }}</div>
        <div>{{ $project->country ?: 'Sin país' }}</div>
    </td>
    <td class="px-6 py-4 text-stone-600">
        {{ $project->start_date?->format('Y-m-d') ?: 'Sin fecha' }}
    </td>
    <td class="px-6 py-4">
        <button
            type="button"
            data-action="status-modal"
            data-url="{{ route('projects.status', $project) }}"
            data-current-status="{{ $project->status }}"
            data-status-options='@json($statusOptions)'
            data-entity-label="proyecto"
        >
            <x-status-badge :value="$project->status" class="cursor-pointer transition hover:opacity-80" />
        </button>
    </td>
    <td class="px-6 py-4">
        <div class="flex items-center justify-end gap-2">
            <a
                href="{{ route('projects.categories.index', $project) }}"
                class="rounded-2xl border border-stone-200 p-2 text-stone-600 transition hover:bg-stone-100 hover:text-stone-900"
                title="Estructura financiera"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M3 5a2 2 0 012-2h3.586A2 2 0 0110 3.586L11.414 5H15a2 2 0 012 2v1H3V5z" />
                    <path d="M3 10h14v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5z" />
                </svg>
            </a>
            <button
                type="button"
                data-action="edit"
                data-url="{{ route('projects.edit', $project) }}"
                data-title="Editar proyecto"
                class="rounded-2xl border border-stone-200 p-2 text-stone-600 transition hover:bg-stone-100 hover:text-stone-900"
                title="Editar"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M17.414 2.586a2 2 0 010 2.828l-8.5 8.5a2 2 0 01-.878.497l-3 1a1 1 0 01-1.265-1.265l1-3a2 2 0 01.497-.878l8.5-8.5a2 2 0 012.828 0z" />
                </svg>
            </button>
            <button
                type="button"
                data-action="delete"
                data-url="{{ route('projects.destroy', $project) }}"
                data-confirm-message="¿Deseas archivar este proyecto? Solo se permite si no tiene dependencias."
                class="rounded-2xl border border-rose-200 p-2 text-rose-700 transition hover:bg-rose-50"
                title="Eliminar"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8.257 3.099c.366-.446.911-.699 1.486-.699h.514c.575 0 1.12.253 1.486.699L12.85 4H16a1 1 0 110 2h-1l-.867 10.142A2 2 0 0112.14 18H7.86a2 2 0 01-1.993-1.858L5 6H4a1 1 0 010-2h3.15l1.107-.901zM8 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm4-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>
    </td>
</tr>
