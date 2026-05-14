<div class="grid grid-cols-3 gap-3 sm:grid-cols-4 lg:grid-cols-5" data-invoice-attachments>
    @forelse ($invoice->attachments as $attachment)
        @php
            $isImage = str_starts_with((string) $attachment->mime_type, 'image/');
            $isVideo = str_starts_with((string) $attachment->mime_type, 'video/');
            $previewUrl = route('invoices.attachments.preview', [$invoice, $attachment]);
            $previewName = $attachment->original_name ?: 'Archivo';
        @endphp
        <div class="group relative overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm">
            @if ($isImage || $isVideo)
                <button
                    type="button"
                    class="block aspect-square w-full bg-stone-100 text-left"
                    x-on:click="previewOpen = true; previewUrl = @js($previewUrl); previewType = @js($isVideo ? 'video' : 'image'); previewName = @js($previewName)"
                    title="Ver archivo"
                >
            @else
                <a href="{{ $previewUrl }}" target="_blank" class="block aspect-square bg-stone-100" title="Ver archivo">
            @endif
                @if ($isImage)
                    <img src="{{ $previewUrl }}" alt="{{ $attachment->original_name ?: 'Archivo' }}" class="h-full w-full object-cover">
                @elseif ($isVideo)
                    <div class="relative h-full w-full bg-stone-900 text-white">
                        <video src="{{ $previewUrl }}#t=0.1" class="h-full w-full object-cover" muted preload="metadata" playsinline></video>
                        <div class="absolute inset-0 flex items-center justify-center bg-black/25">
                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-white/90 text-stone-900 shadow-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 translate-x-0.5" viewBox="0 0 20 20" fill="currentColor"><path d="M6.5 5.5v9l7-4.5-7-4.5z" /></svg>
                            </span>
                        </div>
                    </div>
                @else
                    <div class="flex h-full w-full flex-col items-center justify-center gap-2 text-stone-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor"><path d="M4.5 2A2.5 2.5 0 002 4.5v11A2.5 2.5 0 004.5 18h11a2.5 2.5 0 002.5-2.5V8.621a2.5 2.5 0 00-.732-1.768l-4.12-4.121A2.5 2.5 0 0011.378 2H4.5z" /></svg>
                        <span class="text-xs font-medium">Archivo</span>
                    </div>
                @endif
            @if ($isImage || $isVideo)
                </button>
            @else
                </a>
            @endif
            <div class="min-w-0 px-2 py-1.5">
                <div class="truncate text-xs font-medium text-stone-800">{{ $attachment->original_name ?: 'Archivo' }}</div>
            </div>
            <button type="button" data-action="invoice-attachment-delete" data-url="{{ route('invoices.attachments.destroy', [$invoice, $attachment]) }}" class="absolute right-2 top-2 rounded-full border border-rose-200 bg-white/95 p-1.5 text-rose-700 shadow-sm transition hover:bg-rose-50" title="Eliminar archivo">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.257 3.099c.366-.446.911-.699 1.486-.699h.514c.575 0 1.12.253 1.486.699L12.85 4H16a1 1 0 110 2h-1l-.867 10.142A2 2 0 0112.14 18H7.86a2 2 0 01-1.993-1.858L5 6H4a1 1 0 010-2h3.15l1.107-.901zM8 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm4-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
            </button>
        </div>
    @empty
        <div class="col-span-full rounded-2xl border border-dashed border-stone-300 bg-white px-4 py-6 text-center text-sm text-stone-500">
            Sin archivos cargados.
        </div>
    @endforelse
</div>
