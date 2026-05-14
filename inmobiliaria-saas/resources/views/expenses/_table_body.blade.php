@php
    $invoiceGroups = $expenses->getCollection()
        ->filter(fn ($expense) => $expense->invoice_id && $expense->invoice?->status !== 'deleted')
        ->groupBy('invoice_id');
    $standaloneExpenses = $expenses->getCollection()->filter(fn ($expense) => ! $expense->invoice_id || $expense->invoice?->status === 'deleted');
@endphp

@forelse ($invoiceGroups as $invoiceId => $groupExpenses)
    @php
        $invoice = $groupExpenses->first()->invoice;
        $invoiceTotal = $invoice?->total_amount ?? $groupExpenses
            ->where('status', \App\Enums\EntityStatus::Active->value)
            ->sum(fn ($expense) => (float) $expense->total_amount);
    @endphp
    <tr class="whitespace-nowrap bg-sky-50/60" data-invoice-row-id="{{ $invoiceId }}">
        <td class="w-32 px-6 py-4 font-semibold text-stone-900">{{ $invoice?->invoice_date?->format('Y-m-d') ?: 'Sin fecha' }}</td>
        <td class="px-6 py-4 text-stone-700">{{ $invoice?->project?->name ?: $groupExpenses->first()->project?->name }}</td>
        <td class="w-80 px-6 py-4 text-stone-700">
            <div class="font-semibold text-stone-900">Factura {{ $invoice?->invoice_number ?: 'sin número' }}</div>
            <div class="text-xs text-stone-500">{{ $groupExpenses->count() }} gastos asociados</div>
        </td>
        <td class="px-6 py-4 text-stone-700">{{ $invoice?->invoice_number ?: 'Sin número' }}</td>
        <td class="px-6 py-4 text-stone-700">{{ $invoice?->provider?->name ?: $groupExpenses->first()->provider?->name }}</td>
        <td class="px-6 py-4 font-semibold text-stone-900">$ {{ number_format((float) $invoiceTotal, 0, ',', '.') }}</td>
        <td class="px-6 py-4">
            <button
                type="button"
                data-action="invoice-status"
                data-url="{{ route('invoices.status', $invoice) }}"
                data-current-status="{{ $invoice?->status ?: 'open' }}"
            >
                <x-status-badge :value="$invoice?->status ?: 'open'" class="cursor-pointer transition hover:opacity-80" />
            </button>
        </td>
        <td class="px-6 py-4">
            <div class="flex items-center justify-end gap-2">
                <button type="button" data-action="invoice-detail" data-url="{{ route('invoices.show', $invoice) }}" data-title="Detalle de factura" class="rounded-2xl border border-sky-200 p-2 text-sky-700 transition hover:bg-sky-100" title="Ver detalle">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10 4.5c-4.5 0-7.5 4.5-7.5 5.5s3 5.5 7.5 5.5 7.5-4.5 7.5-5.5-3-5.5-7.5-5.5zm0 8.5a3 3 0 110-6 3 3 0 010 6zm0-1.5a1.5 1.5 0 100-3 1.5 1.5 0 000 3z" />
                    </svg>
                </button>
            </div>
        </td>
    </tr>
@empty
@endforelse

@foreach ($standaloneExpenses as $expense)
    @include('expenses._row', ['expense' => $expense])
@endforeach

@if ($invoiceGroups->isEmpty() && $standaloneExpenses->isEmpty())
    <tr data-empty-state>
        <td colspan="8" class="px-6 py-10 text-center text-stone-500">
            No se encontraron gastos con los filtros actuales.
        </td>
    </tr>
@endif
