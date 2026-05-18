<div class="flex flex-col gap-3 sm:flex-row">
    <div class="flex min-w-0 items-center justify-between gap-3 rounded-2xl border border-stone-200 bg-white px-4 py-3 shadow-sm sm:min-w-[240px]">
        <div class="min-w-0">
            <div class="text-[11px] font-semibold uppercase tracking-[0.14em] text-stone-400">Valor Activos</div>
            <div class="truncate text-sm text-stone-500">Compra + novedades que si dan valor</div>
        </div>
        <div class="shrink-0 text-sm font-semibold text-stone-900">$ {{ number_format((float) (($summary['assets_purchase_total'] ?? 0) + ($summary['novelties_cost_total'] ?? 0)), 0, ',', '.') }}</div>
    </div>

    <div class="flex min-w-0 items-center justify-between gap-3 rounded-2xl border border-stone-200 bg-white px-4 py-3 shadow-sm sm:min-w-[240px]">
        <div class="min-w-0">
            <div class="text-[11px] font-semibold uppercase tracking-[0.14em] text-stone-400">Valor Novedades</div>
            <div class="truncate text-sm text-stone-500">Solo novedades cuyo tipo da valor</div>
        </div>
        <div class="shrink-0 text-sm font-semibold text-stone-900">$ {{ number_format((float) ($summary['novelties_cost_total'] ?? 0), 0, ',', '.') }}</div>
    </div>
</div>
