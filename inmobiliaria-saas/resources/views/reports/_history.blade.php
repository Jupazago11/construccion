<div class="mt-4 overflow-x-auto">
    <table class="min-w-full divide-y divide-stone-200 text-sm">
        <thead class="bg-stone-50 text-left text-stone-500">
            <tr>
                <th class="px-4 py-3 font-medium">Fecha</th>
                <th class="px-4 py-3 font-medium">Proyecto</th>
                <th class="px-4 py-3 font-medium">Clasificación</th>
                <th class="px-4 py-3 font-medium">Proveedor</th>
                <th class="px-4 py-3 font-medium">Total</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-stone-100">
            @forelse ($history as $expense)
                <tr>
                    <td class="whitespace-nowrap px-4 py-3 text-stone-600">{{ $expense->expense_date?->format('Y-m-d') }}</td>
                    <td class="whitespace-nowrap px-4 py-3 text-stone-600">{{ $expense->project?->name }}</td>
                    <td class="whitespace-nowrap px-4 py-3 text-stone-600">
                        <div>{{ $expense->category?->name ?: 'Sin categoría' }}</div>
                        @if ($expense->subcategory)
                            <div class="text-xs text-stone-500">{{ $expense->subcategory->name }}</div>
                        @endif
                        @if ($expense->auxiliary)
                            <div class="text-xs text-stone-500">{{ $expense->auxiliary->name }}</div>
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 text-stone-600">{{ $expense->provider?->name ?: 'Sin proveedor' }}</td>
                    <td class="whitespace-nowrap px-4 py-3 text-stone-900">$ {{ number_format((float) $expense->total_amount, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-8 text-center text-stone-500">Sin datos.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">
    {{ $history->links('vendor.pagination.tailwind') }}
</div>
