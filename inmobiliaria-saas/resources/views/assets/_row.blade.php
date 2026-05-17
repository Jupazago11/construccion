@php
    $conditionLabels = ['new' => 'Nuevo', 'used' => 'De segunda'];
    $assetVisibleValue = (float) $asset->purchase_value + (float) ($asset->active_novelties_cost_sum ?? 0);
@endphp

<tr data-row-id="{{ $asset->id }}">
    <td class="px-6 py-4">
        <div class="whitespace-nowrap font-semibold text-stone-900">{{ $asset->name }}</div>
        <div class="whitespace-nowrap text-stone-500">{{ $asset->purchase_date?->format('Y-m-d') ?: 'Sin fecha de compra' }}</div>
    </td>
    <td class="px-6 py-4 text-stone-600">
        {{ $asset->type?->name ?: $asset->asset_type }}
    </td>
    <td class="px-6 py-4 text-stone-600">
        {{ $conditionLabels[$asset->asset_condition] ?? $asset->asset_condition }}
    </td>
    <td class="whitespace-nowrap px-6 py-4 text-stone-900">
        <div>$ {{ number_format($assetVisibleValue, 0, ',', '.') }}</div>
        <div class="text-xs text-stone-500">Compra + novedades que si dan valor</div>
    </td>
    <td class="px-6 py-4 text-stone-600">
        <div class="app-two-line-text min-w-52">
            <div>{{ number_format((int) ($asset->active_novelties_count ?? 0)) }} registros</div>
            <div class="text-stone-500">Costo acumulado: $ {{ number_format((float) ($asset->active_novelties_cost_sum ?? 0), 0, ',', '.') }}</div>
        </div>
    </td>
    <td class="px-6 py-4">
        <div class="flex items-center justify-end gap-3">
            <a
                href="{{ route('assets.media.index', $asset) }}"
                class="rounded-2xl border border-stone-200 p-2.5 text-stone-600 transition hover:bg-stone-100 hover:text-stone-900"
                title="Fotos y videos"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8 2a3 3 0 00-3 3v7a5 5 0 0010 0V6a1 1 0 10-2 0v6a3 3 0 11-6 0V5a1 1 0 112 0v7a1 1 0 102 0V7a1 1 0 112 0v5a3 3 0 11-6 0V5a3 3 0 016 0v7a5 5 0 11-10 0V5a5 5 0 0110 0v6a1 1 0 11-2 0V5a3 3 0 00-3-3z" clip-rule="evenodd" />
                </svg>
            </a>
            <button
                type="button"
                data-action="create"
                data-url="{{ route('assets.novelties.create', ['asset' => $asset] + request()->query()) }}"
                data-title="Registrar novedad"
                x-on:click.prevent.stop="openModal($el.dataset.url, $el.dataset.title)"
                class="app-create-icon-button"
                title="Registrar novedad"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 13V5a2 2 0 00-2-2H4a2 2 0 00-2 2v8a2 2 0 002 2h3l3 3 3-3h3a2 2 0 002-2zM5 7a1 1 0 011-1h8a1 1 0 110 2H6a1 1 0 01-1-1zm1 3a1 1 0 000 2h3a1 1 0 100-2H6z" clip-rule="evenodd" />
                </svg>
            </button>
            <button
                type="button"
                data-action="edit"
                data-url="{{ route('assets.edit', ['asset' => $asset] + request()->query()) }}"
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
