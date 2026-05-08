@php
    $isSelf = $user->is(auth()->user());
@endphp

<tr data-row-id="{{ $user->id }}">
    <td class="px-6 py-4">
        <div class="font-semibold text-stone-900">{{ $user->name }}</div>
        <div class="text-stone-500">{{ '@'.$user->username }}</div>
        <div class="text-stone-500">{{ $user->email }}</div>
    </td>
    <td class="px-6 py-4 text-stone-600">
        {{ $user->company?->name ?: 'Alcance SuperAdmin' }}
    </td>
    <td class="px-6 py-4 text-stone-600">
        {{ $user->roles->pluck('name')->join(', ') ?: 'Sin rol asignado' }}
    </td>
    <td class="px-6 py-4">
        @if ($isSelf || ! auth()->user()->isSuperAdmin())
            <div class="inline-flex">
                <x-status-badge :value="$user->status" />
            </div>
        @else
            <button
                type="button"
                data-action="status"
                data-url="{{ route('users.status', $user) }}"
                data-current-status="{{ $user->status }}"
                data-status-options='@json(["active", "inactive", "deleted"])'
            >
                <x-status-badge :value="$user->status" class="cursor-pointer transition hover:opacity-80" />
            </button>
        @endif
    </td>
    <td class="px-6 py-4">
        <div class="flex items-center justify-end gap-2">
            <button
                type="button"
                data-action="edit"
                data-url="{{ route('users.edit', $user) }}"
                data-title="Editar usuario"
                class="rounded-2xl border border-stone-200 p-2 text-stone-600 transition hover:bg-stone-100 hover:text-stone-900"
                title="Editar"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M17.414 2.586a2 2 0 010 2.828l-8.5 8.5a2 2 0 01-.878.497l-3 1a1 1 0 01-1.265-1.265l1-3a2 2 0 01.497-.878l8.5-8.5a2 2 0 012.828 0z" />
                </svg>
            </button>
            @unless ($isSelf)
                <button
                    type="button"
                    data-action="delete"
                    data-url="{{ route('users.destroy', $user) }}"
                    data-confirm-message="¿Deseas archivar este usuario? Solo se permite si no tiene dependencias."
                    class="rounded-2xl border border-rose-200 p-2 text-rose-700 transition hover:bg-rose-50"
                    title="Eliminar"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.366-.446.911-.699 1.486-.699h.514c.575 0 1.12.253 1.486.699L12.85 4H16a1 1 0 110 2h-1l-.867 10.142A2 2 0 0112.14 18H7.86a2 2 0 01-1.993-1.858L5 6H4a1 1 0 010-2h3.15l1.107-.901zM8 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm4-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </button>
            @endunless
        </div>
    </td>
</tr>
