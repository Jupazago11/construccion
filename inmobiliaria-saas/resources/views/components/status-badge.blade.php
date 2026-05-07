@props(['value'])

@php
    $classes = match ($value) {
        'active' => 'bg-emerald-100 text-emerald-700 ring-emerald-600/20',
        'inactive' => 'bg-amber-100 text-amber-700 ring-amber-600/20',
        'deleted' => 'bg-rose-100 text-rose-700 ring-rose-600/20',
        'planning' => 'bg-sky-100 text-sky-700 ring-sky-600/20',
        'paused' => 'bg-orange-100 text-orange-700 ring-orange-600/20',
        'completed' => 'bg-teal-100 text-teal-700 ring-teal-600/20',
        'cancelled' => 'bg-stone-200 text-stone-700 ring-stone-500/20',
        default => 'bg-stone-100 text-stone-700 ring-stone-500/20',
    };

    $labels = [
        'active' => 'Activo',
        'inactive' => 'Inactivo',
        'deleted' => 'Eliminado',
        'planning' => 'Planeación',
        'paused' => 'Pausado',
        'completed' => 'Completado',
        'cancelled' => 'Cancelado',
    ];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold uppercase tracking-wide ring-1 ring-inset {$classes}"]) }}>
    {{ $labels[$value] ?? str_replace('_', ' ', $value) }}
</span>
