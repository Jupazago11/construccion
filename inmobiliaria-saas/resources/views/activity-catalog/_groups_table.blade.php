<table class="min-w-full divide-y divide-stone-200 whitespace-nowrap text-sm">
    <thead class="bg-stone-50 text-left text-stone-500">
        <tr>
            <th class="px-5 py-3 font-medium">Grupo</th>
            <th class="px-5 py-3 font-medium">Estructura</th>
            <th class="px-5 py-3 font-medium">Estado</th>
            <th class="px-5 py-3 font-medium"></th>
        </tr>
    </thead>
    <tbody class="divide-y divide-stone-100">
        @forelse ($groups as $group)
            <tr>
                <td class="px-5 py-4 font-medium text-stone-900">{{ $group->name }}</td>
                <td class="px-5 py-4 text-stone-600">{{ $group->subgroups_count }} subgrupos · {{ $group->activities_count }} actividades</td>
                <td class="px-5 py-4">
                    @if ($group->status !== 'deleted')
                        <button type="button" x-on:click='toggleStatus("group", {{ $group->id }}, "{{ $group->status === 'active' ? 'inactive' : 'active' }}")'>
                            <x-status-badge :value="$group->status" class="cursor-pointer transition hover:opacity-80" />
                        </button>
                    @else
                        <x-status-badge :value="$group->status" />
                    @endif
                </td>
                <td class="px-5 py-4">
                    <div class="flex items-center justify-end gap-2">
                        <button type="button" class="rounded-2xl border border-stone-200 p-2 text-stone-600 transition hover:bg-stone-100 hover:text-stone-900" title="Editar" x-on:click="editRecord('group', { id: {{ $group->id }}, name: @js($group->name), company_id: {{ $group->company_id }}, status: @js($group->status) })">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M17.414 2.586a2 2 0 010 2.828l-8.5 8.5a2 2 0 01-.878.497l-3 1a1 1 0 01-1.265-1.265l1-3a2 2 0 01.497-.878l8.5-8.5a2 2 0 012.828 0z" />
                            </svg>
                        </button>
                        @if ($group->status !== 'deleted')
                            <button type="button" class="rounded-2xl border border-rose-200 p-2 text-rose-700 transition hover:bg-rose-50" title="Archivar" x-on:click='archiveRecord("group", {{ $group->id }})'>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.366-.446.911-.699 1.486-.699h.514c.575 0 1.12.253 1.486.699L12.85 4H16a1 1 0 110 2h-1l-.867 10.142A2 2 0 0112.14 18H7.86a2 2 0 01-1.993-1.858L5 6H4a1 1 0 010-2h3.15l1.107-.901zM8 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm4-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="px-5 py-8 text-center text-stone-500">No hay grupos registrados.</td></tr>
        @endforelse
    </tbody>
</table>
