@forelse ($invoices as $invoice)
    <tr
        class="cursor-pointer whitespace-nowrap transition hover:bg-stone-50"
        x-on:click="window.location.assign(@js(route('invoices.show', $invoice)))"
    >
        <td class="px-6 py-4 text-stone-600">{{ $invoice->invoice_date?->format('d/m/Y') ?: '—' }}</td>
        <td class="px-6 py-4">
            <div class="font-medium text-stone-900">{{ $invoice->invoice_number ?: 'Sin referencia' }}</div>
            @if ($invoice->description)
                <div class="mt-0.5 max-w-xs truncate text-xs text-stone-400">{{ $invoice->description }}</div>
            @endif
        </td>
        <td class="px-6 py-4 text-stone-700">{{ $invoice->project?->name ?: '—' }}</td>
        <td class="px-6 py-4 text-stone-700">{{ $invoice->provider?->name ?: '—' }}</td>
        <td class="px-6 py-4 text-right font-semibold text-stone-900">
            $ {{ number_format((float) $invoice->total_amount, 0, ',', '.') }}
        </td>
        <td class="px-6 py-4">
            <x-status-badge :value="$invoice->status" />
        </td>
        <td class="px-6 py-4 text-right">
            <a
                href="{{ route('invoices.show', $invoice) }}"
                class="inline-flex items-center gap-1 rounded-2xl border border-stone-200 px-3 py-1.5 text-xs font-medium text-stone-600 transition hover:bg-stone-50 hover:text-stone-900"
                x-on:click.stop
            >
                Ver
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
            </a>
        </td>
    </tr>
@empty
    <tr data-empty-state>
        <td colspan="7" class="px-6 py-10 text-center text-stone-400">
            No se encontraron facturas con los filtros actuales.
        </td>
    </tr>
@endforelse
