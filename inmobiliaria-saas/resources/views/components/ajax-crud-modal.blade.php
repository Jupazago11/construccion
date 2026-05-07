<div
    x-cloak
    x-show="modalOpen"
    class="fixed inset-0 z-50 overflow-y-auto px-4 py-4 sm:px-6"
    style="display: none;"
>
    <div
        x-show="modalOpen"
        class="fixed inset-0 bg-stone-900/50"
        x-on:click="closeModal()"
    ></div>

    <div class="relative mx-auto flex min-h-full w-full max-w-3xl items-start py-2 sm:py-6">
        <div
            x-show="modalOpen"
            class="flex max-h-[calc(100vh-2rem)] w-full flex-col overflow-hidden rounded-3xl border border-stone-200 bg-white shadow-2xl sm:max-h-[calc(100vh-3rem)]"
        >
            <div class="flex items-start justify-between border-b border-stone-200 px-6 py-5">
                <div>
                    <h2 class="text-xl font-semibold text-stone-900" x-text="modalTitle"></h2>
                    <p class="mt-1 text-sm text-stone-500">Los cambios se guardan sin recargar la página.</p>
                </div>
                <button type="button" class="rounded-full p-2 text-stone-500 transition hover:bg-stone-100 hover:text-stone-900" x-on:click="closeModal()">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>

            <div class="min-h-0 flex-1 overflow-hidden px-6 py-6" x-on:submit.prevent="submitForm($event)" x-on:click="handleClick($event)">
                <template x-if="loading">
                    <div class="flex items-center justify-center py-12 text-sm text-stone-500">
                        Cargando formulario...
                    </div>
                </template>

                <template x-if="error && ! loading">
                    <div class="mb-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700" x-text="error"></div>
                </template>

                <div x-ref="modalContent" class="h-full overflow-hidden" x-html="modalHtml"></div>
            </div>
        </div>
    </div>
</div>
