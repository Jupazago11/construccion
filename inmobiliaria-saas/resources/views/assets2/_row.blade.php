@php
    $assetBaseTotal = (float) $asset2->purchase_value * (int) ($asset2->quantity ?: 1);
@endphp

<tr
    data-row-id="{{ $asset2->id }}"
    data-row-open
    data-url="{{ route('assets2.novelties.create', ['asset2' => $asset2] + request()->query()) }}"
    data-title="Registrar novedad"
    class="cursor-pointer transition hover:bg-stone-50"
>
    <td class="px-6 py-4">
        <div class="whitespace-nowrap font-semibold text-stone-900">{{ $asset2->name }}</div>
        <div class="whitespace-nowrap text-stone-500">{{ $asset2->purchase_date?->format('Y-m-d') ?: 'Sin fecha de compra' }}</div>
    </td>
    <td class="px-6 py-4 text-stone-600">
        {{ number_format((int) ($asset2->quantity ?: 1), 0, ',', '.') }}
    </td>
    <td class="whitespace-nowrap px-6 py-4 text-stone-900">
        $ {{ number_format((float) $asset2->purchase_value, 0, ',', '.') }}
    </td>
    <td class="whitespace-nowrap px-6 py-4 text-stone-900">
        <div>$ {{ number_format($assetBaseTotal, 0, ',', '.') }}</div>
        <div class="text-xs text-stone-500">$ {{ number_format((float) $asset2->purchase_value, 0, ',', '.') }} x {{ number_format((int) ($asset2->quantity ?: 1), 0, ',', '.') }}</div>
    </td>
    <td class="px-6 py-4 text-stone-600">
        <div class="app-two-line-text min-w-52">
            <div>{{ number_format((int) ($asset2->active_novelties_count ?? 0)) }} registros</div>
            <div class="text-stone-500">Costo que da valor: $ {{ number_format((float) ($asset2->active_novelties_cost_sum ?? 0), 0, ',', '.') }}</div>
        </div>
    </td>
    <td class="px-6 py-4">
        <div class="flex items-center justify-end gap-3">
            <a
                href="{{ route('assets2.media.index', $asset2) }}"
                class="rounded-2xl border border-stone-200 p-2.5 text-stone-600 transition hover:bg-stone-100 hover:text-stone-900"
                title="Fotos y videos"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8 2a3 3 0 00-3 3v7a5 5 0 0010 0V6a1 1 0 10-2 0v6a3 3 0 11-6 0V5a1 1 0 112 0v7a1 1 0 102 0V7a1 1 0 112 0v5a3 3 0 11-6 0V5a3 3 0 016 0v7a5 5 0 11-10 0V5a5 5 0 0110 0v6a1 1 0 11-2 0V5a3 3 0 00-3-3z" clip-rule="evenodd" />
                </svg>
            </a>
            <button
                type="button"
                data-action="edit"
                data-url="{{ route('assets2.edit', ['asset2' => $asset2] + request()->query()) }}"
                data-title="Editar activo"
                class="rounded-2xl border border-stone-200 p-2.5 text-stone-600 transition hover:bg-stone-100 hover:text-stone-900"
                title="Editar"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M17.414 2.586a2 2 0 010 2.828l-8.5 8.5a2 2 0 01-.878.497l-3 1a1 1 0 01-1.265-1.265l1-3a2 2 0 01.497-.878l8.5-8.5a2 2 0 012.828 0z" />
                </svg>
            </button>
        </div>
    </td>
</tr>
