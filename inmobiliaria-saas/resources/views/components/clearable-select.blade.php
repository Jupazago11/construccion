@props(['selected' => ''])

<div class="relative mt-1" x-data="{ hasVal: {{ $selected !== '' ? 'true' : 'false' }} }">
    <select
        @change="hasVal = $event.target.value !== ''"
        {{ $attributes->merge(['class' => 'block w-full rounded-2xl border-stone-300 shadow-sm focus:border-stone-900 focus:ring-stone-900']) }}
    >
        {{ $slot }}
    </select>
    <button
        type="button"
        x-show="hasVal"
        x-cloak
        @click="
            let sel = $el.closest('[x-data]').querySelector('select');
            sel.value = '';
            hasVal = false;
            sel.dispatchEvent(new Event('change', { bubbles: true }));
        "
        title="Limpiar"
        class="absolute -right-1.5 -top-1.5 z-10 flex h-4 w-4 items-center justify-center rounded-full bg-stone-500 text-white transition hover:bg-stone-700"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
        </svg>
    </button>
</div>
