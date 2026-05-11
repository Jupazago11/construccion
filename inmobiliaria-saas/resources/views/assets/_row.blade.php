@php
    $typeLabels = ['tool' => 'Herramienta', 'equipment' => 'Equipo'];
    $conditionLabels = ['new' => 'Nuevo', 'used' => 'De segunda'];
@endphp

<tr data-row-id="{{ $asset->id }}">
    <td class="px-6 py-4">
        <div class="font-semibold text-stone-900">{{ $asset->name }}</div>
        <div class="text-stone-500">{{ $asset->purchase_date?->format('Y-m-d') ?: 'Sin fecha de compra' }}</div>
    </td>
    <td class="px-6 py-4 text-stone-600">
        {{ $typeLabels[$asset->asset_type] ?? $asset->asset_type }}
    </td>
    <td class="px-6 py-4 text-stone-600">
        {{ $conditionLabels[$asset->asset_condition] ?? $asset->asset_condition }}
    </td>
    <td class="px-6 py-4 text-stone-900">
        $ {{ number_format((float) $asset->purchase_value, 0, ',', '.') }}
    </td>
    <td class="px-6 py-4 text-stone-600">
        <div>{{ number_format((int) ($asset->active_novelties_count ?? 0)) }} registros</div>
        <div class="text-stone-500">Costo acumulado: $ {{ number_format((float) ($asset->active_novelties_cost_sum ?? 0), 0, ',', '.') }}</div>
    </td>
    <td class="px-6 py-4">
        <div class="flex items-center justify-end gap-2">
            <button
                type="button"
                data-action="create"
                data-url="{{ route('assets.novelties.create', ['asset' => $asset] + request()->query()) }}"
                data-title="Registrar novedad"
                class="rounded-2xl border border-sky-200 p-2 text-sky-700 transition hover:bg-sky-50"
                title="Registrar novedad"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h7a1 1 0 100-2H4V5h8v3a2 2 0 002 2h3v1a1 1 0 102 0V9a2 2 0 00-.586-1.414l-4-4A2 2 0 0013 3H4z" />
                    <path d="M15 12a1 1 0 011 1v1h1a1 1 0 110 2h-1v1a1 1 0 11-2 0v-1h-1a1 1 0 110-2h1v-1a1 1 0 011-1z" />
                </svg>
            </button>
            <button
                type="button"
                data-action="edit"
                data-url="{{ route('assets.edit', ['asset' => $asset] + request()->query()) }}"
                data-title="Editar activo"
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
                data-url="{{ route('assets.destroy', ['asset' => $asset] + request()->query()) }}"
                data-confirm-message="¿Deseas archivar este activo?"
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
