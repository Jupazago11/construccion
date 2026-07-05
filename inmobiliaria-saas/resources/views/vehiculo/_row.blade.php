<tr data-row-id="{{ $record->id }}" class="transition hover:bg-stone-50">
    <td class="whitespace-nowrap px-6 py-4 text-stone-900">
        {{ $record->record_date?->format('Y-m-d') }}
    </td>
    <td class="whitespace-nowrap px-6 py-4 text-stone-900">
        $ {{ number_format((float) $record->amount, 0, ',', '.') }}
    </td>
    <td class="px-6 py-4">
        <span class="inline-flex rounded-full border px-3 py-1 text-xs font-semibold uppercase {{ $record->category === 'ingreso' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-rose-200 bg-rose-50 text-rose-700' }}">
            {{ $record->category === 'ingreso' ? 'Ingreso' : 'Gasto' }}
        </span>
    </td>
    <td class="px-6 py-4 text-stone-600">
        {{ $record->concept }}
    </td>
    <td class="px-6 py-4 text-stone-600">
        {{ $record->description ?: '—' }}
    </td>
    <td class="px-6 py-4">
        <div class="flex items-center justify-end gap-3">
            <button
                type="button"
                data-action="edit"
                data-url="{{ route('vehiculo.edit', ['record' => $record] + request()->query()) }}"
                data-title="Editar registro"
                class="rounded-2xl border border-stone-200 p-2.5 text-stone-600 transition hover:bg-stone-100 hover:text-stone-900"
                title="Editar"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M17.414 2.586a2 2 0 010 2.828l-8.5 8.5a2 2 0 01-.878.497l-3 1a1 1 0 01-1.265-1.265l1-3a2 2 0 01.497-.878l8.5-8.5a2 2 0 012.828 0z" />
                </svg>
            </button>
            <button
                type="button"
                data-action="delete"
                data-url="{{ route('vehiculo.destroy', ['record' => $record] + request()->query()) }}"
                data-confirm-message="¿Deseas archivar este registro?"
                class="rounded-2xl border border-rose-200 p-2.5 text-rose-700 transition hover:bg-rose-50"
                title="Archivar"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8.257 3.099c.366-.446.911-.699 1.486-.699h.514c.575 0 1.12.253 1.486.699L12.85 4H16a1 1 0 110 2h-1l-.867 10.142A2 2 0 0112.14 18H7.86a2 2 0 01-1.993-1.858L5 6H4a1 1 0 010-2h3.15l1.107-.901zM8 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm4-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>
    </td>
</tr>
