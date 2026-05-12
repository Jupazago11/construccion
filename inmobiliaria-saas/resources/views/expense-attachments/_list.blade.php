<div
    class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm"
    x-data="{ previewOpen: false, previewUrl: '', previewType: '', previewName: '' }"
>
    <div class="flex items-center justify-between gap-4">
        <h2 class="text-lg font-semibold text-stone-900">Archivos registrados</h2>
        <p class="text-sm text-stone-500">{{ $expense->attachments->count() }} activos</p>
    </div>

    <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($expense->attachments as $attachment)
            @php
                $previewUrl = route('expenses.attachments.preview', [$expense, $attachment]);
                $attachmentName = $attachment->original_name ?: basename($attachment->path);
                $previewType = $attachment->isImage() ? 'image' : ($attachment->isPdf() ? 'pdf' : 'file');
            @endphp

            <article class="overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm">
                <button
                    type="button"
                    class="block aspect-[4/3] w-full bg-stone-100 text-left"
                    x-on:click="previewOpen = true; previewUrl = @js($previewUrl); previewType = @js($previewType); previewName = @js($attachmentName)"
                    title="Ver archivo"
                >
                    @if ($attachment->isImage())
                        <img src="{{ $previewUrl }}" alt="{{ $attachmentName }}" class="h-full w-full object-cover">
                    @else
                        <div class="flex h-full w-full items-center justify-center bg-stone-100 text-stone-700">
                            <div class="text-center">
                                <span class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-white text-stone-800 shadow-sm">
                                    @if ($attachment->isPdf())
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M4 2a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V7.414A2 2 0 0017.414 6L14 2.586A2 2 0 0012.586 2H4zm7 1.5V7a1 1 0 001 1h3.5L11 3.5zM5.75 11a.75.75 0 000 1.5h8.5a.75.75 0 000-1.5h-8.5zm0 3a.75.75 0 000 1.5h5.5a.75.75 0 000-1.5h-5.5z" clip-rule="evenodd" />
                                        </svg>
                                    @else
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V8.414A2 2 0 0017.414 7L14 3.586A2 2 0 0012.586 3H4z" />
                                        </svg>
                                    @endif
                                </span>
                                <span class="mt-2 block text-sm font-medium">{{ $attachment->isPdf() ? 'PDF' : 'Archivo' }}</span>
                            </div>
                        </div>
                    @endif
                </button>

                <div class="space-y-3 p-4">
                    <div class="min-w-0">
                        <div class="truncate font-semibold text-stone-900">{{ $attachmentName }}</div>
                        <div class="mt-1 text-sm text-stone-500">
                            {{ $attachment->uploader?->name ?: 'Usuario no disponible' }} · {{ number_format(((int) $attachment->size) / 1024 / 1024, 2) }} MB
                        </div>
                    </div>

                    <div class="flex items-center justify-between gap-2">
                        <span class="rounded-full border border-stone-200 px-3 py-1 text-xs font-semibold uppercase text-stone-500">
                            {{ $attachment->isImage() ? 'Imagen' : ($attachment->isPdf() ? 'PDF' : 'Archivo') }}
                        </span>

                        <div class="flex items-center gap-2">
                            <a href="{{ route('expenses.attachments.download', [$expense, $attachment]) }}" class="rounded-2xl border border-stone-200 px-3 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-50">
                                Descargar
                            </a>
                            <button
                                type="button"
                                data-action="delete"
                                data-url="{{ route('expenses.attachments.destroy', [$expense, $attachment]) }}"
                                data-confirm-message="¿Deseas archivar este archivo?"
                                class="rounded-2xl border border-rose-200 p-2 text-rose-700 transition hover:bg-rose-50"
                                title="Archivar archivo"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.366-.446.911-.699 1.486-.699h.514c.575 0 1.12.253 1.486.699L12.85 4H16a1 1 0 110 2h-1l-.867 10.142A2 2 0 0112.14 18H7.86a2 2 0 01-1.993-1.858L5 6H4a1 1 0 010-2h3.15l1.107-.901zM8 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm4-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-stone-300 bg-stone-50 px-4 py-10 text-center text-sm text-stone-500 sm:col-span-2 lg:col-span-3">
                Este gasto todavía no tiene archivos adjuntos activos.
            </div>
        @endforelse
    </div>

    <div
        x-show="previewOpen"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4"
        x-on:keydown.escape.window="previewOpen = false"
    >
        <div class="max-h-[92vh] w-full max-w-5xl overflow-hidden rounded-3xl bg-white shadow-2xl">
            <div class="flex items-center justify-between gap-3 border-b border-stone-200 px-5 py-4">
                <h3 class="truncate text-sm font-semibold text-stone-900" x-text="previewName"></h3>
                <button type="button" class="rounded-2xl border border-stone-200 px-3 py-2 text-sm text-stone-700 transition hover:bg-stone-50" x-on:click="previewOpen = false">
                    Cerrar
                </button>
            </div>

            <div class="flex max-h-[78vh] items-center justify-center bg-stone-950 p-3">
                <template x-if="previewType === 'image'">
                    <img :src="previewUrl" :alt="previewName" class="max-h-[74vh] max-w-full rounded-xl object-contain">
                </template>

                <template x-if="previewType === 'pdf'">
                    <iframe :src="previewUrl" class="h-[74vh] w-full rounded-xl bg-white"></iframe>
                </template>

                <template x-if="previewType === 'file'">
                    <div class="rounded-2xl bg-white px-6 py-5 text-center text-sm text-stone-600">
                        Este tipo de archivo se debe descargar para revisarlo.
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>
