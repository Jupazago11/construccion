<div class="mt-4 overflow-x-auto">
    <table class="min-w-full divide-y divide-stone-200 text-sm">
        <thead class="bg-stone-50 text-left text-stone-500">
            <tr>
                <th class="px-4 py-3 font-medium">Fecha</th>
                <th class="px-4 py-3 font-medium">Proyecto</th>
                <th class="px-4 py-3 font-medium">Ítem</th>
                <th class="px-4 py-3 font-medium">Proveedor</th>
                <th class="px-4 py-3 font-medium">Total</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-stone-100">
            @forelse ($history as $movement)
                <tr>
                    <td class="whitespace-nowrap px-4 py-3 text-stone-600">{{ $movement->movement_date ? \Illuminate\Support\Carbon::parse($movement->movement_date)->format('Y-m-d') : '—' }}</td>
                    <td class="whitespace-nowrap px-4 py-3 text-stone-600">{{ $movement->project_name ?: '—' }}</td>
                    <td class="whitespace-nowrap px-4 py-3 text-stone-600">
                        <div>{{ $movement->item_name }}</div>
                        @if ($movement->group_name)
                            <div class="text-xs text-stone-500">{{ $movement->group_name }}</div>
                        @endif
                        @if ($movement->subgroup_name)
                            <div class="text-xs text-stone-500">{{ $movement->subgroup_name }}</div>
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 text-stone-600">{{ $movement->provider_name ?: '—' }}</td>
                    <td class="whitespace-nowrap px-4 py-3 text-stone-900">$ {{ number_format((float) $movement->total_amount, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-8 text-center text-stone-500">Sin datos.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">
    {{ $history->links() }}
</div>
