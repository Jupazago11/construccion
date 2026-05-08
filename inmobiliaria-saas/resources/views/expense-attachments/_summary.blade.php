<div class="grid gap-4 md:grid-cols-4">
    <div class="rounded-3xl border border-stone-200 bg-white p-5 shadow-sm">
        <p class="text-xs uppercase tracking-wide text-stone-400">Proyecto</p>
        <p class="mt-2 text-lg font-semibold text-stone-900">{{ $expense->project?->name ?: 'Sin proyecto' }}</p>
        <p class="mt-2 text-sm text-stone-500">{{ $expense->description }}</p>
    </div>

    <x-metric-card label="Adjuntos" :value="$summary['attachments']" hint="Archivos activos del gasto" />
    <x-metric-card label="Tamaño total" :value="number_format(($summary['size'] ?? 0) / 1024 / 1024, 2).' MB'" hint="Peso acumulado de archivos activos" />
    <div class="rounded-3xl border border-stone-200 bg-white p-5 shadow-sm">
        <p class="text-xs uppercase tracking-wide text-stone-400">Gasto</p>
        <p class="mt-2 text-lg font-semibold text-stone-900">{{ $expense->expense_number }}</p>
        <div class="mt-3"><x-status-badge :value="$expense->status" /></div>
    </div>
</div>
