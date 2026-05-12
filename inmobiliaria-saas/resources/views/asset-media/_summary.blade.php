<div class="grid gap-4 md:grid-cols-4">
    <div class="rounded-3xl border border-stone-200 bg-white p-5 shadow-sm">
        <p class="text-xs uppercase tracking-wide text-stone-400">Activo</p>
        <p class="mt-2 text-lg font-semibold text-stone-900">{{ $asset->name }}</p>
        <p class="mt-2 text-sm text-stone-500">{{ $asset->company?->name ?: 'Sin empresa' }}</p>
    </div>

    <x-metric-card label="Archivos" :value="$summary['files']" hint="Fotos y videos activos" />
    <x-metric-card label="Fotos" :value="$summary['images']" hint="Imágenes activas" />
    <x-metric-card label="Videos" :value="$summary['videos']" :hint="number_format(($summary['size'] ?? 0) / 1024 / 1024, 2).' MB acumulados'" />
</div>
