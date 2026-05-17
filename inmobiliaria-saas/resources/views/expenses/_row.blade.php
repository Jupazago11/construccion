@php($catalogItem = $expense->activity ?: $expense->product)
@php($catalogGroup = $expense->activity?->group ?: $expense->product?->group)
@php($catalogSubgroup = $expense->activity?->subgroup ?: $expense->product?->subgroup)
<tr data-row-id="{{ $expense->id }}">
    <td class="w-32 whitespace-nowrap px-6 py-4">
        <div class="font-semibold text-stone-900">{{ $expense->expense_date?->format('Y-m-d') ?: 'Sin fecha' }}</div>
    </td>
    <td class="px-6 py-4 text-stone-600">
        {{ $expense->project?->name ?: 'Sin proyecto' }}
    </td>
    <td class="w-80 px-6 py-4 text-stone-600">
        <div class="font-medium text-stone-700">{{ $catalogItem?->name ?: 'Sin producto o actividad' }}</div>
        @if ($catalogGroup || $catalogSubgroup)
            <div class="text-xs text-stone-500">
                {{ $catalogGroup?->name ?: 'Sin grupo' }} · {{ $catalogSubgroup?->name ?: 'Sin subgrupo' }}
            </div>
        @endif
        @if ($expense->quantity && $expense->quantity != 1)
            <div class="text-xs text-stone-500">{{ number_format((float) $expense->quantity, 2, ',', '.') }} x $ {{ number_format((float) $expense->unit_price, 0, ',', '.') }}</div>
        @endif
    </td>
    <td class="px-6 py-4 text-stone-600">
        {{ $expense->invoice?->invoice_number ?: 'Sin factura' }}
    </td>
    <td class="px-6 py-4 text-stone-600">
        {{ $expense->provider?->name ?: 'Sin proveedor' }}
    </td>
    <td class="px-6 py-4 text-stone-900">
        $ {{ number_format((float) $expense->total_amount, 0, ',', '.') }}
    </td>
    <td class="px-6 py-4">
        <button
            type="button"
            data-action="status"
            data-url="{{ route('expenses.status', $expense) }}"
            data-current-status="{{ $expense->status }}"
            data-status-options='@json(["active", "inactive"])'
        >
            <x-status-badge :value="$expense->status" class="cursor-pointer transition hover:opacity-80" />
        </button>
    </td>
    <td class="px-6 py-4">
        <div class="flex items-center justify-end gap-2">
            <a
                href="{{ route('expenses.attachments.index', $expense) }}"
                class="rounded-2xl border border-stone-200 p-2 text-stone-600 transition hover:bg-stone-100 hover:text-stone-900"
                title="Archivos adjuntos"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8 2a3 3 0 00-3 3v7a5 5 0 0010 0V6a1 1 0 10-2 0v6a3 3 0 11-6 0V5a1 1 0 112 0v7a1 1 0 102 0V7a1 1 0 112 0v5a3 3 0 11-6 0V5a3 3 0 016 0v7a5 5 0 11-10 0V5a5 5 0 0110 0v6a1 1 0 11-2 0V5a3 3 0 00-3-3z" clip-rule="evenodd" />
                </svg>
            </a>
            <button
                type="button"
                data-action="edit"
                data-url="{{ route('expenses.edit', $expense) }}"
                data-title="Editar gasto"
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
                data-url="{{ route('expenses.destroy', $expense) }}"
                data-confirm-message="Deseas archivar este gasto? Solo se permite si no tiene archivos adjuntos."
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
