<div
    class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm"
    x-data="{ previewOpen: false, previewUrl: '', previewType: '', previewName: '' }"
>
    <div class="flex items-center justify-between gap-4">
        <h2 class="text-lg font-semibold text-stone-900">Galería del activo</h2>
        <p class="text-sm text-stone-500">{{ $asset->media->count() }} activos</p>
    </div>

    <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($asset->media as $media)
            @php
                $previewUrl = route('assets.media.preview', [$asset, $media]);
                $mediaName = $media->original_name ?: basename($media->path);
            @endphp

            <article class="overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm">
                <button
                    type="button"
                    class="block aspect-[4/3] w-full bg-stone-100 text-left"
                    x-on:click="previewOpen = true; previewUrl = @js($previewUrl); previewType = @js($media->media_type); previewName = @js($mediaName)"
                    title="Ver archivo"
                >
                    @if ($media->isImage())
                        <img src="{{ $previewUrl }}" alt="{{ $mediaName }}" class="h-full w-full object-cover">
                    @else
                        <div class="relative h-full w-full bg-stone-900 text-white">
                            <video src="{{ $previewUrl }}#t=0.1" class="h-full w-full object-cover" muted preload="metadata" playsinline></video>
                            <div class="absolute inset-0 flex items-center justify-center bg-black/25">
                                <span class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-white/90 text-stone-900 shadow-sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 translate-x-0.5" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M6.5 5.5v9l7-4.5-7-4.5z" />
                                    </svg>
                                </span>
                            </div>
                        </div>
                    @endif
                </button>

                <div class="space-y-3 p-4">
                    <div class="min-w-0">
                        <div class="truncate font-semibold text-stone-900">{{ $mediaName }}</div>
                        <div class="mt-1 text-sm text-stone-500">
                            {{ $media->uploader?->name ?: 'Usuario no disponible' }} · {{ number_format(((int) $media->size) / 1024 / 1024, 2) }} MB
                        </div>
                    </div>

                    <div class="flex items-center justify-between gap-2">
                        <span class="rounded-full border border-stone-200 px-3 py-1 text-xs font-semibold uppercase text-stone-500">
                            {{ $media->isVideo() ? 'Video' : 'Foto' }}
                        </span>

                        @can('delete', $media)
                            <button
                                type="button"
                                data-action="delete"
                                data-url="{{ route('assets.media.destroy', [$asset, $media]) }}"
                                data-confirm-message="¿Deseas eliminar este archivo del activo?"
                                class="rounded-2xl border border-rose-200 p-2 text-rose-700 transition hover:bg-rose-50"
                                title="Eliminar archivo"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.366-.446.911-.699 1.486-.699h.514c.575 0 1.12.253 1.486.699L12.85 4H16a1 1 0 110 2h-1l-.867 10.142A2 2 0 0112.14 18H7.86a2 2 0 01-1.993-1.858L5 6H4a1 1 0 010-2h3.15l1.107-.901zM8 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm4-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        @endcan
                    </div>
                </div>
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-stone-300 bg-stone-50 px-4 py-10 text-center text-sm text-stone-500 sm:col-span-2 lg:col-span-3">
                Este activo todavía no tiene fotos ni videos.
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

                <template x-if="previewType === 'video'">
                    <video :src="previewUrl" class="max-h-[74vh] max-w-full rounded-xl" controls playsinline></video>
                </template>
            </div>
        </div>
    </div>
</div>
