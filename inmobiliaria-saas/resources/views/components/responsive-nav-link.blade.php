@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full touch-manipulation ps-3 pe-4 py-2 border-l-4 border-sky-300 text-start text-base font-medium text-sky-800 bg-sky-50 focus:outline-none focus:text-sky-900 focus:bg-sky-100 focus:border-sky-500 transition duration-150 ease-in-out'
            : 'block w-full touch-manipulation ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-stone-600 active:bg-stone-50 active:text-stone-900 focus:outline-none focus:text-stone-800 focus:bg-stone-50 focus:border-stone-300 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes, 'data-mobile-nav-link' => true]) }}>
    {{ $slot }}
</a>
