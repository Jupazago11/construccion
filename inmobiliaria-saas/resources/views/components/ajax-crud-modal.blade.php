<div
    x-cloak
    x-show="modalOpen"
    class="fixed inset-0 z-50 overflow-hidden"
    x-bind:class="nestedModalOpen ? 'pointer-events-none' : ''"
    style="display: none;"
>
    <div
        x-show="modalOpen && ! nestedModalOpen"
        class="fixed inset-0 bg-stone-900/50"
        x-on:click="closeModal()"
    ></div>

    <div class="relative flex h-full w-full items-center justify-center px-3 py-3 sm:px-6 sm:py-6">
        <div
            x-show="modalOpen && ! nestedModalOpen"
            class="grid max-h-[92dvh] w-full max-w-3xl grid-rows-[auto,minmax(0,1fr)] overflow-hidden rounded-3xl border border-stone-200 bg-white shadow-2xl"
        >
            <div class="flex items-start justify-between border-b border-stone-200 px-6 py-5">
                <div>
                    <h2 class="text-xl font-semibold text-stone-900" x-text="modalTitle"></h2>
                </div>
                <button type="button" class="rounded-full p-2 text-stone-500 transition hover:bg-stone-100 hover:text-stone-900" x-on:click="closeModal()">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>

            <div
                class="min-h-0 overflow-hidden px-4 py-4 sm:px-6 sm:py-6"
                x-on:submit.prevent.stop="submitForm($event)"
                x-on:click.stop="handleClick($event)"
            >
                <template x-if="loading">
                    <div class="flex items-center justify-center py-12 text-sm text-stone-500">
                        Cargando formulario...
                    </div>
                </template>

                <div x-ref="modalContent" class="flex h-full min-h-0 flex-col overflow-hidden" x-html="modalHtml"></div>
            </div>
        </div>
    </div>
</div>

<div
    x-cloak
    x-show="nestedModalOpen"
    class="fixed inset-0 z-[60] overflow-hidden"
    style="display: none;"
>
    <div
        x-show="nestedModalOpen"
        class="fixed inset-0 bg-stone-900/35"
        x-on:click="closeNestedModal()"
    ></div>

    <div class="relative flex h-full w-full items-center justify-center px-3 py-3 sm:px-6 sm:py-6">
        <div
            x-show="nestedModalOpen"
            class="grid max-h-[88dvh] w-full max-w-2xl grid-rows-[auto,minmax(0,1fr)] overflow-hidden rounded-3xl border border-stone-200 bg-white shadow-2xl"
        >
            <div class="flex items-start justify-between border-b border-stone-200 px-6 py-5">
                <h2 class="text-xl font-semibold text-stone-900" x-text="nestedModalTitle"></h2>
                <button type="button" class="rounded-full p-2 text-stone-500 transition hover:bg-stone-100 hover:text-stone-900" x-on:click="closeNestedModal()">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>

            <div
                class="min-h-0 overflow-hidden px-4 py-4 sm:px-6 sm:py-6"
                x-on:submit.prevent.stop="submitForm($event)"
                x-on:click.stop="handleClick($event)"
                data-nested-modal-content
            >
                <template x-if="nestedLoading">
                    <div class="flex items-center justify-center py-12 text-sm text-stone-500">
                        Cargando formulario...
                    </div>
                </template>

                <div x-ref="nestedModalContent" class="flex h-full min-h-0 flex-col overflow-hidden" x-html="nestedModalHtml"></div>
            </div>
        </div>
    </div>
</div>
