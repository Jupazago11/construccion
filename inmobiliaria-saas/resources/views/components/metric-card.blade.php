@props(['label', 'value', 'hint' => null])

<div {{ $attributes->merge(['class' => 'rounded-3xl border border-stone-200 bg-white p-5 shadow-sm']) }}>
    <p class="text-sm font-medium uppercase tracking-[0.18em] text-stone-400">{{ $label }}</p>
    <p class="mt-3 text-3xl font-semibold tracking-tight text-stone-900">{{ $value }}</p>
    @if ($hint)
        <p class="mt-2 text-sm text-stone-500">{{ $hint }}</p>
    @endif
</div>
