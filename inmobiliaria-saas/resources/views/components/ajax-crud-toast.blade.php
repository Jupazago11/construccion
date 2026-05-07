<div
    x-cloak
    x-show="toastVisible"
    x-transition:enter="transform transition ease-out duration-200"
    x-transition:enter-start="translate-y-4 opacity-0"
    x-transition:enter-end="translate-y-0 opacity-100"
    x-transition:leave="transform transition ease-in duration-150"
    x-transition:leave-start="translate-y-0 opacity-100"
    x-transition:leave-end="translate-y-4 opacity-0"
    class="pointer-events-none fixed inset-x-0 bottom-4 z-50 flex justify-center px-4"
    style="display: none;"
>
    <div
        class="pointer-events-auto flex w-full max-w-md items-start gap-3 rounded-2xl border px-4 py-3 shadow-xl"
        :class="toastType === 'error'
            ? 'border-rose-200 bg-white text-rose-700'
            : 'border-emerald-200 bg-white text-emerald-700'"
    >
        <div
            class="mt-0.5 h-2.5 w-2.5 rounded-full"
            :class="toastType === 'error' ? 'bg-rose-500' : 'bg-emerald-500'"
        ></div>

        <div class="min-w-0 flex-1 text-sm font-medium" x-text="toastMessage"></div>

        <button
            type="button"
            class="rounded-full p-1 text-stone-400 transition hover:bg-stone-100 hover:text-stone-700"
            x-on:click="hideToast()"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
        </button>
    </div>
</div>
