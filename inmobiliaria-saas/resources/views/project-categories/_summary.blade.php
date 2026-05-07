<div class="grid gap-4 md:grid-cols-4">
    <div class="rounded-3xl border border-stone-200 bg-white p-5 shadow-sm">
        <p class="text-xs uppercase tracking-wide text-stone-400">Proyecto</p>
        <p class="mt-2 text-lg font-semibold text-stone-900">{{ $project->name }}</p>
        <div class="mt-3"><x-status-badge :value="$project->status" /></div>
    </div>

    <x-metric-card label="Categorías" :value="$summary['categories']" hint="Primer nivel del árbol" />
    <x-metric-card label="Subcategorías" :value="$summary['subcategories']" hint="Segundo nivel del árbol" />
    <x-metric-card label="Auxiliares" :value="$summary['auxiliaries']" hint="Tercer nivel opcional" />
</div>
